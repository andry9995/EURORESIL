<?php
namespace App\Controller;

use App\Entity\Resiliation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dashboard')]
#[IsGranted('ROLE_USER')]
class DashboardController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    #[Route('', name: 'app_dashboard')]
    public function index(): Response
    {
        $user         = $this->getUser();
        $resiliations = $this->em->getRepository(Resiliation::class)->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC'],
            50
        );

        $stats = [
            'total'    => count($resiliations),
            'received' => count(array_filter($resiliations, fn($r) => $r->getStatus() === 'received')),
            'sending'  => count(array_filter($resiliations, fn($r) => in_array($r->getStatus(), ['sending','sent']))),
            'credits'  => $user->getCredits(),
        ];

        return $this->render('dashboard/index.html.twig', [
            'resiliations' => $resiliations,
            'stats'        => $stats,
            'user'         => $user,
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
