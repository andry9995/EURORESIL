<?php

namespace App\Service;

use App\Entity\Cancellation;
use App\Entity\Document;
use App\Enum\DocumentType;
use App\Service\LetReco\LetRecoService;
use App\Service\PDF\PDFService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\File;

readonly class CancellationService
{
    public function __construct(
        private PDFService             $PDFService,
        private EntityManagerInterface $em,
        private ParameterBagInterface  $params,
        private LetRecoService         $letRecoService,
    )
    {
    }

    /**
     * @param Cancellation $cancellation
     * @return array
     */
    public function generateLetterPdf(Cancellation $cancellation): array
    {
        $data = [
            'subscriber' => [
                'company' => $cancellation->getSubscriber()['company'],
                'siret' => $cancellation->getSubscriber()['siret'],
                'fullName' => $cancellation->getSubscriberFullName(),
                'address' => $cancellation->getSubscriber()['address'],
                'clientReference' => $cancellation->getSubscriber()['client_reference'],
            ],
            'insurer' => [
                'name' => $cancellation->getInsurer()->getName(),
            ],
            'date' => date('d/m/Y'),
            'contract' => [
                'type' => $cancellation->getContractType() ? $cancellation->getContractType()->label() : '',
                'reference' => $cancellation->getContractReference(),
                'reason' => $cancellation->getReason(),
            ],
        ];

        $templateName = 'letter';
        $outputFileName = 'LETTRE_' . $cancellation->getId();
        $pdf = $this->PDFService->generate($data, $templateName, $outputFileName);

        $document = $cancellation->getDocument() ?? new Document();
        $document->setName($pdf['nom']);
        $document->setType(DocumentType::LETTER);
        $this->em->persist($document);

        $cancellation->setDocument($document);
        $this->em->persist($cancellation);

        $this->em->flush();

        $documentFilePath = $this->params->get("documents_directory");

        $file = new File($pdf['path']);
        $file->move($documentFilePath, $document->getId());

        return $pdf;
    }
}