<?php

namespace App\Service;

use App\Entity\Cancellation;
use App\Entity\CancellationProof;
use App\Entity\Document;
use App\Enum\CancellationProofType;
use App\Enum\CancellationStatus;
use App\Enum\DocumentType;
use App\Service\LetReco\LetRecoService;
use App\Service\PDF\PDFService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

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

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function getProofs(Cancellation $cancellation): void
    {
        // déposit proof
        $depositExist = $this->em->getRepository(CancellationProof::class)->findOneBy([
            'cancellation' => $cancellation,
            'type' => CancellationProofType::DEPOSIT
        ]);

        if(!$depositExist) {
            $depositProof = $this->letRecoService->depositProof($cancellation->getLetRecoId());

            if ($depositProof['status']) {
                $depositCancellationProof = new CancellationProof();

                $depositCancellationProof
                    ->setCancellation($cancellation)
                    ->setType(CancellationProofType::DEPOSIT)
                    ->setCreatedAt($depositCancellationProof->getCreatedAt() ?? new \DateTimeImmutable())
                ;

                $document = $depositCancellationProof->getDocument() ?? new Document();
                $document
                    ->setName("DEPOSIT_" . $cancellation->getLetRecoId() . ".pdf")
                    ->setType(DocumentType::DEPOSIT_PROOF)
                ;

                $depositCancellationProof->setDocument($document);

                $cancellation->setStatus(CancellationStatus::SENT);

                $this->em->persist($depositCancellationProof);
                $this->em->persist($document);
                $this->em->flush();

                file_put_contents($this->params->get("documents_directory") . $document->getId(), $depositProof['result']);
            }
        }


        // acceptation proof
        $acceptanceExist = $this->em->getRepository(CancellationProof::class)->findOneBy([
            'cancellation' => $cancellation,
            'type' => CancellationProofType::ACCEPTANCE
        ]);

        if(!$acceptanceExist) {
            $acceptanceProof = $this->letRecoService->acceptanceProof($cancellation->getLetRecoId(), $cancellation->getInsurer()->getEmail());

            if ($acceptanceProof['status']) {
                $acceptanceCancellationProof = new CancellationProof();

                $acceptanceCancellationProof
                    ->setCancellation($cancellation)
                    ->setType(CancellationProofType::ACCEPTANCE)
                    ->setCreatedAt($acceptanceCancellationProof->getCreatedAt() ?? new \DateTimeImmutable())
                ;

                $document = $acceptanceCancellationProof->getDocument() ?? new Document();
                $document
                    ->setName("ACCEPTANCE_" . $cancellation->getLetRecoId() . ".pdf")
                    ->setType(DocumentType::ACCEPTANCE_PROOF)
                ;

                $acceptanceCancellationProof->setDocument($document);

                $cancellation->setStatus(CancellationStatus::ACCEPTED);

                $this->em->persist($cancellation);
                $this->em->persist($acceptanceCancellationProof);
                $this->em->persist($document);
                $this->em->flush();

                file_put_contents($this->params->get("documents_directory") . $document->getId(), $acceptanceProof['result']);
            }
        }

        // refusal proof
        $refusalExist = $this->em->getRepository(CancellationProof::class)->findOneBy([
            'cancellation' => $cancellation,
            'type' => CancellationProofType::REFUSAL
        ]);

        if(!$refusalExist) {
            $refusalProof = $this->letRecoService->refusalProof($cancellation->getLetRecoId(), $cancellation->getInsurer()->getEmail());

            if ($refusalProof['status']) {
                $refusalCancellationProof = new CancellationProof();

                $refusalCancellationProof
                    ->setCancellation($cancellation)
                    ->setType(CancellationProofType::REFUSAL)
                    ->setCreatedAt($refusalCancellationProof->getCreatedAt() ?? new \DateTimeImmutable())
                ;

                $document = $refusalCancellationProof->getDocument() ?? new Document();
                $document
                    ->setName("REFUSAL_" . $cancellation->getLetRecoId() . ".pdf")
                    ->setType(DocumentType::REFUSAL_PROOF)
                ;

                $refusalCancellationProof->setDocument($document);

                $cancellation->setStatus(CancellationStatus::REFUSED);

                $this->em->persist($cancellation);
                $this->em->persist($refusalCancellationProof);
                $this->em->persist($document);
                $this->em->flush();

                file_put_contents($this->params->get("documents_directory") . $document->getId(), $refusalProof['result']);
            }
        }
    }
}