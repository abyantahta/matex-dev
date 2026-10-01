<?php

namespace App\Notifications;

use App\Models\PurchaseOrder;
use App\Notifications\Channels\WhatsAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to every Supplier RM user of a PO's company when Purchasing submits
 * the draft (SubmitPurchaseOrder) — the point where the ball moves to
 * their side: review and confirm qty/schedule.
 */
class PurchaseOrderSubmittedNotification extends Notification
{
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
            fn ($poItem) => "- {$poItem->item->item_number} ({$poItem->item->description}): {$poItem->qty_ordered} {$poItem->item->uom}"
        )->implode("\n");

        return "Halo {$notifiable->name},\n\n"
            ."Purchasing PT. Sankei Dharma Indonesia telah mengirimkan draft PO *{$po->po_number}* dan menunggu konfirmasi qty & jadwal dari Anda.\n\n"
            ."{$lines}\n\n"
            .'Due date: '.$po->due_date->format('d M Y')."\n\n"
            ."Silakan login ke portal Matex untuk konfirmasi:\n"
            .route('purchase-orders.show', $po);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $po = $this->po;

        $mail = (new MailMessage)
            ->subject("Draft PO {$po->po_number} Menunggu Konfirmasi Anda")
            ->greeting("Halo {$notifiable->name},")
            ->line("Purchasing PT. Sankei Dharma Indonesia telah mengirimkan draft Purchase Order **{$po->po_number}** dan menunggu konfirmasi qty & jadwal pengiriman dari Anda.")
            ->line('Rencana pesanan:');

        foreach ($po->items as $poItem) {
            $mail->line("- {$poItem->item->item_number} ({$poItem->item->description}): {$poItem->qty_ordered} {$poItem->item->uom}");
        }

        return $mail
            ->line('Due date: '.$po->due_date->format('d M Y'))
            ->line('Mohon segera login ke portal Matex untuk meninjau dan mengonfirmasi qty & jadwal pengiriman.')
            ->action('Buka PO di Portal Matex', route('purchase-orders.show', $po))
            ->line('Terima kasih atas kerja samanya.');
    }
}
