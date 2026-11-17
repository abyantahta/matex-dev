<?php

namespace App\Notifications\Concerns;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\User;
use App\Support\Money;
use Illuminate\Notifications\Messages\MailMessage;

trait FormatsPoMail
{
    protected function greet(MailMessage $mail, object $notifiable): MailMessage
    {
        $name = $notifiable->name ?? '';

        return $mail->greeting("Dear {$name},");
    }

    protected function regards(MailMessage $mail, ?User $purchasing): MailMessage
    {
        $name = filled($purchasing?->name)
            ? $purchasing->name
            : 'Purchasing PT. Sankei Dharma Indonesia';

        return $mail->salutation("Regards, {$name}");
    }

    protected function withBroadcastCc(MailMessage $mail, object $notifiable): MailMessage
    {
        if ($notifiable instanceof User) {
            $cc = $notifiable->broadcastEmailList();
            if ($cc !== []) {
                $mail->cc($cc);
            }
        }

        return $mail;
    }

    protected function itemLine(PurchaseOrderItem $poItem, mixed $qty): string
    {
        $item = $poItem->item;
        $line = sprintf(
            '%s (%s): %s kg',
            $item?->item_number ?? '-',
            $item?->description ?? '-',
            (int) round((float) $qty)
        );

        $price = Money::format($poItem->unit_price ?? $item?->price);
        if ($price !== '') {
            $line .= " · Rp {$price}/kg";
        }

        return $line;
    }

    protected function purchasingUser(PurchaseOrder $po): ?User
    {
        $po->loadMissing('creator');

        return $po->creator;
    }
}
