<?php

declare(strict_types=1);

namespace App\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateProductRequest extends FormRequest
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
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'price_vnd'   => ['sometimes', 'integer', 'min:0'],
            'unit_type'   => ['sometimes', 'string', Rule::in(['con', 'kg'])],
            'category_id' => ['sometimes', 'integer', 'exists:categories,id'],
            'is_active'   => ['sometimes', 'boolean'],
        ];
    }
}
