<?php
namespace App\Controller;

use App\Entity\Resiliation;
use App\Service\LetrecoService;
use App\Service\UniversignService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/webhook')]
class WebhookController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
    ) {}

    /** Webhook Universign — signature terminee */
    #[Route('/universign', name: 'webhook_universign', methods: ['POST'])]
    public function universign(Request $request, UniversignService $universign): Response
    {
        $payload   = $request->getContent();
        $signature = $request->headers->get('X-Universign-Signature', '');

        if (!$universign->verifyWebhookSignature($payload, $signature)) {
            $this->logger->warning('Webhook Universign : signature invalide');
            return new Response('Unauthorized', 401);
        }

        $data          = json_decode($payload, true);
        $txId          = $data['transaction_id'] ?? null;
        $status        = $data['status'] ?? null;
        $resilId       = $data['metadata']['resiliation_id'] ?? null;

        if ($resilId && $txId && $status === 'COMPLETED') {
            $resiliation = $this->em->find(Resiliation::class, $resilId);
            if ($resiliation) {
                // La signature est terminee : mettre en attente d'envoi Letreco
                // (l'envoi Letreco peut etre declenche automatiquement ici)
                $resiliation->setStatus(Resiliation::STATUS_SENDING);
                $this->em->flush();
                $this->logger->info('Universign COMPLETED', ['resiliation_id' => $resilId]);
            }
        }

        return new Response('OK', 200);
    }

    /** Webhook Letreco — nouveau statut / preuve */
    #[Route('/letreco', name: 'webhook_letreco', methods: ['POST'])]
    public function letreco(Request $request, LetrecoService $letreco): Response
    {
        $payload   = $request->getContent();
        $signature = $request->headers->get('X-Letreco-Signature', '');

        if (!$letreco->verifyWebhookSignature($payload, $signature)) {
            $this->logger->warning('Webhook Letreco : signature invalide');
            return new Response('Unauthorized', 401);
        }

        $data    = json_decode($payload, true);
        $resilId = $data['metadata']['resiliation_id'] ?? null;

        if ($resilId) {
            $resiliation = $this->em->find(Resiliation::class, $resilId);
            if ($resiliation) {
                $letreco->handleWebhook($data, $resiliation);
                $this->logger->info('Letreco webhook traite', ['type' => $data['type'] ?? '?', 'resiliation_id' => $resilId]);
            }
        }

        return new Response('OK', 200);
    }
}
