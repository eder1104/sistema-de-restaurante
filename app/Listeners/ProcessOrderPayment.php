<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Events\OrderStatusUpdated;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Str;

class ProcessOrderPayment implements ShouldQueue
{
    public int $delay = 2;

    public function __construct(private readonly OrderRepositoryInterface $orderRepository) {}

    public function handle(OrderCreated $event): void
    {
        $order = $event->order;

        $this->simulatePaymentGateway($order->id);
    }

    private function simulatePaymentGateway(int $orderId): void
    {
        sleep(1);

        $paidOrder = $this->orderRepository->updateStatus($orderId, 'paid', [
            'transaction_id' => 'TXN-' . strtoupper(Str::random(10)),
            'paid_at'        => now(),
        ]);

        OrderStatusUpdated::dispatch($paidOrder);

        sleep(2);

        $preparingOrder = $this->orderRepository->updateStatus($orderId, 'preparing');

        OrderStatusUpdated::dispatch($preparingOrder);

        sleep(5);

        $readyOrder = $this->orderRepository->updateStatus($orderId, 'ready');

        OrderStatusUpdated::dispatch($readyOrder);
    }
}
