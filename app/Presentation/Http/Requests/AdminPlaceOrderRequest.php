<?php

declare(strict_types=1);

namespace App\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AdminPlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'customer_name'       => 'required|string|max:255',
            'customer_phone'      => 'required|string|regex:/^0\d{9}$/',
            'delivery_address'    => 'required|string|max:1000',
            'items'               => 'required|array|min:1',
            'items.*.product_id'  => 'required|integer|exists:products,id',
            'items.*.quantity'    => 'required|numeric|gt:0',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'customer_name.required'         => 'Vui lòng nhập họ tên.',
            'customer_phone.required'        => 'Vui lòng nhập số điện thoại.',
            'customer_phone.regex'           => 'Số điện thoại phải có 10 chữ số và bắt đầu bằng 0.',
            'delivery_address.required'      => 'Vui lòng nhập địa chỉ giao hàng.',
            'items.required'                 => 'Vui lòng chọn ít nhất một sản phẩm.',
            'items.min'                      => 'Đơn hàng phải có ít nhất một sản phẩm.',
            'items.*.product_id.required'    => 'Mã sản phẩm là bắt buộc.',
            'items.*.product_id.exists'      => 'Sản phẩm không tồn tại.',
            'items.*.quantity.required'      => 'Số lượng sản phẩm là bắt buộc.',
            'items.*.quantity.gt'            => 'Số lượng sản phẩm phải lớn hơn 0.',
        ];
    }
}
