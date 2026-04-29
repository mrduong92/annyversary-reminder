<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMemorialEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isSolar = $this->input('date_type') === 'solar';

        return [
            'family_member_id' => [
                'required',
                'integer',
                'exists:family_members,id',
                function ($attribute, $value, $fail) {
                    // Kiểm tra xem thành viên này đã có ngày giỗ chưa
                    $existing = \App\Models\MemorialEvent::where('family_member_id', $value)
                        ->where('user_id', auth()->id())
                        ->exists();
                    if ($existing) {
                        $fail('Thành viên này đã có ngày giỗ. Mỗi người chỉ có 1 ngày giỗ.');
                    }
                },
            ],
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
            'family_member_id.required' => 'Vui lòng chọn thành viên.',
            'lunar_day.required'   => 'Vui lòng nhập ngày âm lịch.',
            'lunar_day.min'        => 'Ngày âm lịch phải từ 1 đến 30.',
            'lunar_day.max'        => 'Ngày âm lịch phải từ 1 đến 30.',
            'lunar_month.required' => 'Vui lòng nhập tháng âm lịch.',
            'lunar_month.min'      => 'Tháng âm lịch phải từ 1 đến 12.',
            'lunar_month.max'      => 'Tháng âm lịch phải từ 1 đến 12.',
        ];
    }
}
