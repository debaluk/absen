<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

class ExcelExport
{
    public static function download(
        string $filename,
        string $sheet,
        string $title,
        ?object $company,
        string $month,
        string $department,
        string $location,
        array $headers,
        iterable $rows,
        array $widths = [],
    ): StreamedResponse {
        $escape = static fn ($value): string => htmlspecialchars((string) ($value ?? ''), ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $columnCount = count($headers);
        $mergeAcross = max(0, $columnCount - 1);
        $merged = static function ($value, string $style) use ($escape, $mergeAcross): string {
            return '<Row><Cell ss:StyleID="' . $style . '" ss:MergeAcross="' . $mergeAcross . '"><Data ss:Type="String">' . $escape($value) . '</Data></Cell></Row>';
        };

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<?mso-application progid="Excel.Sheet"?>'
            . '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">'
            . '<Styles>'
            . '<Style ss:ID="Default" ss:Name="Normal"><Alignment ss:Vertical="Center"/><Font ss:FontName="Arial" ss:Size="10"/></Style>'
            . '<Style ss:ID="Company"><Alignment ss:Horizontal="Center"/><Font ss:FontName="Arial" ss:Size="14" ss:Bold="1"/></Style>'
            . '<Style ss:ID="CompanyInfo"><Alignment ss:Horizontal="Center" ss:WrapText="1"/><Font ss:FontName="Arial" ss:Size="9"/></Style>'
            . '<Style ss:ID="Title"><Alignment ss:Horizontal="Center"/><Font ss:FontName="Arial" ss:Size="12" ss:Bold="1"/><Interior ss:Color="#D9EAF7" ss:Pattern="Solid"/></Style>'
            . '<Style ss:ID="Meta"><Font ss:FontName="Arial" ss:Size="10"/></Style>'
            . '<Style ss:ID="Header"><Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/><Font ss:FontName="Arial" ss:Size="10" ss:Bold="1"/><Interior ss:Color="#D9E1F2" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/></Borders></Style>'
            . '<Style ss:ID="Cell"><Alignment ss:Vertical="Center" ss:WrapText="1"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/></Borders></Style>'
            . '</Styles><Worksheet ss:Name="' . $escape($sheet) . '"><Table>';

        if ($widths !== []) {
            foreach ($widths as $width) {
                $xml .= '<Column ss:Width="' . max(30, (int) $width) . '"/>';
            }
        }

        $xml .= $merged($company->name ?? config('app.name'), 'Company');
        $companyInfo = collect([
            $company->address ?? null,
            $company->phone ?? null,
            $company->email ?? null,
            $company->website ?? null,
        ])->filter()->implode(' | ');
        if ($companyInfo !== '') {
            $xml .= $merged($companyInfo, 'CompanyInfo');
        }

        $xml .= $merged($title, 'Title')
            . $merged('Bulan : ' . $month, 'Meta')
            . $merged('Departemen : ' . $department, 'Meta')
            . $merged('Lokasi : ' . $location, 'Meta')
            . '<Row/>'
            . '<Row>';
        foreach ($headers as $header) {
            $xml .= '<Cell ss:StyleID="Header"><Data ss:Type="String">' . $escape($header) . '</Data></Cell>';
        }
        $xml .= '</Row>';

        foreach ($rows as $row) {
            $xml .= '<Row>';
            foreach ($row as $value) {
                $xml .= '<Cell ss:StyleID="Cell"><Data ss:Type="String">' . $escape($value) . '</Data></Cell>';
            }
            $xml .= '</Row>';
        }

        $xml .= '</Table><WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel"><FreezePanes/><FrozenNoSplit/><SplitHorizontal>7</SplitHorizontal><TopRowBottomPane>7</TopRowBottomPane><ActivePane>2</ActivePane></WorksheetOptions></Worksheet></Workbook>';

        return response()->streamDownload(
            static fn () => print($xml),
            $filename,
            ['Content-Type' => 'application/vnd.ms-excel; charset=UTF-8'],
        );
    }
}
