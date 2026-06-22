<?php

namespace App\Service;

use App\Entity\Document;
use App\Entity\Invoice;
use App\Entity\Pack;
use App\Entity\User;
use App\Enum\DocumentType;
use App\Service\PDF\OpenTBSService;
use App\Service\PDF\PDFHelper;
use App\Service\PDF\PDFService;
use App\Service\SherlockPay\SherlockPayService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;

class CreditService
{
    public const TVA_RATE = 0.20;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SherlockPayService     $sherlockPayService,
        private readonly PDFService             $PDFService,
        private readonly ParameterBagInterface  $params,
    )
    {
    }

    /**
     * @return array
     */
    public function getPricingGrid(): array
    {
        return [
            ['min' => 10, 'max' => 49, 'price' => 4.90, 'label' => 'Découverte'],
            ['min' => 50, 'max' => 199, 'price' => 3.90, 'label' => 'Avantage'],
            ['min' => 200, 'max' => 499, 'price' => 2.90, 'label' => 'Pro'],
            ['min' => 500, 'max' => null, 'price' => 1.95, 'label' => 'Volume'],
        ];
    }

    /**
     * @param int $qty
     * @return float
     */
    public function getUnitPrice(int $qty): float
    {
        foreach ($this->getPricingGrid() as $tier) {
            if ($qty >= $tier['min'] && ($tier['max'] === null || $qty <= $tier['max'])) {
                return $tier['price'];
            }
        }

        return 4.90;
    }

    /**
     * @param int $qty
     * @return array
     */
    public function calculateAmount(int $qty): array
    {
        $unitPrice = $this->getUnitPrice($qty);
        $amountHt = round($qty * $unitPrice, 2);
        $tva = round($amountHt * self::TVA_RATE, 2);

        return [
            'qty' => $qty,
            'unit_price' => $unitPrice,
            'amount_ht' => $amountHt,
            'tva' => $tva,
            'amount_ttc' => round($amountHt + $tva, 2),
        ];
    }

    /**
     * @param User $user
     * @param int $qty
     * @param Invoice|null $invoice
     * @return array
     */
    public function purchasePack(User $user, int $qty, ?Invoice $invoice = null): array
    {
        $amounts = $this->calculateAmount($qty);
        $orderId = $this->generateInvoiceNumber();

        $sherlockResponse = $this->sherlockPayService->send([
            'amount' => $amounts['amount_ttc'],
            'orderId' => $orderId
        ]);

        if ($sherlockResponse['status']) {
            $pack = $invoice ? $invoice->getPack() : new Pack();
            $pack->setUser($user);
            $pack->setQtyPurchased($qty);
            $pack->setUnitPrice((string)$amounts['unit_price']);
            $pack->setAmountTtc((string)$amounts['amount_ttc']);
            $this->em->persist($pack);

            $invoice = $invoice ?? new Invoice();
            $invoice->setUser($user);
            $invoice->setPack($pack);
            $invoice->setInvoiceNumber($orderId);
            $invoice->setAmountHt((string)$amounts['amount_ht']);
            $invoice->setAmountTtc((string)$amounts['amount_ttc']);

            $this->em->persist($invoice);
            $this->em->flush();
        }

        return $sherlockResponse;
    }

    /**
     * @param User $user
     * @return bool
     */
    public function deductCredit(User $user): bool
    {
        if ($user->getCredits() < 1) return false;
        $user->deductCredit();
        $this->em->flush();
        return true;
    }

    /**
     * @return string
     */
    private function generateInvoiceNumber(): string
    {
        $year = date('Y');
        $count = $this->em->getRepository(Invoice::class)->count([]) + 1;
        return sprintf('EUR-%s-%04d', $year, $count);
    }

    public function afterPay(Request $request): array
    {
        $response = $this->sherlockPayService->paymentResponse($request);

        if ($response['success']) {
            $invoiceNumber = $response['data']['orderId'];
            $invoice = $this->em->getRepository(Invoice::class)->findOneBy(['invoiceNumber' => $invoiceNumber]);
            if ($invoice) {
                $invoice->setPaidAt(new \DateTimeImmutable());
                $this->em->persist($invoice);

                $user = $invoice->getUser();
                $user->addCredits($invoice->getPack()->getQtyPurchased());
                $this->em->persist($user);

                $this->em->flush();

                $this->generatePDF($invoice);
            }
        }

        return $response;
    }

    /**
     * @param Invoice $invoice
     * @return array
     */
    public function generatePDF(Invoice $invoice): array
    {
        $data = [
            'numFacture' => $invoice->getInvoiceNumber(),
            'dateFacture' => $invoice->getIssuedAt()->format('d/m/Y'),
            'nom' => $invoice->getUser()->getRaisonSociale(),
            'telephone' => "0000000000",
            'email' => $invoice->getUser()->getEmail(),
            'objet' => "Achat de packs recommandés",
            'description' => "Packs recommandés",
            'quantite' => $invoice->getPack()->getQtyPurchased(),
            'montant' => $invoice->getPack()->getUnitPrice(),
            'totalHt' => $invoice->getAmountHt(),
            'tauxTva' => $invoice->getTvaRate(),
            'tva' => ((float)$invoice->getAmountHt() * (float)$invoice->getTvaRate()) / 100,
            'totalTtc' => $invoice->getAmountTtc(),
            'siret' => $invoice->getUser()->getSiret(),
            'datePaiement' => $invoice->getPaidAt() ? $invoice->getPaidAt()->format('d/m/Y') : '-',
        ];

        $templateName = 'facture';
        $outputFileName = 'FACTURE_' . $invoice->getInvoiceNumber();

        $pdf = $this->PDFService->generate($data, $templateName, $outputFileName);

        $document = $invoice->getDocument() ?? new Document();
        $document->setName($pdf['nom']);
        $document->setType(DocumentType::INVOICE);
        $this->em->persist($document);

        $invoice->setDocument($document);
        $this->em->persist($invoice);

        $this->em->flush();

        $documentFilePath = $this->params->get("documents_directory");

        $file = new File($pdf['path']);
        $file->move($documentFilePath, $document->getId());

        return $pdf;
    }
}
