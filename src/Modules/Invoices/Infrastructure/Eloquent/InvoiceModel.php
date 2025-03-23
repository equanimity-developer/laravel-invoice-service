<?php

declare(strict_types=1);

namespace Modules\Invoices\Infrastructure\Eloquent;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class InvoiceModel extends Model
{
    use HasUuids;

    protected $table = 'invoices';

    protected $fillable = [
        'customer_name',
        'customer_email',
        'status',
    ];

    public function productLines(): HasMany
    {
        return $this->hasMany(ProductLineModel::class, 'invoice_id');
    }
} 