<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePrayerRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title'             => ['required', 'string', 'max:200'],
            'content'           => ['required', 'string'],
            'memorial_event_id' => ['nullable', 'integer', 'exists:memorial_events,id'],
            'ai_generated'      => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'   => 'Vui lòng nhập tiêu đề văn khấn.',
            'content.required' => 'Nội dung văn khấn không được để trống.',
        ];
    }
}
