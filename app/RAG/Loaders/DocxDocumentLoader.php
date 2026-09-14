<?php

namespace App\RAG\Loaders;

use PhpOffice\PhpWord\Element\AbstractContainer;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\IOFactory;

class DocxDocumentLoader implements DocumentLoaderInterface
{
    public function supports(string $mimeType): bool
    {
        return $mimeType === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
    }

    public function extractText(string $filePath): string
    {
        $phpWord = IOFactory::load($filePath, 'Word2007');
        $lines = [];

        foreach ($phpWord->getSections() as $section) {
            $this->extractFromContainer($section, $lines);
        }

        return implode("\n", $lines);
    }

    
    private function extractFromContainer(AbstractContainer $container, array &$lines): void
    {
        foreach ($container->getElements() as $element) {
            if ($element instanceof Text) {
                $lines[] = $element->getText();
            } elseif ($element instanceof TextRun) {
                $lines[] = $this->extractFromTextRun($element);
            } elseif ($element instanceof Table) {
                foreach ($element->getRows() as $row) {
                    foreach ($row->getCells() as $cell) {
                        $this->extractFromContainer($cell, $lines);
                    }
                }
            } elseif ($element instanceof AbstractContainer) {
                $this->extractFromContainer($element, $lines);
            }
        }
    }

    private function extractFromTextRun(TextRun $textRun): string
    {
        $parts = [];

        foreach ($textRun->getElements() as $element) {
            if ($element instanceof Text) {
                $parts[] = $element->getText();
            }
        }

        return implode('', $parts);
    }
}
