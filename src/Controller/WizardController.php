<?php
namespace App\Controller;

use App\Entity\RefAssureur;
use App\Entity\Resiliation;
use App\Service\CreditService;
use App\Service\LetrecoService;
use App\Service\UniversignService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/wizard')]
#[IsGranted('ROLE_USER')]
class WizardController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CreditService $creditService,
    ) {}

    /** Étape 1 — Souscripteur */
    #[Route('/souscripteur/{id?}', name: 'app_wizard_souscripteur')]
    public function souscripteur(Request $request, ?string $id = null): Response
    {
        $resiliation = $id
            ? $this->em->find(Resiliation::class, $id) ?? new Resiliation()
            : new Resiliation();

        if ($request->isMethod('POST')) {
            $souscripteur = [
                'categorie'     => $request->request->get('categorie', 'particulier'),
                'civilite'      => $request->request->get('civilite'),
                'nom'           => $request->request->get('nom'),
                'nom_naissance' => $request->request->get('nom_naissance'),
                'prenom'        => $request->request->get('prenom'),
                'societe'       => $request->request->get('societe'),
                'siret'         => $request->request->get('siret'),
                'adresse'       => $request->request->get('adresse'),
                'complement'    => $request->request->get('complement'),
                'cp'            => $request->request->get('cp'),
                'ville'         => $request->request->get('ville'),
                'pays'          => $request->request->get('pays', 'France'),
                'email'         => $request->request->get('email'),
                'ref_client'    => $request->request->get('ref_client'),
            ];

            $resiliation->setUser($this->getUser());
            $resiliation->setSouscripteur($souscripteur);
            $this->em->persist($resiliation);
            $this->em->flush();

            return $this->redirectToRoute('app_wizard_assureur', ['id' => $resiliation->getId()]);
        }

        return $this->render('wizard/souscripteur.html.twig', [
            'resiliation' => $resiliation,
        ]);
    }

    /** Étape 2 — Assureur */
    #[Route('/assureur/{id}', name: 'app_wizard_assureur')]
    public function assureur(Request $request, string $id): Response
    {
        $resiliation = $this->getResiliationOrRedirect($id);
        if ($resiliation instanceof Response) return $resiliation;

        if ($request->isMethod('POST')) {
            $resiliation->setAssureurNom($request->request->get('assureur'));
            $this->em->flush();
            return $this->redirectToRoute('app_wizard_contrat_type', ['id' => $id]);
        }

        return $this->render('wizard/assureur.html.twig', [
            'resiliation' => $resiliation,
        ]);
    }

    /** Recherche autocomplete assureurs (AJAX) */
    #[Route('/api/assureurs', name: 'app_wizard_api_assureurs')]
    public function apiAssureurs(Request $request): JsonResponse
    {
        $q = $request->query->get('q', '');
        $assureurs = $this->em->getRepository(RefAssureur::class)
            ->createQueryBuilder('a')
            ->where('a.name LIKE :q')
            ->andWhere('a.active = true')
            ->setParameter('q', '%' . $q . '%')
            ->setMaxResults(15)
            ->getQuery()
            ->getArrayResult();

        return $this->json(array_map(fn($a) => $a['name'], $assureurs));
    }

    /** Étape 3a — Type de contrat */
    #[Route('/contrat-type/{id}', name: 'app_wizard_contrat_type')]
    public function contratType(Request $request, string $id): Response
    {
        $resiliation = $this->getResiliationOrRedirect($id);
        if ($resiliation instanceof Response) return $resiliation;

        if ($request->isMethod('POST')) {
            $resiliation->setTypeContrat($request->request->get('type_contrat'));
            $this->em->flush();
            return $this->redirectToRoute('app_wizard_contrat_detail', ['id' => $id]);
        }

        return $this->render('wizard/contrat_type.html.twig', [
            'resiliation'  => $resiliation,
            'types_contrat' => $this->getTypesContrat(),
        ]);
    }

    /** Étape 3b — Détails du contrat + motif */
    #[Route('/contrat-detail/{id}', name: 'app_wizard_contrat_detail')]
    public function contratDetail(Request $request, string $id): Response
    {
        $resiliation = $this->getResiliationOrRedirect($id);
        if ($resiliation instanceof Response) return $resiliation;

        if ($request->isMethod('POST')) {
            $resiliation->setContratRef($request->request->get('contrat_ref'));
            $resiliation->setMotif($request->request->get('motif'));
            $this->em->flush();
            return $this->redirectToRoute('app_wizard_recap', ['id' => $id]);
        }

        return $this->render('wizard/contrat_detail.html.twig', [
            'resiliation' => $resiliation,
            'cadre_legal' => $this->getCadreLegal($resiliation->getTypeContrat()),
            'motifs'      => $this->getMotifs(),
        ]);
    }

    /** Étape 4 — Récapitulatif + envoi */
    #[Route('/recap/{id}', name: 'app_wizard_recap')]
    public function recap(Request $request, string $id): Response
    {
        $resiliation = $this->getResiliationOrRedirect($id);
        if ($resiliation instanceof Response) return $resiliation;

        if ($request->isMethod('POST') && $request->request->get('action') === 'save_extra') {
            $resiliation->setExtraText($request->request->get('extra_text'));
            $this->em->flush();
            return $this->json(['success' => true]);
        }

        return $this->render('wizard/recap_envoi.html.twig', [
            'resiliation' => $resiliation,
        ]);
    }

    /** Déclenchement signature Universign */
    #[Route('/sign/{id}', name: 'app_wizard_sign', methods: ['POST'])]
    public function sign(string $id, UniversignService $universign): JsonResponse
    {
        $resiliation = $this->getResiliationOrRedirect($id);
        if ($resiliation instanceof Response) return $this->json(['error' => 'Not found'], 404);

        // TODO: générer les PDFs (lettre + mandat) via PdfGeneratorService
        // $letterPdf = $pdfGenerator->generateLetter($resiliation);
        // $mandatPdf = $pdfGenerator->generateMandat($resiliation);

        // Placeholder pour la démonstration
        $letterPdf = 'PDF_LETTRE_PLACEHOLDER';
        $mandatPdf = 'PDF_MANDAT_PLACEHOLDER';

        $user   = $this->getUser();
        $result = $universign->createSignatureTransaction(
            $resiliation,
            $letterPdf,
            $mandatPdf,
            $user->getEmail(),
            '' // téléphone à récupérer du profil
        );

        $resiliation->setStatus(Resiliation::STATUS_SIGNING);
        $resiliation->setUniversignTxId($result['transaction_id'] ?? null);
        $this->em->flush();

        return $this->json(['signature_url' => $result['signature_url'] ?? '#']);
    }

    /** Callback après signature Universign */
    #[Route('/sign-callback/{id}', name: 'app_wizard_sign_callback')]
    public function signCallback(string $id): Response
    {
        $resiliation = $this->em->find(Resiliation::class, $id);
        return $this->render('wizard/sign_callback.html.twig', ['resiliation' => $resiliation]);
    }

    /** Envoi en LRE */
    #[Route('/send/{id}', name: 'app_wizard_send', methods: ['POST'])]
    public function send(string $id, LetrecoService $letreco): JsonResponse
    {
        $resiliation = $this->getResiliationOrRedirect($id);
        if ($resiliation instanceof Response) return $this->json(['error' => 'Not found'], 404);

        if (!$this->creditService->deductCredit($this->getUser())) {
            return $this->json(['error' => 'Solde de credits insuffisant.'], 402);
        }

        // TODO: récupérer les PDFs signés depuis Universign / S3
        $letterPdf     = 'PDF_SIGNE_PLACEHOLDER';
        $mandatPdf     = 'PDF_MANDAT_SIGNE_PLACEHOLDER';
        $recipientEmail = 'service.resiliation@' . strtolower(str_replace(' ', '', $resiliation->getAssureurNom())) . '.fr';

        $result = $letreco->sendLre($resiliation, $letterPdf, $mandatPdf, $recipientEmail, $resiliation->getAssureurNom());

        $resiliation->setStatus(Resiliation::STATUS_SENDING);
        $resiliation->setLetrecoEnvoiId($result['envoi_id'] ?? null);
        $resiliation->setLetrecoPliNumber($result['numero_pli'] ?? null);
        $this->em->flush();

        return $this->json(['success' => true, 'redirect' => $this->generateUrl('app_wizard_success', ['id' => $id])]);
    }

    #[Route('/success/{id}', name: 'app_wizard_success')]
    public function success(string $id): Response
    {
        $resiliation = $this->em->find(Resiliation::class, $id);
        return $this->render('wizard/success.html.twig', ['resiliation' => $resiliation]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function getResiliationOrRedirect(string $id): Resiliation|Response
    {
        $resiliation = $this->em->find(Resiliation::class, $id);
        if (!$resiliation || $resiliation->getUser()->getId() !== $this->getUser()->getId()) {
            $this->addFlash('error', 'Resiliation introuvable.');
            return $this->redirectToRoute('app_home');
        }
        return $resiliation;
    }

    private function getTypesContrat(): array
    {
        return [
            ['key' => 'auto',      'label' => 'Assurance auto',                        'icon' => 'car'],
            ['key' => 'hab-prop',  'label' => 'Assurance habitation (proprietaire)',    'icon' => 'home'],
            ['key' => 'hab-loc',   'label' => 'Assurance habitation (locataire)',       'icon' => 'home'],
            ['key' => 'sante',     'label' => 'Complementaire sante',                  'icon' => 'heart'],
            ['key' => 'moto',      'label' => 'Assurance moto',                        'icon' => 'bike'],
            ['key' => 'vtm',       'label' => 'Autre vehicule terrestre a moteur',     'icon' => 'truck'],
            ['key' => 'famille',   'label' => 'Protection familiale',                  'icon' => 'users'],
            ['key' => 'mrp',       'label' => 'Multirisque professionnelle',           'icon' => 'briefcase'],
            ['key' => 'prevoyance','label' => 'Prevoyance',                            'icon' => 'shield'],
            ['key' => 'pj',        'label' => 'Protection juridique',                  'icon' => 'gavel'],
            ['key' => 'emprunteur','label' => 'Assurance emprunteur',                  'icon' => 'bank'],
            ['key' => 'animal',    'label' => 'Assurance animaux',                     'icon' => 'paw'],
            ['key' => 'autre',     'label' => 'Autre contrat',                         'icon' => 'file'],
        ];
    }

    private function getMotifs(): array
    {
        return [
            'Resiliation infra-annuelle',
            'Echeance principale',
            'Loi Chatel (art. L.113-15-1 du Code des assurances)',
            'Changement de domicile',
            'Retractation',
            'Changement de profession',
            'Retraite professionnelle',
            'Cessation d activite professionnelle',
            'Augmentation de votre tarif',
            'Deces de l assure',
            'Changement de domicile professionnel',
            'Motif personnalise',
        ];
    }

    private function getCadreLegal(?string $typeContrat): array
    {
        return match($typeContrat) {
            'auto','hab-prop','hab-loc','moto','vtm' => [
                'titre' => 'Loi Hamon',
                'texte' => 'Resiliable a tout moment apres un an, sans frais ni justificatif.',
            ],
            'sante' => [
                'titre' => 'Resiliation infra-annuelle',
                'texte' => 'Votre complementaire sante est resiliable a tout moment apres un an depuis 2020.',
            ],
            'emprunteur' => [
                'titre' => 'Loi Lemoine',
                'texte' => 'Changement d assurance emprunteur possible a tout moment depuis 2022.',
            ],
            default => [
                'titre' => 'Cadre legal applicable',
                'texte' => 'Resiliation a l echeance avec preavis. Nous calculons la bonne date.',
            ],
        };
    }
}
