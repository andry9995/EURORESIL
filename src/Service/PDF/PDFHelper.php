<?php


namespace App\Service\PDF;


use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Service\PDF\PDFExec;

class PDFHelper
{
    public function __construct(
        private readonly ParameterBagInterface $params,
        private readonly PDFExec $pdfExec
    )
    {

    }

    public function convert($fullPathOdt): void
    {
        $this->pdfExec->run('convert', [
            $this->params->get('tmp_directory'),
            $this->params->get('tmp_directory'),
            $fullPathOdt
        ]);
    }

    public function merge($pdfsToMerge,$pathMergedPDF): void
    {
        $pdfsToMergePaths = [];
        foreach ($pdfsToMerge as $pdf){
            $pdfsToMergePaths[] = $pdf['path'];
        }

        $this->pdfExec->run('merge', [
            $pathMergedPDF,
            implode(" ",$pdfsToMergePaths)
        ]);

        foreach($pdfsToMerge as $pdf){
            if ($pdf['delete'] ?? false) unlink($pdf['path']);
        }
    }

}
