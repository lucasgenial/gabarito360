<?php

namespace App\Support\Export;

/**
 * Gera uma planilha XLSX mínima e válida (OOXML SpreadsheetML) a partir de um
 * cabeçalho e linhas, sem depender da extensão `zip` (usa {@see StoredZipWriter}).
 *
 * Strings são escritas como `inlineStr` (dispensa a tabela de sharedStrings) e
 * valores numéricos como células numéricas, preservando ordenação/somas no Excel.
 */
class XlsxWriter
{
    /**
     * @param  list<string>  $header
     * @param  list<array<int, scalar|null>>  $rows
     */
    public function build(array $header, array $rows, string $sheetName = 'Planilha1'): string
    {
        $matrix = array_merge([$header], $rows);
        $sheet = $this->sheetXml($matrix);

        $zip = new StoredZipWriter;
        $zip->add('[Content_Types].xml', $this->contentTypes());
        $zip->add('_rels/.rels', $this->rootRels());
        $zip->add('xl/workbook.xml', $this->workbook($sheetName));
        $zip->add('xl/_rels/workbook.xml.rels', $this->workbookRels());
        $zip->add('xl/worksheets/sheet1.xml', $sheet);

        return $zip->finish();
    }

    /**
     * @param  list<array<int, scalar|null>>  $matrix
     */
    private function sheetXml(array $matrix): string
    {
        $rowsXml = '';

        foreach ($matrix as $rowIndex => $cells) {
            $rowNumber = $rowIndex + 1;
            $cellsXml = '';

            foreach (array_values($cells) as $colIndex => $value) {
                $ref = $this->columnLetter($colIndex).$rowNumber;
                $cellsXml .= $this->cellXml($ref, $value);
            }

            $rowsXml .= '<row r="'.$rowNumber.'">'.$cellsXml.'</row>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetData>'.$rowsXml.'</sheetData>'
            .'</worksheet>';
    }

    private function cellXml(string $ref, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '<c r="'.$ref.'"/>';
        }

        if (is_int($value) || is_float($value)) {
            return '<c r="'.$ref.'"><v>'.$value.'</v></c>';
        }

        $escaped = htmlspecialchars((string) $value, ENT_QUOTES | ENT_XML1, 'UTF-8');

        return '<c r="'.$ref.'" t="inlineStr"><is><t xml:space="preserve">'.$escaped.'</t></is></c>';
    }

    private function columnLetter(int $index): string
    {
        $letter = '';
        $index++;

        while ($index > 0) {
            $remainder = ($index - 1) % 26;
            $letter = chr(65 + $remainder).$letter;
            $index = intdiv($index - 1, 26);
        }

        return $letter;
    }

    private function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'</Types>';
    }

    private function rootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private function workbook(string $sheetName): string
    {
        $name = htmlspecialchars($sheetName, ENT_QUOTES | ENT_XML1, 'UTF-8');

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="'.$name.'" sheetId="1" r:id="rId1"/></sheets>'
            .'</workbook>';
    }

    private function workbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'</Relationships>';
    }
}
