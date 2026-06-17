<?php
namespace App\Controller;

use App\Entity\Invoice;
use App\Service\CreditService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/packs')]
#[IsGranted('ROLE_USER')]
class PackController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CreditService $creditService,
    ) {}

    #[Route('', name: 'app_packs')]
    public function index(): Response
    {
        $user     = $this->getUser();
        $invoices = $this->em->getRepository(Invoice::class)->findBy(
            ['user' => $user],
            ['issuedAt' => 'DESC']
        );

        return $this->render('packs/index.html.twig', [
            'user'         => $user,
            'invoices'     => $invoices,
            'pricing_grid' => $this->creditService->getPricingGrid(),
        ]);
    }

    #[Route('/pricing', name: 'app_packs_pricing')]
    public function pricing(Request $request): Response
    {
        $qty = max(1, min(1000, (int) $request->query->get('qty', 50)));
        return $this->json($this->creditService->calculateAmount($qty));
    }

    #[Route('/purchase', name: 'app_packs_purchase', methods: ['POST'])]
    public function purchase(Request $request): Response
    {
        $qty = (int) $request->request->get('qty', 0);
        if ($qty < 1 || $qty > 10000) {
            $this->addFlash('error', 'Quantite invalide.');
            return $this->redirectToRoute('app_packs');
        }

        // TODO: déclencher Stripe et attendre la confirmation webhook
        // Pour le moment, achat direct (à remplacer par le flux Stripe en production)
        $invoice = $this->creditService->purchasePack($this->getUser(), $qty);

        $this->addFlash('success', sprintf('%d recommandes ajoutes. Facture %s generee.', $qty, $invoice->getInvoiceNumber()));
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
