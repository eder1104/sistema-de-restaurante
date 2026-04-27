<?php

namespace App\Http\Controllers\Api;

use App\Events\OrderCreated;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function __construct(private readonly OrderRepositoryInterface $orderRepository) {}

    public function index(): JsonResponse
    {
        $orders = $this->orderRepository->all();

        return response()->json([
            'data' => $orders->map(fn ($order) => [
                'id'            => $order->id,
                'customer_name' => $order->customer_name,
                'customer_email'=> $order->customer_email,
                'items'         => $order->items,
                'total'         => $order->total,
                'status'        => $order->status,
                'status_label'  => $order->status_label,
                'payment_method'=> $order->payment_method,
                'transaction_id'=> $order->transaction_id,
                'paid_at'       => $order->paid_at?->toISOString(),
                'created_at'    => $order->created_at->toISOString(),
            ]),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $order = $this->orderRepository->findById($id);

        if (! $order) {
            return response()->json(['message' => 'Pedido no encontrado.'], 404);
        }

        return response()->json(['data' => $order]);
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $items = $request->validated('items');

        $total = collect($items)->sum(
            fn ($item) => $item['unit_price'] * $item['quantity']
        );

        $order = $this->orderRepository->create([
            'customer_name'  => $request->validated('customer_name'),
            'customer_email' => $request->validated('customer_email'),
            'payment_method' => $request->validated('payment_method'),
            'items'          => $items,
            'total'          => $total,
            'status'         => 'pending',
        ]);

        OrderCreated::dispatch($order);

        return response()->json([
            'message' => 'Pedido creado exitosamente.',
            'data'    => [
                'id'            => $order->id,
                'customer_name' => $order->customer_name,
                'total'         => $order->total,
                'status'        => $order->status,
                'status_label'  => $order->status_label,
                'created_at'    => $order->created_at->toISOString(),
            ],
        ], 201);
    }
}
