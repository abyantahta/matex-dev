<?php

namespace App\Notifications;

use App\Models\PurchaseOrder;
use App\Models\User;
use App\Notifications\Channels\WhatsAppChannel;
use App\Notifications\Concerns\FormatsPoMail;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PurchaseOrderRejectedNotification extends Notification
{
    use FormatsPoMail;
    use Queueable;

    public function __construct(
        private readonly PurchaseOrder $po,
        private readonly User $purchasing,
        private readonly string $reason,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', WhatsAppChannel::class];
    }

    public function toWhatsApp(object $notifiable): string
    {
        $po = $this->po;
        $lines = $po->items->map(
            fn ($poItem) => '- '.$this->itemLine($poItem, $poItem->qty_confirmed ?? $poItem->qty_ordered)
        )->implode("\n");

        return "Dear {$notifiable->name},\n\n"
            ."Konfirmasi PO *{$po->po_number}* ditolak oleh Purchasing. Mohon submit ulang.\n\n"
            ."Alasan: {$this->reason}\n\n"
            ."{$lines}\n\n"
            .route('purchase-orders.show', $po);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $po = $this->po;

        $mail = (new MailMessage)
            ->subject("Konfirmasi PO {$po->po_number} Ditolak — Mohon Submit Ulang");

        $this->greet($mail, $notifiable);
        $mail->line("Konfirmasi Purchase Order **{$po->po_number}** ditolak oleh Purchasing. Mohon submit ulang qty & jadwal.")
            ->line("Alasan: {$this->reason}")
            ->line('Qty yang diajukan:');

        foreach ($po->items as $poItem) {
            $mail->line('- '.$this->itemLine($poItem, $poItem->qty_confirmed ?? $poItem->qty_ordered));
        }

        $mail->action('Buka PO di Portal Matex', route('purchase-orders.show', $po));
        $this->regards($mail, $this->purchasing);

        return $this->withBroadcastCc($mail, $notifiable);
    }
}
