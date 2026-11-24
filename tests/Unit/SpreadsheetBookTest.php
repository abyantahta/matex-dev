<?php

namespace Tests\Unit;

use App\Support\SpreadsheetBook;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class SpreadsheetBookTest extends TestCase
{
    public function test_it_reads_excel_rewritten_spreadsheetml_with_ss_id(): void
    {
        $xml = <<<'XML'
<?xml version="1.0"?>
<?mso-application progid="Excel.Sheet"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">
 <Worksheet ss:Name="Harga">
  <Table ss:ID="Table1" ss:ExpandedColumnCount="3" ss:ExpandedRowCount="2">
   <Row>
    <Cell><Data ss:Type="String">item_number</Data></Cell>
    <Cell><Data ss:Type="String">description</Data></Cell>
    <Cell><Data ss:Type="String">harga</Data></Cell>
   </Row>
   <Row>
    <Cell><Data ss:Type="String">MTPLT23004</Data></Cell>
    <Cell><Data ss:Type="String">coil</Data></Cell>
    <Cell><Data ss:Type="Number">13550</Data></Cell>
   </Row>
  </Table>
 </Worksheet>
</Workbook>
XML;

        $rows = SpreadsheetBook::read($this->upload('harga.xls', $xml));

        $this->assertCount(1, $rows);
        $this->assertSame('MTPLT23004', $rows[0]['item_number']);
        $this->assertSame('13550', $rows[0]['harga']);
    }

    public function test_it_reads_xml_after_xmlns_is_stripped_but_ss_attributes_remain(): void
    {
        $xml = <<<'XML'
<?xml version="1.0"?>
<Workbook>
 <Styles>
  <Style ss:ID="Default" ss:Name="Normal"/>
 </Styles>
 <Worksheet ss:Name="Harga">
  <Table ss:ID="Table1">
   <Row>
    <Cell><Data ss:Type="String">item_number</Data></Cell>
    <Cell><Data ss:Type="String">harga</Data></Cell>
   </Row>
   <Row>
    <Cell><Data ss:Type="String">MTPLT22025</Data></Cell>
    <Cell><Data ss:Type="Number">9900</Data></Cell>
   </Row>
  </Table>
 </Worksheet>
</Workbook>
XML;

        $rows = SpreadsheetBook::read($this->upload('harga.xls', $xml));

        $this->assertCount(1, $rows);
        $this->assertSame('MTPLT22025', $rows[0]['item_number']);
        $this->assertSame('9900', $rows[0]['harga']);
    }

    private function upload(string $name, string $contents): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, $contents);
    }
}
