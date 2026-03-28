<?php

declare(strict_types=1);

namespace App\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AdjustStockRequest extends FormRequest
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
            'delta'           => ['required', 'numeric'],
            'adjustment_type' => ['required', 'string', Rule::in(['nhap_hang', 'kiem_ke', 'hu_hong', 'khac'])],
            'note'            => ['nullable', 'string', 'max:1000'],
        ];
    }
}
