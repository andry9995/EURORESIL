<?php

namespace App\Service\SherlockPay;

use Exception;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

readonly class SherlockPayService
{
    public function __construct(
        private SherlockPayApi        $sherlockPayApi,
        private UrlGeneratorInterface $urlGenerator,
        #[Autowire('%env(SHERLOCK_PAY_SECRET_KEY)%')]
        private string                $secretKey,
        #[Autowire('%env(SHERLOCK_PAY_KEY_VERSION)%')]
        private string                $keyVersion,
        #[Autowire('%env(SHERLOCK_PAY_SEAL_ALGORITHM)%')]
        private string                $sealAlgorithm,
        #[Autowire('%env(SHERLOCK_PAY_MERCHANT_ID)%')]
        private string                $merchantId,
        #[Autowire('%env(SHERLOCK_PAY_INTERFACE_VERSION)%')]
        private string                $interfaceVersion,
        #[Autowire('%env(APP_ENV)%')]
        private string                $env,
    ) {}

    public function send(array $params): array
    {
        $requestData = [
            'normalReturnUrl' => $this->urlGenerator->generate(
                'app_pack_pay',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            'merchantId' => $this->merchantId,
            'amount' => sprintf("%04d", (int)((float)$params['amount'] * 100)),
            'orderId' => $params['orderId'],
            'orderChannel' => 'INTERNET',
            'currencyCode' => '978',
            'interfaceVersion' => $this->interfaceVersion,
            'captureMode' => 'AUTHOR_CAPTURE',
            'captureDay' => '0',
            'templateName' => 'BSCOM',
        ];

        if ($this->env !== 'prod') {
            $requestData['transactionReference'] = bin2hex(random_bytes(16));
        }

        $requestTable = $requestData;
        $requestTable['seal'] = $this->makeSeal($requestData);
        $requestTable['keyVersion'] = $this->keyVersion;
        $requestTable['sealAlgorithm'] = $this->sealAlgorithm;

        $response = $this->sherlockPayApi->paymentInit(
            json_encode($requestTable, JSON_UNESCAPED_UNICODE, 512)
        );

        if ($response['status']) {
            $responseData = [];
            foreach ($response['result'] as $key => $value) {
                if (strcasecmp($key, 'seal') !== 0) {
                    $responseData[$key] = $value;
                }
            }

            $seal = $this->makeSeal($responseData);

            if ($seal === $response['result']['seal']) {
                if($response['result']['redirectionStatusCode'] === '00') {
                    return $response;
                }
            }
        }

        return array_merge($response, [
           "status" => false,
        ]);
    }

    private function makeSeal(array $data): string
    {
        $sortedMultiDimArray = $this->recursiveTableSort($data);

        $singleDimArray = [];
        array_walk_recursive($sortedMultiDimArray, function ($value) use (&$singleDimArray) {
            $singleDimArray[] = $value;
        });

        $flattenedData = implode('', $singleDimArray);

        $useHmac = match ($this->sealAlgorithm) {
            'HMAC-SHA-256', '' => true,
            default => false,
        };

        return $this->computeSeal($flattenedData, $this->secretKey, $useHmac);
    }

    private function recursiveTableSort(array $table): array
    {
        ksort($table);
        foreach ($table as $key => $value) {
            if (is_array($value)) {
                $table[$key] = $this->recursiveTableSort($value);
            }
        }
        return $table;
    }

    private function computeSeal(string $data, string $secretKey, bool $useHmac = true): string
    {
        $encoding = mb_internal_encoding();

        if ($encoding !== 'UTF-8') {
            $data = iconv($encoding, 'UTF-8', $data);
            $secretKey = iconv($encoding, 'UTF-8', $secretKey);
        }

        return $useHmac
            ? hash_hmac('sha256', $data, $secretKey)
            : hash('sha256', $data . $secretKey);
    }

    private function extractPaymentResponseData(string $data): array
    {
        $response = [];
        foreach (explode('|', $data) as $field) {
            $parts = explode('=', $field, 2);
            if (count($parts) === 2) {
                $response[$parts[0]] = $parts[1];
            }
        }
        return $response;
    }

    public function paymentResponse(Request $request): array
    {
        $data = (string)$request->request->get('Data');
        $encoding = (string)$request->request->get('Encode');
        $seal = (string)$request->request->get('Seal');

        $computedSeal = $this->computeSeal(
            $data,
            $this->secretKey,
            $this->sealAlgorithm === 'HMAC-SHA-256'
        );

        if ($computedSeal !== $seal) {
            return [
                'success' => false,
                'error' => 'Seals are not equal',
            ];
        }

        if ($encoding === 'base64') {
            $data = base64_decode($data, true) ?: '';
        }

        $result = $this->extractPaymentResponseData($data);

        return [
            'success' => ($result['responseCode'] ?? null) === '00',
            'errorCode' => $result['responseCode'] ?? null,
            'data' => $result,
        ];
    }
}