<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMemorialEventRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $isSolar = $this->input('date_type') === 'solar';

        return [
            // title hoặc family_member_id — bắt buộc phải có ít nhất một
            'title' => [
                'nullable', 'string', 'max:200',
                function ($attribute, $value, $fail) {
                    if (empty($value) && empty($this->input('family_member_id'))) {
                        $fail('Vui lòng nhập tiêu đề hoặc chọn thành viên gia phả.');
                    }
                },
            ],
            'family_member_id' => [
                'nullable', 'integer', 'exists:family_members,id',
                function ($attribute, $value, $fail) {
                    if (! $value) return;
                    $member = \App\Models\FamilyMember::find((int) $value);
                    if (! $member) return;
                    if (\App\Models\MemorialEvent::where('family_member_id', $value)->exists()) {
                        $fail('Thành viên này đã có ngày giỗ.');
                    }
                },
            ],
            'event_type'      => ['nullable', 'string', 'in:anniversary_of_death,ancestor_anniversary,birthday,anniversary,event'],
            'date_type'       => ['required', 'in:lunar,solar'],
            'lunar_day'       => ['required', 'integer', 'min:1', 'max:' . ($isSolar ? 31 : 30)],
            'lunar_month'     => ['required', 'integer', 'min:1', 'max:12'],
            'notes'           => ['nullable', 'string', 'max:500'],
            'is_active'       => ['boolean'],
            'recipient_ids'   => ['nullable', 'array'],
            'recipient_ids.*' => ['integer', 'exists:recipients,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'lunar_day.required'   => 'Vui lòng nhập ngày.',
            'lunar_month.required' => 'Vui lòng nhập tháng.',
        ];
    }
}
