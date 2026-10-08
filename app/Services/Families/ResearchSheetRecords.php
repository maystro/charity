<?php

namespace App\Services\Families;

/**
 * Very small hard-coded XLSX reader for the single sheet used in this import.
 * Returns an array of rows with keys mapped to the spreadsheet columns.
 */
class ResearchSheetRecords
{
    protected string $path;

    public function __construct(string $path = '')
    {
        $this->path = $path ?: base_path('docs/نموذج تسجيل ابحاث.xlsx');
    }

    /**
     * @return array<int, array<string,mixed>>
     */
    public function all(): array
    {
        if (! file_exists($this->path)) {
            return [];
        }

        $zip = new \ZipArchive;
        if ($zip->open($this->path) !== true) {
            return [];
        }

        $shared = [];
        if (($xml = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
            $doc = new \DOMDocument;
            $doc->loadXML($xml);
            foreach ($doc->getElementsByTagName('si') as $si) {
                $text = '';
                foreach ($si->getElementsByTagName('t') as $t) {
                    $text .= $t->textContent;
                }
                $shared[] = $text;
            }
        }

        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheet === false) {
            return [];
        }

        $doc = new \DOMDocument;
        $doc->loadXML($sheet);
        $rows = $doc->getElementsByTagName('row');

        $out = [];
        $header = [];
        foreach ($rows as $i => $row) {
            $cells = $row->getElementsByTagName('c');
            $rowData = [];
            foreach ($cells as $cell) {
                $ref = $cell->getAttribute('r'); // e.g. A1
                $col = preg_replace('/\d+$/', '', $ref);
                $t = $cell->getAttribute('t');
                $v = '';
                foreach ($cell->childNodes as $ch) {
                    if ($ch->nodeName === 'v') {
                        $v = $ch->textContent;
                    }
                    if ($ch->nodeName === 'is') {
                        foreach ($ch->getElementsByTagName('t') as $tt) {
                            $v .= $tt->textContent;
                        }
                    }
                }
                if ($t === 's' && $v !== '') {
                    $v = $shared[(int) $v] ?? $v;
                }

                $rowData[$col] = $v;
            }

            // First row -> header mapping based on known columns A..AB
            if ($i === 0) {
                $header = $this->mapHeader($rowData);

                continue;
            }

            // Map to associative using header
            $mapped = [];
            foreach ($header as $col => $key) {
                $mapped[$key] = $rowData[$col] ?? '';
            }
            // include raw text for later heuristics
            $mapped['_raw'] = $rowData;
            $out[] = $mapped;
        }

        return $out;
    }

    /**
     * Map excel column letters to semantic keys based on the known header row.
     *
     * @param  array<string,string>  $rowData
     * @return array<string,string>
     */
    protected function mapHeader(array $rowData): array
    {
        // We expect the header to be in Arabic; map by position if needed.
        $cols = array_keys($rowData);
        $keys = [
            'A' => 'code',
            'B' => 'wife_name',
            'C' => 'wife_national_id',
            'D' => 'husband_name',
            'E' => 'husband_national_id',
            'F' => 'phone',
            'G' => 'community',
            'H' => 'address',
            'I' => 'child1_name',
            'J' => 'child1_national_id',
            'K' => 'child2_name',
            'L' => 'child2_national_id',
            'M' => 'child3_name',
            'N' => 'child3_national_id',
            'O' => 'child4_name',
            'P' => 'child4_national_id',
            'Q' => 'child5_name',
            'R' => 'child5_national_id',
            'S' => 'children_summary',
            'T' => 'total_income',
            'U' => 'details',
            'V' => 'burdens',
            'W' => 'requested_aids',
            'X' => 'submitted_at',
            'Y' => 'was_discussed',
            'Z' => 'discussion_date',
            'AA' => 'committee_decision',
            'AB' => 'visit_result',
        ];

        // Fallback: if header columns differ, try to keep mapping by available cols.
        $mapped = [];
        foreach ($cols as $index => $col) {
            $mapped[$col] = $keys[$col] ?? 'col_'.$col;
        }

        return $mapped;
    }
}
