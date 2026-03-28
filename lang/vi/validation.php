<?php

return [
    'accepted'  => ':attribute phải được chấp nhận.',
    'required'  => 'Trường :attribute là bắt buộc.',
    'email'     => ':attribute phải là địa chỉ email hợp lệ.',
    'min'       => [
        'numeric' => ':attribute phải tối thiểu :min.',
        'string'  => ':attribute phải có ít nhất :min ký tự.',
    ],
    'max'       => [
        'numeric' => ':attribute không được vượt quá :max.',
        'string'  => ':attribute không được vượt quá :max ký tự.',
    ],
    'unique'    => ':attribute này đã được sử dụng.',
    'confirmed' => 'Xác nhận :attribute không khớp.',
    'numeric'   => ':attribute phải là số.',
    'integer'   => ':attribute phải là số nguyên.',
    'string'    => ':attribute phải là chuỗi ký tự.',
    'in'        => ':attribute không hợp lệ.',

    'attributes' => [
        'email'    => 'email',
        'password' => 'mật khẩu',
        'name'     => 'tên',
        'phone'    => 'số điện thoại',
        'address'  => 'địa chỉ',
    ],
];
