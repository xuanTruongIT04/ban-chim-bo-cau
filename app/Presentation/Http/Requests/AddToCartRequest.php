<?php

declare(strict_types=1);

namespace App\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AddToCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'min:1'],
            'quantity'   => ['required', 'numeric', 'gt:0'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'product_id.required' => 'Vui lòng chọn sản phẩm.',
            'product_id.integer'  => 'Mã sản phẩm không hợp lệ.',
            'product_id.min'      => 'Mã sản phẩm không hợp lệ.',
            'quantity.required'   => 'Vui lòng nhập số lượng.',
            'quantity.numeric'    => 'Số lượng phải là số.',
            'quantity.gt'         => 'Số lượng phải lớn hơn 0.',
        ];
    }
}
