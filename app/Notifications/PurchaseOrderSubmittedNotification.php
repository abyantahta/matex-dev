<?php

namespace App\Notifications;

use App\Models\PurchaseOrder;
use App\Notifications\Channels\WhatsAppChannel;
use App\Notifications\Concerns\FormatsPoMail;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PurchaseOrderSubmittedNotification extends Notification
{
    use FormatsPoMail;
    use Queueable;

    public function __construct(private readonly PurchaseOrder $po) {}

    public function via(object $notifiable): array
    {
        return ['mail', WhatsAppChannel::class];
    }

    public function toWhatsApp(object $notifiable): string
    {
        $po = $this->po;
        $lines = $po->items->map(
            fn ($poItem) => '- '.$this->itemLine($poItem, $poItem->qty_ordered)
        )->implode("\n");

        return "Dear {$notifiable->name},\n\n"
            ."Purchasing PT. Sankei Dharma Indonesia telah mengirimkan draft PO *{$po->po_number}* dan menunggu konfirmasi qty & jadwal dari Anda.\n\n"
            ."{$lines}\n\n"
            .'Due date: '.$po->due_date->format('d M Y')."\n\n"
            .route('purchase-orders.show', $po);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $po = $this->po;

        $mail = (new MailMessage)
            ->subject("Draft PO {$po->po_number} Menunggu Konfirmasi Anda");

        $this->greet($mail, $notifiable);
        $mail->line("Purchasing PT. Sankei Dharma Indonesia telah mengirimkan draft Purchase Order **{$po->po_number}** dan menunggu konfirmasi qty & jadwal pengiriman dari Anda.")
            ->line('Rencana pesanan:');

        foreach ($po->items as $poItem) {
            $mail->line('- '.$this->itemLine($poItem, $poItem->qty_ordered));
        }

        $mail->line('Due date: '.$po->due_date->format('d M Y'))
            ->action('Buka PO di Portal Matex', route('purchase-orders.show', $po));
        $this->regards($mail, $this->purchasingUser($po));

        return $this->withBroadcastCc($mail, $notifiable);
    }
}
