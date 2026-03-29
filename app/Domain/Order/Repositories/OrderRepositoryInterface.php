<?php

declare(strict_types=1);

namespace App\Domain\Order\Repositories;

use App\Domain\Order\Entities\Order;
use App\Domain\Order\Enums\DeliveryMethod;
use App\Domain\Order\Enums\OrderStatus;
use App\Domain\Order\Enums\PaymentStatus;

interface OrderRepositoryInterface
{
    /**
     * @param array<string, mixed>  $data
     * @param array<int, array<string, mixed>> $items
     */
    public function create(array $data, array $items): Order;

    public function findById(int $id): ?Order;

    public function updateStatus(int $id, OrderStatus $status): Order;

    public function updatePaymentStatus(int $id, PaymentStatus $status): Order;

    public function updateDeliveryMethod(int $id, DeliveryMethod $method): Order;
}
