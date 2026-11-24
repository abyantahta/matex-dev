<?php

namespace App\Services\Qad;

use App\Support\QadSoap;

class QadReceivingService
{
    public function __construct(private readonly QadSoapClient $client) {}

    /**
     * Record a receipt against a QAD PO (action SDI_eKanbanGR, operation
     * receivePurchaseOrder). $lines: [['line' => int, 'qty' => float], ...].
     *
     * Envelope shape is intentionally different from QadPurchaseOrderService
     * (maintainPurchaseOrder): this operation uses QAD's default (unprefixed)
     * xml-services namespace instead of qsvc:, and its session context has
     * extra receiver/mnemonicsRaw fields plus a different version.
     *
     * This exact flat shape (lotserialQty/site/location directly under
     * lineDetail, no <operation>, no nested <receiptDetail>, plus yn/yn1)
     * is what was manually verified end-to-end (qty actually posts in QAD)
     * for a PO created via maintainPurchaseOrder/SDI_BuatPO — the earlier
     * nested-receiptDetail shape (copied from warehouse, whose POs come
     * from a Requisition→Approval flow instead) was a confirmed silent
     * no-op for matex's directly-created POs. Don't revert to that shape.
     *
     * site/location come from the same config('qad.defaults') used for
     * podSite/podLoc at PO creation (QadPurchaseOrderService) — QAD expects
     * them to match what the PO line was created with.
     *
     * fillAll MUST stay false — verified with a real partial receipt: with
     * fillAll=true, QAD closes the PO after the FIRST receipt regardless of
     * how much of podQtyOrd was actually received, so a second (remainder)
     * receivePurchaseOrder call fails with "Purchase Order closed."
     *
     * Called once per Receiving row (not once per DN) — a partial receipt
     * just sends that increment's qty; QAD reduces its own open qty
     * incrementally per call, same as matex's own remaining-qty tracking.
     *
     * packingslip = nomor DN Matex. effDate = tanggal surat jalan (DN
     * terakhir sebelum konfirmasi keberangkatan), bukan waktu PPIC klik
     * receive — delay OHP/QAD tidak boleh menggeser tanggal posting.
     *
     * @param  array<int, array{line: int, qty: float}>  $lines
     * @return array{success: bool, payload: array, response: array}
     */
    public function receivePurchaseOrder(
        string $poNumber,
        array $lines,
        ?string $packingSlip = null,
        ?string $effectiveDate = null,
    ): array {
        $poEsc = $this->esc($poNumber);
        $psEsc = $this->esc($packingSlip ?? '');
        $effDate = $effectiveDate ?: now()->format('Y-m-d');
        $defaults = config('qad.defaults');

        $lineXml = '';
        foreach ($lines as $line) {
            $lineNo = (int) $line['line'];
            $qty = number_format((float) $line['qty'], 5, '.', '');

            $lineXml .= <<<XML

                <lineDetail>
                <line>{$lineNo}</line>
                <lotserialQty>{$qty}</lotserialQty>
                <site>{$defaults['site']}</site>
                <location>{$defaults['location']}</location>
                <multiEntry>false</multiEntry>
                </lineDetail>
                XML;
        }

        $envelope = <<<XML
            <soapenv:Envelope xmlns="urn:schemas-qad-com:xml-services" xmlns:qcom="urn:schemas-qad-com:xml-services:common" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://www.w3.org/2005/08/addressing">
            <soapenv:Header>
            <wsa:Action/>
            <wsa:To>urn:services-qad-com:SDI_eKanbanGR</wsa:To>
            <wsa:MessageID>urn:services-qad-com::SDI_eKanbanGR</wsa:MessageID>
            <wsa:ReferenceParameters>
            <qcom:suppressResponseDetail>false</qcom:suppressResponseDetail>
            </wsa:ReferenceParameters>
            <wsa:ReplyTo>
            <wsa:Address>urn:services-qad-com:</wsa:Address>
            </wsa:ReplyTo>
            </soapenv:Header>
            <soapenv:Body>
            <receivePurchaseOrder>
            <qcom:dsSessionContext>
            <qcom:ttContext>
            <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
            <qcom:propertyName>domain</qcom:propertyName>
            <qcom:propertyValue>{$this->client->domain()}</qcom:propertyValue>
            </qcom:ttContext>
            <qcom:ttContext>
            <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
            <qcom:propertyName>receiver</qcom:propertyName>
            <qcom:propertyValue>SDI_eKanbanGR</qcom:propertyValue>
            </qcom:ttContext>
            <qcom:ttContext>
            <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
            <qcom:propertyName>scopeTransaction</qcom:propertyName>
            <qcom:propertyValue>false</qcom:propertyValue>
            </qcom:ttContext>
            <qcom:ttContext>
            <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
            <qcom:propertyName>version</qcom:propertyName>
            <qcom:propertyValue>ERP3_3</qcom:propertyValue>
            </qcom:ttContext>
            <qcom:ttContext>
            <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
            <qcom:propertyName>mnemonicsRaw</qcom:propertyName>
            <qcom:propertyValue>false</qcom:propertyValue>
            </qcom:ttContext>
            <qcom:ttContext>
            <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
            <qcom:propertyName>username</qcom:propertyName>
            <qcom:propertyValue>{$this->esc($this->client->username())}</qcom:propertyValue>
            </qcom:ttContext>
            <qcom:ttContext>
            <qcom:propertyQualifier>QAD</qcom:propertyQualifier>
            <qcom:propertyName>password</qcom:propertyName>
            <qcom:propertyValue>{$this->esc($this->client->password())}</qcom:propertyValue>
            </qcom:ttContext>
            </qcom:dsSessionContext>
            <dsPurchaseOrderReceive>
            <purchaseOrderReceive>
            <ordernum>{$poEsc}</ordernum>
            <packingslip>{$psEsc}</packingslip>
            <effDate>{$effDate}</effDate>
            <fillAll>false</fillAll>
            <move>true</move>{$lineXml}
            <yn>true</yn>
            <yn1>true</yn1>
            </purchaseOrderReceive>
            </dsPurchaseOrderReceive>
            </receivePurchaseOrder>
            </soapenv:Body>
            </soapenv:Envelope>
            XML;

        $result = $this->client->sendRaw($envelope);
        $body = $result['response'] ?? '';

        $qadResult = QadSoap::result($body);
        $warnings = QadSoap::warnings($body);
        $isFault = QadSoap::isFault($body);
        $success = $result['successful'] && ! $isFault && $qadResult !== 'error';

        return [
            'success' => $success,
            'payload' => [
                'po_number' => $poNumber,
                'packingslip' => $packingSlip,
                'eff_date' => $effDate,
                'lines' => $lines,
            ],
            'response' => [
                'result' => $qadResult,
                'warnings' => $warnings,
                'error' => $success ? null : ($warnings ?: QadSoap::faultString($body) ?: (
                    $result['successful'] ? null : 'QAD tidak merespons (HTTP '.$result['status'].').'
                )),
                'raw' => $body,
            ],
        ];
    }

    private function esc(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
