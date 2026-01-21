<?php

namespace App\Services\Cv;

use DOMDocument;
use DOMXPath;
use RuntimeException;
use ZipArchive;

class WordBookmarkFiller
{
    private string $wNs = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    public function fill(string $templatePath, array $replacements, string $outputPath): void
    {
        if (!is_file($templatePath)) {
            throw new RuntimeException("Template no encontrado: {$templatePath}");
        }

        $outDir = dirname($outputPath);
        if (!is_dir($outDir) && !mkdir($outDir, 0775, true) && !is_dir($outDir)) {
            throw new RuntimeException("No se pudo crear directorio: {$outDir}");
        }

        if (!copy($templatePath, $outputPath)) {
            throw new RuntimeException("No se pudo copiar template a: {$outputPath}");
        }

        $zip = new ZipArchive();
        if ($zip->open($outputPath) !== true) {
            throw new RuntimeException("No se pudo abrir docm/docx: {$outputPath}");
        }

        $xml = $zip->getFromName('word/document.xml');
        if ($xml === false) {
            $zip->close();
            throw new RuntimeException("No se encontró word/document.xml dentro del template.");
        }

        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;

        if (!$dom->loadXML($xml)) {
            $zip->close();
            throw new RuntimeException("No se pudo parsear document.xml");
        }

        $xp = new DOMXPath($dom);
        $xp->registerNamespace('w', $this->wNs);

        foreach ($replacements as $bookmark => $value) {
            $value = $value === null ? '' : (string) $value;
            $value = str_replace(["\r\n", "\n", "\r"], ' ', $value);

            $query = '//w:bookmarkStart[@w:name=' . $this->xpathLiteral((string)$bookmark) . ']';
            $nodes = $xp->query($query);

            if (!$nodes || $nodes->length === 0) {
                continue; // si el bookmark no existe, lo ignoramos
            }

            foreach ($nodes as $bmStart) {
                if ($value === '') continue;
                $this->insertRunAfterBookmark($dom, $bmStart, $value);
            }
        }

        $zip->addFromString('word/document.xml', $dom->saveXML());
        $zip->close();
    }

    private function insertRunAfterBookmark(DOMDocument $dom, \DOMNode $bookmarkStart, string $text): void
    {
        $run = $dom->createElementNS($this->wNs, 'w:r');

        // Copiar estilo del run previo (si existe) para que se vea igual
        $prev = $bookmarkStart->previousSibling;
        while ($prev && !($prev instanceof \DOMElement && $prev->localName === 'r')) {
            $prev = $prev->previousSibling;
        }
        if ($prev instanceof \DOMElement) {
            foreach ($prev->childNodes as $c) {
                if ($c instanceof \DOMElement && $c->localName === 'rPr') {
                    $run->appendChild($c->cloneNode(true));
                    break;
                }
            }
        }

        $t = $dom->createElementNS($this->wNs, 'w:t');
        $t->setAttributeNS('http://www.w3.org/XML/1998/namespace', 'xml:space', 'preserve');
        $t->appendChild($dom->createTextNode($text));

        $run->appendChild($t);

        $parent = $bookmarkStart->parentNode;
        $next = $bookmarkStart->nextSibling;
        if ($parent) $parent->insertBefore($run, $next);
    }

    private function xpathLiteral(string $value): string
    {
        if (strpos($value, "'") === false) return "'" . $value . "'";
        if (strpos($value, '"') === false) return '"' . $value . '"';

        $parts = explode("'", $value);
        $out = "concat(";
        foreach ($parts as $i => $p) {
            if ($i > 0) $out .= ", \"'\", ";
            $out .= "'" . $p . "'";
        }
        $out .= ")";
        return $out;
    }
}
