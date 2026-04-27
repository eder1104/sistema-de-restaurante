<?php

namespace App\Repositories\Eloquent;

use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class OrderRepository implements OrderRepositoryInterface
{
    public function __construct(private readonly Order $model) {}

    public function all(): Collection
    {
        return $this->model->latest()->get();
    }

    public function findById(int $id): ?Order
    {
        return $this->model->find($id);
    }

    public function create(array $data): Order
    {
        return $this->model->create($data);
    }

    public function updateStatus(int $id, string $status, array $extra = []): Order
    {
        $order = $this->model->findOrFail($id);
        $order->update(array_merge(['status' => $status], $extra));
        return $order->fresh();
    }
}
