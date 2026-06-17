<?php
namespace App\Controller;

use App\Service\CreditService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Parcours LRAR generique pour les professionnels multi-metiers.
 */
#[Route('/lrar')]
#[IsGranted('ROLE_USER')]
class LrarController extends AbstractController
{
    private const USE_CASES = [
        'mise_en_demeure'  => ['label' => 'Mise en demeure',              'objet' => 'Mise en demeure',                  'professions' => ['avocat','syndic','adb','expert','autre']],
        'notification'     => ['label' => 'Notification / Sommation',     'objet' => 'Notification',                     'professions' => ['avocat','syndic','adb']],
        'convocation_ag'   => ['label' => "Convocation AG",              'objet' => "Convocation a l'assemblee generale",'professions' => ['syndic']],
        'appel_fonds'      => ['label' => 'Appel de fonds',              'objet' => 'Appel de fonds',                   'professions' => ['syndic']],
        'conge_preavais'   => ['label' => 'Conge / Preavis',             'objet' => 'Conge',                            'professions' => ['adb']],
        'resiliation'      => ['label' => 'Resiliation de contrat',       'objet' => 'Resiliation',                      'professions' => ['courtier','avocat','syndic','adb','expert','autre']],
        'courrier_libre'   => ['label' => 'Courrier libre',               'objet' => '',                                 'professions' => ['courtier','avocat','syndic','adb','expert','autre']],
    ];

    public function __construct(private readonly CreditService $creditService) {}

    #[Route('/compose/{useCase}', name: 'app_lrar_compose')]
    public function compose(string $useCase): Response
    {
        $case = self::USE_CASES[$useCase] ?? self::USE_CASES['courrier_libre'];

        return $this->render('lrar/compose.html.twig', [
            'use_case'       => $useCase,
            'use_case_label' => $case['label'],
            'objet_default'  => $case['objet'],
            'message_default'=> $this->getDefaultMessage($useCase),
        ]);
    }

    #[Route('/send', name: 'app_lrar_send', methods: ['POST'])]
    public function send(Request $request): Response
    {
        $user = $this->getUser();

        if (!$this->creditService->deductCredit($user)) {
            $this->addFlash('error', 'Solde de credits insuffisant. Achetez un pack pour continuer.');
            return $this->redirectToRoute('app_packs');
        }

        // TODO: integrer signature Universign + envoi Letreco
        // $this->universign->createSignatureTransaction(...)
        // $this->letreco->sendLre(...)

        $this->addFlash('success', 'Votre LRE a ete envoyee avec succes.');
        return $this->redirectToRoute('app_lrar_success');
    }

    #[Route('/success', name: 'app_lrar_success')]
    public function success(): Response
    {
        return $this->render('lrar/success.html.twig');
    }

    /**
     * Retourne les cas d'usage disponibles pour une profession.
     */
    public static function getUseCasesForProfession(string $profession): array
    {
        return array_filter(
            self::USE_CASES,
            fn($case) => in_array($profession, $case['professions']) || in_array('autre', $case['professions'])
        );
    }

    private function getDefaultMessage(string $useCase): string
    {
        return match($useCase) {
            'mise_en_demeure' => "Madame, Monsieur,\n\nPar la presente, nous vous mettons en demeure de...\n\nA defaut, nous nous reservons le droit d'engager toutes procedures utiles.\n\nVeuillez agreer...",
            'convocation_ag'  => "Madame, Monsieur,\n\nNous avons l'honneur de vous convoquer a l'assemblee generale de la copropriete qui se tiendra le [DATE] a [LIEU].\n\nL'ordre du jour est le suivant :\n1. ...\n\nVeuillez agreer...",
            'appel_fonds'     => "Madame, Monsieur,\n\nNous vous informons d'un appel de fonds d'un montant de [MONTANT] € correspondant a [MOTIF].\n\nCe montant est a regler avant le [DATE].\n\nVeuillez agreer...",
            'conge_preavais'  => "Madame, Monsieur,\n\nNous vous notifions par la presente le conge du bail portant sur le logement situe au [ADRESSE], a compter du [DATE].\n\nVeuillez agreer...",
            default           => "Madame, Monsieur,\n\n\n\nVeuillez agreer, Madame, Monsieur, l'expression de nos salutations distinguees.",
        };
    }
}
