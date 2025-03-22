<?php

declare(strict_types=1);

namespace Modules\Invoices\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AddProductLineRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_price' => ['required', 'integer', 'min:1'],
        ];
    }
} 