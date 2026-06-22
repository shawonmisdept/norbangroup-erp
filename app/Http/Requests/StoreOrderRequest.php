<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:255'],
            'company'      => ['nullable', 'string', 'max:255'],
            'email'        => ['required', 'email', 'max:255'],
            'phone'        => ['required', 'string', 'max:30'],
            'item_name' => ['required', 'string', 'max:255'],
            'quantity'     => ['nullable', 'integer', 'min:1'],
            'notes'        => ['nullable', 'string', 'max:5000'],
            'techpack'     => ['nullable', 'array'],
            'techpack.*'   => ['file', 'max:102400'],
            'artwork'      => ['nullable', 'array'],
            'artwork.*'    => ['file', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'         => 'Please enter your name.',
            'email.required'        => 'Please enter your email address.',
            'email.email'           => 'Please enter a valid email address.',
            'phone.required'        => 'Please enter your phone number.',
            'item_name.required' => 'Please enter an item name.',
            'techpack.*.max'        => 'Each tech pack file must not exceed 100 MB.',
            'artwork.*.max'         => 'Each artwork file must not exceed 10 MB.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $techpackTotal = collect($this->file('techpack', []))->sum(fn ($f) => $f->getSize());
            if ($techpackTotal > 200 * 1024 * 1024) {
                $validator->errors()->add('techpack', 'Total tech pack size must not exceed 200 MB.');
            }

            $artworkTotal = collect($this->file('artwork', []))->sum(fn ($f) => $f->getSize());
            if ($artworkTotal > 20 * 1024 * 1024) {
                $validator->errors()->add('artwork', 'Total artwork size must not exceed 20 MB.');
            }
        });
    }
}
