<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRecipientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('recipient')->user_id === $this->user()->id;
    }

    public function rules(): array
    {
        return [
            'name'               => ['required', 'string', 'max:100'],
            'phone'              => ['required', 'string', 'regex:/^0[0-9]{9}$/'],
            'notify_days_before' => ['required', 'array', 'min:1'],
            'notify_days_before.*' => ['integer', 'in:1,3,7,14'],
            'is_active'          => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'               => 'Vui lòng nhập tên người nhận.',
            'phone.required'              => 'Vui lòng nhập số điện thoại.',
            'phone.regex'                 => 'Số điện thoại không đúng định dạng (VD: 0912345678).',
            'notify_days_before.required' => 'Chọn ít nhất một thời điểm nhắc.',
        ];
    }
}
