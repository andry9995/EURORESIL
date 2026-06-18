<?php
namespace App\Service;

use App\Entity\Invoice;
use App\Entity\Pack;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Gestion des packs de crédits LRE et de la facturation.
 */
class CreditService
{
    /** Paliers tarifaires dégressifs (quantité min => prix unitaire HT) */
    public const TIERS = [
        600 => 1.95,
        300 => 2.40,
        100 => 2.90,
        25  => 3.90,
        1   => 4.90,
    ];

    public const TVA_RATE = 0.20;

    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    /**
     * Calcule le prix unitaire HT pour une quantité donnée.
     */
    public function getUnitPrice(int $qty): float
    {
        foreach (self::TIERS as $minQty => $price) {
            if ($qty >= $minQty) return $price;
        }
        return 4.90;
    }

    /**
     * Calcule le total HT et TTC pour une quantité.
     */
    public function calculateAmount(int $qty): array
    {
        $unitPrice = $this->getUnitPrice($qty);
        $amountHt  = round($qty * $unitPrice, 2);
        $tva       = round($amountHt * self::TVA_RATE, 2);
        $amountTtc = round($amountHt + $tva, 2);

        return [
            'qty'        => $qty,
            'unit_price' => $unitPrice,
            'amount_ht'  => $amountHt,
            'tva'        => $tva,
            'amount_ttc' => $amountTtc,
        ];
    }

    /**
     * Achète un pack et crédite le compte utilisateur.
     * (À appeler après confirmation Stripe)
     */
    public function purchasePack(User $user, int $qty): Invoice
    {
        $amounts = $this->calculateAmount($qty);

        // Créer le pack
        $pack = new Pack();
        $pack->setUser($user);
        $pack->setQtyPurchased($qty);
        $pack->setUnitPrice((string) $amounts['unit_price']);
        $pack->setAmountTtc((string) $amounts['amount_ttc']);

        // Créditer l'utilisateur
        $user->addCredits($qty);

        // Générer la facture
        $invoice = new Invoice();
        $invoice->setUser($user);
        $invoice->setPack($pack);
        $invoice->setInvoiceNumber($this->generateInvoiceNumber());
        $invoice->setAmountHt((string) $amounts['amount_ht']);
        $invoice->setAmountTtc((string) $amounts['amount_ttc']);

        $this->em->persist($pack);
        $this->em->persist($invoice);
        $this->em->flush();

        return $invoice;
    }

    /**
     * Débite un crédit pour un envoi LRE.
     */
    public function deductCredit(User $user): bool
    {
        if ($user->getCredits() < 1) return false;
        $user->deductCredit();
        $this->em->flush();
        return true;
    }

    private function generateInvoiceNumber(): string
    {
        $year  = date('Y');
        $count = $this->em->getRepository(Invoice::class)->count([]) + 1;
        return sprintf('EUR-%s-%04d', $year, $count);
    }

    /**
     * Retourne la grille tarifaire pour l'affichage.
     */
    public function getPricingGrid(): array
    {
        return [
            ['quantity' => 10,  'price' => 4.90, 'label' => 'Découverte'],
            ['quantity' => 50,  'price' => 3.90, 'label' => 'Avantage'],
            ['quantity' => 200, 'price' => 2.90, 'label' => 'Pro'],
            ['quantity' => 500, 'price' => 2.40, 'label' => 'Volume'],
        ];
    }
}
