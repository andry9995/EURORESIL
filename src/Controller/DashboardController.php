<?php

namespace App\Controller;

use App\Entity\Cancellation;
use App\Entity\User;
use App\Enum\CancellationStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dashboard')]
#[IsGranted('ROLE_USER')]
class DashboardController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route('', name: 'app_dashboard')]
    public function index(): Response
    {
        /**
         * @var User $user
         */
        $user = $this->getUser();
        $cancellations = $this->em->getRepository(Cancellation::class)->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC'],
        );

        $stats = [
            'total' => count($cancellations),
            'sent' => count(array_filter($cancellations, fn($r) => $r->getStatus() === CancellationStatus::SENT)),
            'draft' => count(array_filter($cancellations, fn($r) => in_array($r->getStatus(), [CancellationStatus::DRAFT, CancellationStatus::SENDING]))),
            'credits' => $user->getCredits(),
        ];

        return $this->render('dashboard/index.html.twig', [
            'cancellations' => $cancellations,
            'stats' => $stats,
            'user' => $user,
        ]);
    }

    #[Route('/detail/{id}', name: 'app_dashboard_detail')]
    public function detail(string $id): Response
    {
        $resiliation = $this->em->find(Resiliation::class, $id);

        if (!$resiliation || $resiliation->getUser()->getId() !== $this->getUser()->getId()) {
            throw $this->createNotFoundException('Resiliation introuvable.');
        }

        return $this->render('dashboard/detail.html.twig', [
            'resiliation' => $resiliation,
        ]);
    }
}
