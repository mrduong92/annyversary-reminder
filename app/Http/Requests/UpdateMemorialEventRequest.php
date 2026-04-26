<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMemorialEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('event')->user_id === $this->user()->id;
    }

    public function rules(): array
    {
        $isSolar = $this->input('date_type') === 'solar';

        return [
            'name'         => ['required', 'string', 'max:100'],
            'relationship' => ['nullable', 'string', 'max:50'],
            'date_type'    => ['required', 'in:lunar,solar'],
            'lunar_day'    => ['required', 'integer', 'min:1', 'max:' . ($isSolar ? 31 : 30)],
            'lunar_month'  => ['required', 'integer', 'min:1', 'max:12'],
            'notes'           => ['nullable', 'string', 'max:500'],
            'is_active'       => ['boolean'],
            'recipient_ids'   => ['nullable', 'array'],
            'recipient_ids.*' => ['integer', 'exists:recipients,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'        => 'Vui lòng nhập tên người mất.',
            'lunar_day.required'   => 'Vui lòng nhập ngày âm lịch.',
            'lunar_day.min'        => 'Ngày âm lịch phải từ 1 đến 30.',
            'lunar_day.max'        => 'Ngày âm lịch phải từ 1 đến 30.',
            'lunar_month.required' => 'Vui lòng nhập tháng âm lịch.',
            'lunar_month.min'      => 'Tháng âm lịch phải từ 1 đến 12.',
            'lunar_month.max'      => 'Tháng âm lịch phải từ 1 đến 12.',
        ];
    }
}
