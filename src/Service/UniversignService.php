<?php
namespace App\Service;

use App\Entity\Resiliation;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Intégration API Universign (signature électronique eIDAS).
 * Docs : https://developers.universign.com
 */
class UniversignService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $apiUrl,
        private readonly string $apiKey,
        private readonly string $webhookSecret,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Crée une transaction de signature pour la lettre + le mandat.
     * Retourne l'URL de signature à afficher à l'utilisateur.
     */
    public function createSignatureTransaction(
        Resiliation $resiliation,
        string $letterPdfContent,
        string $mandatPdfContent,
        string $signerEmail,
        string $signerPhone,
    ): array {
        $payload = [
            'documents' => [
                [
                    'name'    => 'lettre_resiliation.pdf',
                    'content' => base64_encode($letterPdfContent),
                    'type'    => 'pdf',
                ],
                [
                    'name'    => 'mandat_resiliation.pdf',
                    'content' => base64_encode($mandatPdfContent),
                    'type'    => 'pdf',
                ],
            ],
            'signers' => [
                [
                    'email'           => $signerEmail,
                    'phone'           => $signerPhone,
                    'signature_level' => 'aes', // AES avec OTP SMS
                ],
            ],
            'webhook_url'  => $_ENV['APP_URL'] . '/webhook/universign',
            'redirect_url' => $_ENV['APP_URL'] . '/wizard/sign-callback/' . $resiliation->getId(),
            'metadata'     => ['resiliation_id' => $resiliation->getId()],
        ];

        try {
            $response = $this->httpClient->request('POST', $this->apiUrl . '/transactions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => $payload,
            ]);

            return $response->toArray();
        } catch (\Throwable $e) {
            $this->logger->error('Universign API error', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Erreur Universign : ' . $e->getMessage());
        }
    }

    /**
     * Vérifie la signature HMAC d'un webhook Universign.
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $expected = hash_hmac('sha256', $payload, $this->webhookSecret);
        return hash_equals($expected, $signature);
    }

    /**
     * Télécharge les documents signés depuis Universign.
     */
    public function downloadSignedDocuments(string $transactionId): array
    {
        $response = $this->httpClient->request('GET', $this->apiUrl . '/transactions/' . $transactionId . '/documents', [
            'headers' => ['Authorization' => 'Bearer ' . $this->apiKey],
        ]);
        return $response->toArray();
    }
}
