<?php

declare(strict_types=1);

namespace App\Infrastructure\Notifications;

use App\Domain\Order\Entities\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class NewOrderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public bool $afterCommit = true; // KEY: only queued after transaction commits

    public function __construct(
        private readonly Order $order,
    ) {}

    /** @return array<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Don hang moi #' . $this->order->id)
            ->markdown('emails.orders.new-order', [
                'order' => $this->order,
            ]);
    }
}
