<?php

namespace App\Support;

class QadRemarks
{
    public const MAX = 40;

    /**
     * QAD poRmks: `{draft PO}, {remarks purchasing}`, dipotong 40 karakter.
     */
    public static function compose(string $draftNumber, ?string $purchasingRemarks): string
    {
        $extra = trim((string) $purchasingRemarks);
        $remarks = $extra !== '' ? "{$draftNumber}, {$extra}" : $draftNumber;

        return mb_substr($remarks, 0, self::MAX);
    }
}
