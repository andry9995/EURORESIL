<?php

namespace App\Controller;

use App\Entity\Invoice;
use App\Entity\User;
use App\Service\CreditService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/packs')]
class PackController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CreditService          $creditService,
    )
    {
    }

    #[Route('', name: 'app_packs')]
    public function index(): Response
    {
        $user = $this->getUser();
        $invoices = $this->em->getRepository(Invoice::class)->findBy(
            ['user' => $user],
            ['issuedAt' => 'DESC']
        );

        return $this->render('packs/index.html.twig', [
            'user' => $user,
            'invoices' => $invoices,
            'pricingGrid' => $this->creditService->getPricingGrid(),
        ]);
    }

    #[Route('/pricing', name: 'app_packs_pricing')]
    public function pricing(Request $request): Response
    {
        $qty = max(1, min(1000, (int)$request->query->get('qty', 50)));
        return $this->json($this->creditService->calculateAmount($qty));
    }

    #[Route('/purchase', name: 'app_packs_purchase', methods: ['POST'])]
    public function purchase(Request $request): Response
    {
        /**
         * @var User $user
         */
        $user = $this->getUser();

        $qty = (int)$request->request->get('qty', 0);
        if ($qty < 1 || $qty > 10000) {
            $this->addFlash('error', 'Quantite invalide.');
            return $this->redirectToRoute('app_packs');
        }

        $response = $this->creditService->purchasePack($user, $qty);

        if (!$response['status']) {
            $this->addFlash('error', "Une erreur est survenue lors de l'achat du crédit.");

            return $this->redirectToRoute('app_packs');
        }

        return $this->render('packs/sherlock_redirect.html.twig', [
            'sherlock' => $response['result']
        ]);
    }

    #[Route('/continue-purchase/{id}', name: 'app_packs_continue_purchase', methods: ['GET'])]
    public function continuePurchase(Request $request, Invoice $invoice): Response
    {
        $response = $this->creditService->purchasePack($invoice->getUser(), $invoice->getPack()->getQtyPurchased(), $invoice);

        if (!$response['status']) {
            $this->addFlash('error', "Une erreur est survenue lors de l'achat du crédit.");

            return $this->redirectToRoute('app_packs');
        }

        return $this->render('packs/sherlock_redirect.html.twig', [
            'sherlock' => $response['result']
        ]);
    }

    #[Route('/pay', name: 'app_pack_pay')]
    public function pay(Request $request): Response
    {
        $response = $this->creditService->afterPay($request);

        if ($response['success']) {
            $this->addFlash('success', "Paiement effectué.");
        } else {
            $this->addFlash('error', "Une erreur est survenue lors du paiement.");
        }

        return $this->redirectToRoute('app_packs');
    }

    #[Route('/invoice/{id}/pdf', name: 'app_invoice_pdf')]
    public function invoicePdf(string $id): Response
    {
        $invoice = $this->em->find(Invoice::class, $id);
        if (!$invoice || $invoice->getUser()->getId() !== $this->getUser()->getId()) {
            throw $this->createNotFoundException();
        }

        // TODO: générer / servir le PDF de facture depuis S3
        // Pour la démonstration, redirection
        $this->addFlash('info', 'Generation PDF en cours...');
        return $this->redirectToRoute('app_packs');
    }
}
