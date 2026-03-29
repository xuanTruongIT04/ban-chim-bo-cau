<?php

declare(strict_types=1);

namespace App\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CheckoutRequest extends FormRequest
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
            'customer_name'    => 'required|string|max:255',
            'customer_phone'   => 'required|string|regex:/^0\d{9}$/',
            'delivery_address' => 'required|string|max:1000',
            'payment_method'   => 'required|string|in:cod,chuyen_khoan',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'customer_name.required'    => 'Vui lòng nhập họ tên.',
            'customer_phone.required'   => 'Vui lòng nhập số điện thoại.',
            'customer_phone.regex'      => 'Số điện thoại phải có 10 chữ số và bắt đầu bằng 0.',
            'delivery_address.required' => 'Vui lòng nhập địa chỉ giao hàng.',
            'payment_method.required'   => 'Vui lòng chọn phương thức thanh toán.',
            'payment_method.in'         => 'Phương thức thanh toán không hợp lệ. Chọn cod hoặc chuyen_khoan.',
        ];
    }
}
