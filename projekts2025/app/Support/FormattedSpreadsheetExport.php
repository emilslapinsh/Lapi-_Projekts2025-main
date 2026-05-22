<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

// Formatēts tabulas eksports Excel atvēršanai
// Ģenerē HTML ar stiliem, ko Excel atver kā izlīdzinātu tabulu
final class FormattedSpreadsheetExport
{
    /**
     * @param  array<int, array{0: string, 1: string}>  $meta
     * @param  list<string>  $headers
     * @param  list<list<string|int|float|null>>  $rows
     * @param  list<'left'|'right'|'center'>|null  $alignments
     */
    public static function download(
        string $filename,
        string $sheetTitle,
        array $meta,
        array $headers,
        array $rows,
        ?array $alignments = null,
    ): StreamedResponse {
        $html = self::buildHtml($sheetTitle, $meta, $headers, $rows, $alignments);

        return response()->streamDownload(function () use ($html) {
            echo "\xEF\xBB\xBF".$html;
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }

    /**
     * @param  array<int, array{0: string, 1: string}>  $meta
     * @param  list<string>  $headers
     * @param  list<list<string|int|float|null>>  $rows
     * @param  list<'left'|'right'|'center'>|null  $alignments
     */
    private static function buildHtml(
        string $sheetTitle,
        array $meta,
        array $headers,
        array $rows,
        ?array $alignments,
    ): string {
        $colCount = count($headers);
        $alignments = $alignments ?? array_fill(0, $colCount, 'left');

        $colgroup = self::colgroup($colCount);
        $metaRows = self::metaTable($meta);
        $headerRow = self::headerRow($headers, $alignments);
        $bodyRows = self::bodyRows($rows, $alignments);

        $title = self::e($sheetTitle);

        return <<<HTML
<!DOCTYPE html>
<html lang="lv">
<head>
<meta charset="UTF-8">
<style>
  body { font-family: Calibri, Arial, sans-serif; font-size: 11pt; color: #18181b; }
  h1 { font-size: 16pt; margin: 0 0 12px; color: #18181b; }
  table.meta { margin-bottom: 16px; border: none; }
  table.meta td { padding: 3px 16px 3px 0; border: none; vertical-align: top; }
  table.meta td.label { font-weight: 600; color: #52525b; white-space: nowrap; }
  table.data { border-collapse: collapse; width: 100%; table-layout: fixed; }
  table.data th {
    background: #27272a;
    color: #fafafa;
    font-weight: 700;
    padding: 10px 12px;
    border: 1px solid #3f3f46;
    text-align: left;
    white-space: nowrap;
  }
  table.data td {
    padding: 8px 12px;
    border: 1px solid #d4d4d8;
    vertical-align: top;
    word-wrap: break-word;
  }
  table.data tbody tr:nth-child(even) td { background: #f4f4f5; }
  table.data tbody tr:hover td { background: #e4e4e7; }
  .align-right { text-align: right; }
  .align-center { text-align: center; }
</style>
</head>
<body>
<h1>{$title}</h1>
{$metaRows}
<table class="data">
{$colgroup}
<thead>
{$headerRow}
</thead>
<tbody>
{$bodyRows}
</tbody>
</table>
</body>
</html>
HTML;
    }

    private static function colgroup(int $colCount): string
    {
        $widths = match (true) {
            $colCount <= 4 => ['140px', '200px', '120px', '320px'],
            $colCount <= 6 => ['110px', '120px', '110px', '110px', '280px', '140px'],
            default => ['100px', '100px', '90px', '100px', '90px', '120px', '90px', '160px', '220px'],
        };

        $cols = '';
        for ($i = 0; $i < $colCount; $i++) {
            $w = $widths[$i] ?? '120px';
            $cols .= '<col style="width: '.$w.';">';
        }

        return "<colgroup>\n{$cols}</colgroup>";
    }

    /** @param  array<int, array{0: string, 1: string}>  $meta */
    private static function metaTable(array $meta): string
    {
        if ($meta === []) {
            return '';
        }

        $rows = '';
        foreach ($meta as [$label, $value]) {
            $rows .= '<tr>'
                .'<td class="label">'.self::e($label).'</td>'
                .'<td>'.self::e($value).'</td>'
                .'</tr>';
        }

        return "<table class=\"meta\">\n{$rows}\n</table>";
    }

    /** @param  list<string>  $headers */
    /** @param  list<'left'|'right'|'center'>  $alignments */
    private static function headerRow(array $headers, array $alignments): string
    {
        $cells = '';
        foreach ($headers as $i => $header) {
            $cells .= self::th($header, $alignments[$i] ?? 'left');
        }

        return "<tr>\n{$cells}</tr>";
    }

    /** @param  list<list<string|int|float|null>>  $rows */
    /** @param  list<'left'|'right'|'center'>  $alignments */
    private static function bodyRows(array $rows, array $alignments): string
    {
        if ($rows === []) {
            $cols = count($alignments);

            return '<tr><td colspan="'.$cols.'" style="text-align:center;color:#71717a;">Nav ierakstu</td></tr>';
        }

        $html = '';
        foreach ($rows as $row) {
            $html .= "<tr>\n";
            foreach ($row as $i => $cell) {
                $html .= self::td(self::cellValue($cell), $alignments[$i] ?? 'left');
            }
            $html .= "</tr>\n";
        }

        return $html;
    }

    private static function th(string $text, string $align): string
    {
        return '<th'.self::alignClass($align).'>'.self::e($text).'</th>';
    }

    private static function td(string $text, string $align): string
    {
        return '<td'.self::alignClass($align).'>'.self::e($text).'</td>';
    }

    private static function alignClass(string $align): string
    {
        return match ($align) {
            'right' => ' class="align-right"',
            'center' => ' class="align-center"',
            default => '',
        };
    }

    private static function cellValue(string|int|float|null $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return (string) $value;
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
