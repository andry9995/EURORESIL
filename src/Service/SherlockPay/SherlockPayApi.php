<?php

namespace App\Service\SherlockPay;

use App\Service\AbstractApiClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SherlockPayApi extends AbstractApiClient
{
    public function __construct(
        HttpClientInterface                                $httpClient,
        EntityManagerInterface                             $em,
        #[Autowire('%env(SHERLOCK_PAY_BASE_URL)%')] string $baseUrl,
    )
    {
        parent::__construct($em);

        $this->setupClient($httpClient, [
            'base_uri' => $baseUrl,
        ]);
    }

    protected function getApiName(): string
    {
        return 'SherlockPay';
    }

    /**
     * @param array $payload
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function paymentInit(string $payload): array
    {
        return $this->call('POST', '/rs-services/v2/paymentInit', [
            'verify_peer' => false,
            'verify_host' => false,
            'timeout' => 60,
            'headers' => ['content-type: application/json'],
            'body' => $payload,
        ]);
    }
}