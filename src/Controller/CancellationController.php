<?php

namespace App\Controller;

use App\Entity\Cancellation;
use App\Entity\Insurer;
use App\Entity\User;
use App\Enum\CancellationStatus;
use App\Enum\ContractType;
use App\Enum\Profile;
use App\Form\CancellationContractDetailType;
use App\Form\CancellationContractType;
use App\Form\CancellationInsurerType;
use App\Form\CancellationSubscriberType;
use App\Service\CancellationService;
use App\Service\CreditService;
use App\Service\LetReco\LetRecoService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[Route('/cancellation')]
#[IsGranted('ROLE_USER')]
final class CancellationController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CreditService          $creditService,
        private readonly CancellationService    $cancellationService,
        private readonly LetRecoService         $letRecoService,
    )
    {
    }

    #[Route('/subscriber/{id?}', name: 'app_cancellation_subscriber')]
    public function subscriber(Request $request, ?Cancellation $cancellation = null): Response
    {
        /**
         * @var User $user
         */
        $user = $this->getUser();

        $cancellation = $cancellation ?? new Cancellation();

        $form = $this->createForm(CancellationSubscriberType::class, $cancellation, [
            'is_pro_user' => (method_exists($user, 'getProfile') && $user->getProfile() === Profile::PRO),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $cancellation->setUser($user);
            $this->em->persist($cancellation);
            $this->em->flush();

            return $this->redirectToRoute('app_cancellation_insurer', ['id' => $cancellation->getId()]);
        }

        return $this->render('cancellation/subscriber.html.twig', [
            'form' => $form->createView(),
            'cancellation' => $cancellation,
        ]);
    }

    #[Route('/insurer/{id}', name: 'app_cancellation_insurer')]
    public function insurer(Request $request, Cancellation $cancellation): Response
    {
        $form = $this->createForm(CancellationInsurerType::class, $cancellation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $insurer = $this->em->getRepository(Insurer::class)->find($form->get('insurer')->getData());

            $cancellation->setInsurer($insurer);
            $this->em->flush();

            return $this->redirectToRoute('app_cancellation_contract_type', ['id' => $cancellation->getId()]);
        }

        return $this->render('cancellation/insurer.html.twig', [
            'form' => $form->createView(),
            'cancellation' => $cancellation,
        ]);
    }

    #[Route('/insurer-list', name: 'app_cancellation_insurer_list', methods: ['GET'])]
    public function apiInsurers(Request $request): JsonResponse
    {
        $q = $request->query->get('q', '');

        $insurers = $this->em->getRepository(Insurer::class)
            ->createQueryBuilder('i')
            ->where('i.name LIKE :q')
            ->andWhere('i.active = true')
            ->setParameter('q', '%' . $q . '%')
            ->setMaxResults(15)
            ->getQuery()
            ->getArrayResult();

        return $this->json(
            array_map(
                fn($a) => [
                    'id' => $a['id'],
                    'name' => $a['name']
                ],
                $insurers
            )
        );
    }

    #[Route('/contract-type/{id}', name: 'app_cancellation_contract_type')]
    public function contractType(Request $request, Cancellation $cancellation): Response
    {
        $form = $this->createForm(CancellationContractType::class, $cancellation);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            return $this->redirectToRoute('app_cancellation_contract_detail', ['id' => $cancellation->getId()]);
        }

        return $this->render('cancellation/contract_type.html.twig', [
            'form' => $form->createView(),
            'cancellation' => $cancellation,
            'contract_types' => ContractType::options(),
            'current_insurer' => $cancellation->getInsurer()->getName(),
        ]);
    }

    #[Route('/contract-detail/{id}', name: 'app_cancellation_contract_detail')]
    public function contractDetail(Request $request, Cancellation $cancellation): Response
    {
        $form = $this->createForm(CancellationContractDetailType::class, $cancellation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();

            $this->cancellationService->generateLetterPdf($cancellation);

            return $this->redirectToRoute('app_cancellation_preview', ['id' => $cancellation->getId()]);
        }

        return $this->render('cancellation/contract_detail.html.twig', [
            'form' => $form->createView(),
            'cancellation' => $cancellation,
            'legal_framework' => $this->getLegalFramework($cancellation->getContractType()),
            'reasons' => $this->getReasons(),
        ]);
    }

    private function getReasons(): array
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

    private function getLegalFramework(?ContractType $contractType): array
    {
        if (!$contractType) {
            return [
                'title' => 'Cadre légal applicable',
                'text' => 'Résiliation à l\'échéance avec préavis. Nous calculons la bonne date.',
            ];
        }

        return match ($contractType) {
            ContractType::CAR,
            ContractType::HOMEOWNER,
            ContractType::TENANT,
            ContractType::MOTORCYCLE,
            ContractType::OTHER_MOTOR_VEHICLE => [
                'title' => 'Loi Hamon',
                'text' => 'Résiliable à tout moment après un an, sans frais ni justificatif.',
            ],
            ContractType::HEALTH => [
                'title' => 'Résiliation infra-annuelle',
                'text' => 'Votre complémentaire santé est résiliable à tout moment après un an depuis 2020.',
            ],
            ContractType::BORROWER => [
                'title' => 'Loi Lemoine',
                'text' => 'Changement d\'assurance emprunteur possible à tout moment depuis 2022.',
            ],
            default => [
                'title' => 'Cadre légal applicable',
                'text' => 'Résiliation à l\'échéance avec préavis. Nous calculons la bonne date.',
            ],
        };
    }

    #[Route('/preview/{id}', name: 'app_cancellation_preview')]
    public function preview(Cancellation $cancellation): Response
    {
        $subscriber = $cancellation->getSubscriber() ?? [];
        $fullName = sprintf('%s %s', $subscriber['first_name'] ?? '', $subscriber['last_name'] ?? '');

        return $this->render('cancellation/preview.html.twig', [
            'cancellation' => $cancellation,
            'subscriber' => $subscriber,
            'subscriber_fullname' => trim($fullName),
        ]);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    #[Route('/send/{id}', name: 'app_cancellation_send', methods: ['POST'])]
    public function send(Request $request, Cancellation $cancellation): JsonResponse
    {
        if (!$this->isCsrfTokenValid('cancellation_send', $request->headers->get('X-CSRF-TOKEN'))) {
            return $this->json(['error' => 'Invalid CSRF token'], Response::HTTP_FORBIDDEN);
        }

        $path = sprintf("%s/%s",
            $this->getParameter("documents_directory"),
            $cancellation->getDocument()->getId()
        );

        $response = $this->letRecoService->send([
            'id' => $cancellation->getId(),
            'subject' => "Résiliation de contrat n° {$cancellation->getContractReference()}",
            'insurance' => [
                'name' => $cancellation->getInsurer()->getName(),
                'email' => $cancellation->getInsurer()->getEmail(),
                'address' => '',
                'postalCode' => '',
                'city' => '',
                'country' => 'France',
            ],
            'files' => [
                [
                    'filename' => $cancellation->getDocument()->getName(),
                    'path' => $path
                ]
            ],
            'letRecoUserId' => $cancellation->getUser()->getLetRecoUserId(),
            'letRecoPassword' => $cancellation->getUser()->getLetRecoPassword(),
        ]);

        if(!$response['status']) {
            $cancellation->setStatus(CancellationStatus::FAILED);
            $this->em->persist($cancellation);
            $this->em->flush();
            throw new \RuntimeException('Erreur Letreco : ' . $response['result']['error']);
        }

        /**
         * @var User $user
         */
        $user = $this->getUser();
        $this->creditService->deductCredit($user);

        $cancellation->setStatus(CancellationStatus::SENDING);
        $this->em->persist($cancellation);

        $this->em->flush();

        $this->cancellationService->getProofs($cancellation);

        return $this->json([
            'redirect' => $this->generateUrl('app_home')
        ]);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    #[Route('/detail/{id}', name: 'app_cancellation_detail')]
    public function detail(Cancellation $cancellation): Response
    {
        $this->cancellationService->getProofs($cancellation);

        return $this->render('cancellation/detail.html.twig', [
            'cancellation' => $cancellation,
            'isReadOnly' => true,
            'subscriber' => $cancellation->getSubscriber(),
        ]);
    }
}
