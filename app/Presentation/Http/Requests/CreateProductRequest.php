<?php

declare(strict_types=1);

namespace App\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name'           => ['required', 'string', 'max:255'],
            'description'    => ['nullable', 'string', 'max:5000'],
            'price_vnd'      => ['required', 'integer', 'min:0'],
            'unit_type'      => ['required', 'string', Rule::in(['con', 'kg'])],
            'category_id'    => ['required', 'integer', 'exists:categories,id'],
            'stock_quantity' => ['numeric', 'min:0'],
            'is_active'      => ['boolean'],
        ];
    }
}
