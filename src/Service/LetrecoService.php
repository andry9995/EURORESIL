<?php
namespace App\Service;

use App\Entity\Resiliation;
use App\Entity\ResilPreuve;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Intégration API Letreco (LRE qualifiée eIDAS).
 * Envoie la lettre recommandée électronique et gère les statuts/preuves.
 */
class LetrecoService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly EntityManagerInterface $em,
        private readonly string $apiUrl,
        private readonly string $apiKey,
        private readonly string $webhookSecret,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Envoie la lettre en LRE qualifiée via Letreco.
     * Retourne l'ID du pli et le numéro de suivi.
     */
    public function sendLre(
        Resiliation $resiliation,
        string $letterPdfContent,
        string $mandatPdfContent,
        string $recipientEmail,
        string $recipientName,
    ): array {
        $payload = [
            'destinataire' => [
                'email' => $recipientEmail,
                'nom'   => $recipientName,
            ],
            'objet'     => 'Résiliation de contrat d\'assurance - Réf. ' . $resiliation->getContratRef(),
            'type'      => 'lre_qualifiee', // LRE qualifiée eIDAS obligatoire
            'documents' => [
                [
                    'nom'     => 'lettre_resiliation.pdf',
                    'contenu' => base64_encode($letterPdfContent),
                ],
                [
                    'nom'     => 'mandat_resiliation.pdf',
                    'contenu' => base64_encode($mandatPdfContent),
                ],
            ],
            'webhook_url' => $_ENV['APP_URL'] . '/webhook/letreco',
            'metadata'    => ['resiliation_id' => $resiliation->getId()],
        ];

        try {
            $response = $this->httpClient->request('POST', $this->apiUrl . '/envois', [
                'headers' => [
                    'X-Api-Key'    => $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);

            return $response->toArray();
        } catch (\Throwable $e) {
            $this->logger->error('Letreco API error', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Erreur Letreco : ' . $e->getMessage());
        }
    }

    /**
     * Traite un webhook Letreco (nouveau statut/preuve).
     * Appelé depuis WebhookController.
     */
    public function handleWebhook(array $payload, Resiliation $resiliation): void
    {
        $eventType = $payload['type'] ?? null;

        $typeMap = [
            'depot'      => ResilPreuve::TYPE_DEPOT,
            'reception'  => ResilPreuve::TYPE_RECEPTION,
            'retrait'    => ResilPreuve::TYPE_RETRAIT,
            'negligence' => ResilPreuve::TYPE_NEGLIGENCE,
        ];

        if (!isset($typeMap[$eventType])) {
            $this->logger->warning('Letreco webhook type inconnu', ['type' => $eventType]);
            return;
        }

        // Mettre à jour le statut de la résiliation
        $newStatus = match($typeMap[$eventType]) {
            ResilPreuve::TYPE_DEPOT      => Resiliation::STATUS_SENT,
            ResilPreuve::TYPE_RECEPTION,
            ResilPreuve::TYPE_NEGLIGENCE => Resiliation::STATUS_RECEIVED,
            default                       => $resiliation->getStatus(),
        };
        $resiliation->setStatus($newStatus);

        // Créer l'enregistrement de preuve
        $preuve = new ResilPreuve();
        $preuve->setType($typeMap[$eventType]);
        $preuve->setEventDate(new \DateTimeImmutable($payload['date'] ?? 'now'));
        $preuve->setRawPayload($payload);

        // Stocker le PDF de preuve (à implémenter : télécharger depuis l'URL Letreco)
        if (!empty($payload['proof_url'])) {
            // TODO: télécharger et stocker sur S3
            // $preuve->setProofPdfPath($this->downloadAndStore($payload['proof_url']));
        }

        $resiliation->addPreuve($preuve);
        $this->em->flush();
    }

    /**
     * Vérifie la signature HMAC d'un webhook Letreco.
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $expected = hash_hmac('sha256', $payload, $this->webhookSecret);
        return hash_equals($expected, $signature);
    }
}
