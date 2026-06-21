<?php

namespace App\Service\PDF;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

readonly class PDFService
{
    public function __construct(
        private PDFHelper             $PDFHelper,
        private ParameterBagInterface $params,
        private OpenTBSService        $openTBSService,
    )
    {
    }

    /**
     * @param array $data
     * @param string $templateName
     * @param string $outputFileName
     * @return array
     */
    public function generate(
        array  $data,
        string $templateName,
        string $outputFileName
    ): array
    {
        $templateOdtPath = sprintf("%s%s.odt",
            $this->params->get('odt_directory'),
            $templateName
        );

        $fullPathOdt = sprintf("%s%s.odt",
            $this->params->get('tmp_directory'),
            $outputFileName
        );

        $fullPathPdf = sprintf("%s%s.pdf",
            $this->params->get('tmp_directory'),
            $outputFileName
        );

        $tbsOptions = [
            'templatePath' => $templateOdtPath,
            'renderPath' => $fullPathOdt,
            'objectRef' => [],
            'fields' => ['data' => $data],
            'varRef' => []
        ];

        $this->openTBSService->show($tbsOptions);

        $this->PDFHelper->convert($fullPathOdt);

        unlink($fullPathOdt);

        return [
            'path' => $fullPathPdf,
            'nom' => $outputFileName
        ];
    }
}