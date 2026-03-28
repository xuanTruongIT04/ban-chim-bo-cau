<?php

declare(strict_types=1);

namespace App\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UploadProductImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<string>> */
    public function rules(): array
    {
        return [
            'image'      => ['required', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'is_primary' => ['boolean'],
        ];
    }
}
