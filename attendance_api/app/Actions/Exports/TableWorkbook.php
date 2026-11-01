<?php

namespace App\Actions\Exports;

use App\Exceptions\ApiException;
use ZipArchive;

/** Minimal OOXML table export: text is always inlineStr, never interpreted as formulas. */
final class TableWorkbook
{
    public function write(array $sheets): string
    {
        if (! class_exists(ZipArchive::class)) {
            throw new ApiException('SERVICE_UNAVAILABLE', 'Excel export requires PHP zip extension.', 503);
        }
        $path = tempnam(sys_get_temp_dir(), 'atd-xlsx-');
        $zip = new ZipArchive;
        try {
            if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('Cannot create export.');
            }
            $types = '';
            $links = '';
            $tabs = '';
            foreach ($sheets as $index => $sheet) {
                $number = $index + 1;
                $tabs .= '<sheet name="'.$this->xml($sheet['name']).'" sheetId="'.$number.'" r:id="rId'.$number.'"/>';
                $links .= '<Relationship Id="rId'.$number.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$number.'.xml"/>';
                $types .= '<Override PartName="/xl/worksheets/sheet'.$number.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
                $rows = '';
                foreach ($sheet['rows'] as $r => $row) {
                    $cells = '';
                    foreach (array_values($row) as $c => $value) {
                        $ref = $this->column($c + 1).($r + 1);
                        $style = $r === 0 ? ' s="1"' : '';
                        $cells .= is_int($value) || is_float($value)
                            ? '<c r="'.$ref.'"'.$style.'><v>'.$value.'</v></c>'
                            : '<c r="'.$ref.'" t="inlineStr"'.$style.'><is><t xml:space="preserve">'.$this->xml((string) $value).'</t></is></c>';
                    }
                    $rows .= '<row r="'.($r + 1).'"'.($r === 0 ? ' ht="36" customHeight="1"' : '').'>'.$cells.'</row>';
                }
                $last = $this->column(max(1, count($sheet['rows'][0] ?? []))).max(1, count($sheet['rows']));
                $zip->addFromString('xl/worksheets/sheet'.$number.'.xml', '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetViews><sheetView workbookViewId="0"><pane xSplit="6" ySplit="1" topLeftCell="G2" activePane="bottomRight" state="frozen"/></sheetView></sheetViews><cols><col min="1" max="1" width="8" customWidth="1"/><col min="2" max="16384" width="22" customWidth="1"/></cols><sheetData>'.$rows.'</sheetData><autoFilter ref="A1:'.$last.'"/></worksheet>');
            }
            $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'.$types.'</Types>');
            $zip->addFromString('_rels/.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
            $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>'.$tabs.'</sheets></workbook>');
            $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'.$links.'<Relationship Id="styles" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
            $zip->addFromString('xl/styles.xml', '<?xml version="1.0"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><color rgb="FFFFFFFF"/><sz val="11"/><name val="Calibri"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF2455DB"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="1"><border/></borders><cellStyleXfs count="1"><xf/></cellStyleXfs><cellXfs count="2"><xf fontId="0" fillId="0" borderId="0" xfId="0"/><xf fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment wrapText="1" vertical="center"/></xf></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>');
            $zip->close();

            return $path;
        } catch (\Throwable $error) {
            @unlink($path);
            throw $error;
        }
    }

    private function xml(string $value): string
    {
        return htmlspecialchars(preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $value) ?? '', ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function column(int $number): string
    {
        $label = '';
        while ($number > 0) {
            $number--;
            $label = chr(65 + $number % 26).$label;
            $number = intdiv($number, 26);
        }

        return $label;
    }
}
