<?php

declare(strict_types=1);

namespace App\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateDeliveryMethodRequest extends FormRequest
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
            'delivery_method' => 'required|string|in:noi_tinh,ngoai_tinh',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'delivery_method.required' => 'Vui long chon hinh thuc giao hang.',
            'delivery_method.in'       => 'Hinh thuc giao hang khong hop le. Chon noi_tinh hoac ngoai_tinh.',
        ];
    }
}
