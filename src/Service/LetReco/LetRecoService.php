<?php

namespace App\Service\LetReco;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

readonly class LetRecoService
{
    public function __construct(
        private LetRecoApi $letRecoApi
    )
    {
    }

    /**
     * @param array $params
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function send(array $params): array
    {
        $payload = [
            "service" => "Service LETRECO Loi 65",
            "subject" => $this->makeSubject($params),
            "comment" => $this->makeComment(),
            "ref1" => (string)$params['id'],
            "recipients" => $this->makeRecipients($params),
            "attachments" => $this->makeAttachments($params)
        ];

        return $this->letRecoApi->postRecord($payload, $params['letRecoUserId'], $params['letRecoPassword']);
    }

    /**
     * @param array $params
     * @return string
     */
    private function makeSubject(array $params): string
    {
        $subject = $params['subject'];

        if (($_ENV['APP_ENV'] ?? 'dev') !== 'prod') {
            return sprintf("TEST - %s", $subject);
        }

        return $subject;
    }

    /**
     * @return string
     */
    public function makeComment(): string
    {
        if (($_ENV['APP_ENV'] ?? 'dev') !== 'prod') {
            return "Test technique d'integration, ne pas tenir compte.";
        }

        return "";
    }

    /**
     * @param array $params
     * @return array[]
     */
    public function makeRecipients(array $params): array
    {
        return [
            [
                "type" => "TRANSIENT",
                "company" => $params["insurance"]["name"],
                "email" => $params["insurance"]["email"],
                "locale" => "fr",
                "address" => $params["insurance"]["address"],
                "postalCode" => $params["insurance"]["postalCode"],
                "city" => $params["insurance"]["city"],
                "country" => "France",
                "signer_quality" => "PROFESSIONAL",
                "signer_authentication_type" => "OTP_MAIL",
            ]
        ];
    }

    /**
     * @param array $params
     * @return array
     */
    public function makeAttachments(array $params): array
    {
        $attachments = [];

        foreach ($params["files"] as $file) {
            $attachments[] = [
                'filename' => pathinfo($file['filename'], PATHINFO_BASENAME),
                'data' => base64_encode(file_get_contents($file['path']))
            ];
        }

        return $attachments;
    }

    /**
     * @param string $reference
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getRecord(string $reference): array
    {
        return $this->letRecoApi->getRecord($reference);
    }

    /**
     * @param string $reference
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function depositProof(string $reference): array
    {
        return $this->letRecoApi->depositProof($reference);
    }

    /**
     * @param string $reference
     * @param string $email
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function acceptanceProof(string $reference, string $email): array
    {
        return $this->letRecoApi->acceptanceProof($reference, $email);
    }

    /**
     * @param string $reference
     * @param string $email
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function refusalProof(string $reference, string $email): array
    {
        return $this->letRecoApi->refusalProof($reference, $email);
    }

    /**
     * @param array $params
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function createUser(array $params): array
    {
        $responseCheckUser = $this->letRecoApi->getUserByEmail($params['email']);

        if (!$responseCheckUser['status']) {
            if($responseCheckUser['code'] === 404){
                $response = $this->letRecoApi->createUser([
                    'uid' => $params['uid'],
                    'email' => $params['email'],
                    'domain' => 'bscompp',
                    'active' => true,
                    'first_name' => $params['firstName'] ?? '',
                    'last_name' => $params['lastName'] ?? '',
                    'company' => $params['company'],
                    'locale' => 'fr',
//            'groups' => ["bscompp_users"],
                    'password' => $params['password'],
                ]);

                if($response['status']) {
                    return $this->letRecoApi->addUserToGroup($params['uid']);
                }
            }

            return $responseCheckUser;
        }

        $response = $this->letRecoApi->changePassword($params['email'], $params['password']);

        if($response['status']) {
            $this->letRecoApi->addUserToGroup($responseCheckUser['result']['uid']);
        }

        return $responseCheckUser;
    }
}