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
        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents) ?? $contents;
        $xml = self::loadSpreadsheetXml($contents);
        if ($xml === false) {
            return collect();
        }

        $rows = [];
        $headers = [];

        foreach ($xml->xpath('//*[local-name()="Row"]') ?: [] as $row) {
            $values = [];
            $col = 0;
            foreach ($row->xpath('./*[local-name()="Cell"]') ?: [] as $cell) {
                $explicit = self::spreadsheetIndex($cell);
                if ($explicit > 0) {
                    $col = $explicit - 1;
                }
                $data = $cell->xpath('./*[local-name()="Data"]');
                $values[$col] = trim((string) (($data[0] ?? null) ?: $cell));
                $col++;
            }

            if ($values === []) {
                continue;
            }

            ksort($values);
            $values = array_values($values);

            if ($headers === []) {
                $headers = array_map(fn ($h) => strtolower(trim($h)), $values);

                continue;
            }

            $assoc = [];
            foreach ($headers as $i => $header) {
                if ($header === '') {
                    continue;
                }
                $assoc[$header] = $values[$i] ?? '';
            }
            $rows[] = $assoc;
        }

        return collect($rows);
    }

    private static function loadSpreadsheetXml(string $contents): \SimpleXMLElement|false
    {
        $xml = self::silenceSimpleXml($contents);
        if ($xml !== false) {
            return $xml;
        }

        // Excel sering menulis atribut ss:ID / ss:Type. Jangan hapus xmlns
        // tanpa juga menghapus prefix atribut, atau simplexml meledak 500.
        return self::silenceSimpleXml(self::stripSpreadsheetNamespaces($contents));
    }

    private static function silenceSimpleXml(string $contents): \SimpleXMLElement|false
    {
        $previous = libxml_use_internal_errors(true);
        try {
            $xml = simplexml_load_string($contents);

            return $xml === false ? false : $xml;
        } catch (\Throwable) {
            return false;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    private static function stripSpreadsheetNamespaces(string $contents): string
    {
        $contents = preg_replace('/xmlns(:[a-z0-9]+)?="[^"]*"/i', '', $contents) ?? $contents;
        $contents = preg_replace('/\s+[a-zA-Z_][\w.-]*:[A-Za-z_][\w.-]*="[^"]*"/', '', $contents) ?? $contents;

        return preg_replace_callback(
            '/<\/?[a-zA-Z_][\w.-]*:/',
            static fn (array $m): string => str_starts_with($m[0], '</') ? '</' : '<',
            $contents
        ) ?? $contents;
    }

    private static function spreadsheetIndex(\SimpleXMLElement $cell): int
    {
        $candidates = [
            $cell->attributes(),
            $cell->attributes('ss', true),
            $cell->attributes('urn:schemas-microsoft-com:office:spreadsheet'),
        ];

        foreach ($candidates as $attrs) {
            if ($attrs === null) {
                continue;
            }
            $index = (int) ($attrs['Index'] ?? 0);
            if ($index > 0) {
                return $index;
            }
        }

        return (int) ($cell['Index'] ?? 0);
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
