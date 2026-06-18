<?php

namespace App\Service\LetReco;

use App\Service\AbstractApiClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class LetRecoApi extends AbstractApiClient
{
    public function __construct(
        HttpClientInterface $httpClient,
        EntityManagerInterface $em,
        #[Autowire('%env(LETRECO_BASE_URL)%')] string $baseUrl,
        #[Autowire('%env(LETRECO_AUTH_IDENT)%')] string $authIdent,
        #[Autowire('%env(LETRECO_AUTH_PASSWORD)%')] string $authPassword,
        #[Autowire('%env(LETRECO_AUTH_DOMAIN)%')] string $authDomain,
        #[Autowire('%env(LETRECO_ACTING_AS)%')] string $actingAs,
    )
    {
        parent::__construct($em);

        $this->setupClient($httpClient, [
            'base_uri' => $baseUrl,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-OTC-Auth-Ident' => base64_encode($authIdent),
                'X-OTC-Auth-Password' => base64_encode($authPassword),
                'X-OTC-Auth-Domain' => base64_encode($authDomain),
                'X-OTC-ActingAs-Domain' => base64_encode($actingAs),
            ]
        ]);
    }

    /**
     * @return string
     */
    protected function getApiName(): string
    {
        return 'LetReco';
    }

    /**
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getVersion(): array
    {
        return $this->call('GET', '/kwp-user/api/v2/version');
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
    public function postRecord(array $payload): array
    {
        return $this->call('POST', '/kwp-user/api/v2/records', [
            'json' => $payload,
        ]);
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
        return $this->call(
            'GET',
            sprintf('/kwp-user/api/v2/records/recordByReference/%s', $reference),
        );
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
    public function getProofInfo(string $reference): array
    {
        return $this->call(
            'GET',
            sprintf('/kwp-user/api/v2/proof/info/%s', $reference),
        );
    }

    /**
     * @param string $type
     * @param string $reference
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function downloadProof(string $type, string $reference): array
    {
        return $this->call(
            'GET',
            sprintf('/kwp-user/api/v2/proof/%s/%s', $type, $reference),
            [],
            true
        );
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
    public function createUser(array $payload): array
    {
        return $this->call('POST', '/kwp-user/api/v2/users/createUser', [
            'json' => $payload,
        ]);
    }
}