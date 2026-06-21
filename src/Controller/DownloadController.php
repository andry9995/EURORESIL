<?php

namespace App\Controller;

use App\Entity\Document;
use App\Entity\Invoice;
use App\Service\CreditService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

final class DownloadController extends AbstractController
{
    public function __construct(
        private readonly CreditService $creditService,
        private readonly entityManagerInterface $em,
    )
    {

    }
    #[Route('/download/{id}', name: 'app_download')]
    public function index(Document $document): Response
    {
        $invoice = $this->em->getRepository(Invoice::class)->findOneBy(['document' => $document]);
        $this->creditService->generatePDF($invoice);

        $path = sprintf("%s/%s",
            $this->getParameter("documents_directory"),
            $document->getId()
        );

        if (!file_exists($path)){
            throw $this->createNotFoundException();
        }

        $file = new File($path);

        return $this->file($file, $document->getName(), ResponseHeaderBag::DISPOSITION_INLINE);
    }
}
