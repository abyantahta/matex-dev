<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Excel SpreadsheetML (.xls) tanpa library tambahan — Excel membuka file ini
 * langsung, dan import membaca CSV atau XML yang sama.
 */
class SpreadsheetBook
{
    /**
     * @param  array<int, string>  $headers
     * @param  iterable<int, array<int, string|int|float|null>>  $rows
     */
    public static function download(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        $xml = self::toXml($headers, $rows);

        return response()->streamDownload(function () use ($xml) {
            echo $xml;
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }

    /**
     * @return Collection<int, array<string, string>>
     */
    public static function read(UploadedFile $file): Collection
    {
        $contents = (string) file_get_contents($file->getRealPath());
        $extension = strtolower($file->getClientOriginalExtension());

        if (str_contains($contents, '<Workbook') || in_array($extension, ['xls', 'xml'], true)) {
            return self::fromXml($contents);
        }

        return self::fromCsv($contents);
    }

    /**
     * @param  array<int, string>  $headers
     * @param  iterable<int, array<int, string|int|float|null>>  $rows
     */
    public static function toXml(array $headers, iterable $rows): string
    {
        $cells = static function (array $values): string {
            $xml = '';
            foreach ($values as $value) {
                $text = htmlspecialchars((string) ($value ?? ''), ENT_XML1 | ENT_QUOTES, 'UTF-8');
                $xml .= '<Cell><Data ss:Type="String">'.$text.'</Data></Cell>';
            }

            return $xml;
        };

        $body = '<Row>'.$cells($headers).'</Row>';
        foreach ($rows as $row) {
            $body .= '<Row>'.$cells(array_values($row)).'</Row>';
        }

        return <<<XML
<?xml version="1.0"?>
<?mso-application progid="Excel.Sheet"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">
 <Worksheet ss:Name="Harga">
  <Table>
   {$body}
  </Table>
 </Worksheet>
</Workbook>
XML;
    }

    /**
     * @return Collection<int, array<string, string>>
     */
    private static function fromXml(string $contents): Collection
    {
        $contents = preg_replace('/xmlns(:[a-z0-9]+)?="[^"]*"/i', '', $contents) ?? $contents;
        $xml = simplexml_load_string($contents);
        if ($xml === false) {
            return collect();
        }

        $rows = [];
        $headers = [];

        foreach ($xml->xpath('//Row') ?: [] as $index => $row) {
            $values = [];
            foreach ($row->Cell ?? [] as $cell) {
                $values[] = trim((string) ($cell->Data ?? $cell));
            }

            if ($index === 0) {
                $headers = array_map(fn ($h) => strtolower(trim($h)), $values);

                continue;
            }

            $assoc = [];
            foreach ($headers as $i => $header) {
                $assoc[$header] = $values[$i] ?? '';
            }
            $rows[] = $assoc;
        }

        return collect($rows);
    }

    /**
     * @return Collection<int, array<string, string>>
     */
    private static function fromCsv(string $contents): Collection
    {
        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents) ?? $contents;
        $lines = preg_split("/\r\n|\n|\r/", $contents) ?: [];
        $headers = [];
        $rows = [];

        foreach ($lines as $index => $line) {
            if (trim($line) === '') {
                continue;
            }

            $values = str_getcsv($line);
            if ($index === 0 || $headers === []) {
                $headers = array_map(fn ($h) => strtolower(trim((string) $h)), $values);

                continue;
            }

            $assoc = [];
            foreach ($headers as $i => $header) {
                $assoc[$header] = $values[$i] ?? '';
            }
            $rows[] = $assoc;
        }

        return collect($rows);
    }
}
