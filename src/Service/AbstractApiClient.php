<?php

namespace App\Service;

use App\Entity\ApiLog;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

abstract class AbstractApiClient
{
    protected HttpClientInterface $client;
    protected EntityManagerInterface $em;

    /**
     * @return string
     */
    abstract protected function getApiName(): string;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @param HttpClientInterface $httpClient
     * @param array $options
     * @return void
     */
    protected function setupClient(HttpClientInterface $httpClient, array $options): void
    {
        $this->client = $httpClient->withOptions($options);
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array $options
     * @param bool $asBinary
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    protected function call(string $method, string $uri, array $options = [], bool $asBinary = false): array
    {
        $startTime = microtime(true);

        try {
            $response = $this->client->request($method, $uri, $options);
            $statusCode = $response->getStatusCode();
            $isSuccess = $statusCode >= 200 && $statusCode < 300;

            if ($isSuccess) {
                $result = $asBinary ? ['binary_data' => '...'] : $response->toArray(false);
            } else {
                $result = ['error' => $response->getContent(false)];
            }

            return [
                'status' => $isSuccess,
                'result' => $result,
                'code'   => $statusCode
            ];
        } catch (Exception $e) {
            $result = ['error' => $e->getMessage()];
            $statusCode = $e->getCode() ?: 500;

            return [
                'status' => false,
                'result' => $result,
                'code'   => $statusCode
            ];
        } finally {
            $durationMs = (int) round((microtime(true) - $startTime) * 1000);

            $payload = $options['json'] ?? $options['body'] ?? null;

            if(!is_array($payload)){
                $payload = json_decode($payload, true);
            }

            $log = new ApiLog();
            $log->setApiName($this->getApiName())
                ->setMethod($method)
                ->setUri($uri)
                ->setPayload($payload)
                ->setResponse($result)
                ->setStatusCode($statusCode)
                ->setDurationMs($durationMs);

            $this->em->persist($log);
            $this->em->flush();
        }
    }
}