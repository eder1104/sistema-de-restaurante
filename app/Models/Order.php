<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Order extends Model
{
    protected $fillable = [
        'customer_name',
        'customer_email',
        'items',
        'total',
        'status',
        'payment_method',
        'transaction_id',
        'paid_at',
    ];

    protected $casts = [
        'items'    => 'array',
        'total'    => 'decimal:2',
        'paid_at'  => 'datetime',
    ];

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function statusLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->status) {
                'pending'   => 'Pendiente',
                'paid'      => 'Pagado',
                'preparing' => 'En Preparación',
                'ready'     => 'Listo',
                'delivered' => 'Entregado',
                'cancelled' => 'Cancelado',
                default     => $this->status,
            }
        );
    }
}
