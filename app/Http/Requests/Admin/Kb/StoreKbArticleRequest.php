<?php

namespace App\Http\Requests\Admin\Kb;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreKbArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('kb.manage') ?? false;
    }

    public function rules(): array
    {
        $moduleId = $this->input('kb_module_id');
        $submoduleKey = $this->input('submodule_key');
        $normalizedKey = ($submoduleKey === '' || $submoduleKey === 'overview') ? null : $submoduleKey;

        return [
            'kb_module_id'   => ['required', 'integer', 'exists:kb_modules,id'],
            'submodule_key'  => [
                'nullable',
                'string',
                'max:80',
                Rule::unique('kb_articles', 'submodule_key')
                    ->where(fn ($q) => $q->where('kb_module_id', $moduleId))
                    ->ignore($this->route('article')?->id),
            ],
            'title_en'       => ['required', 'string', 'max:255'],
            'title_bn'       => ['required', 'string', 'max:255'],
            'summary_en'     => ['nullable', 'string', 'max:500'],
            'summary_bn'     => ['nullable', 'string', 'max:500'],
            'body_en'        => ['nullable', 'string'],
            'body_bn'        => ['nullable', 'string'],
            'is_published'   => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $key = $this->input('submodule_key');

        if ($key === '' || $key === 'overview') {
            $this->merge(['submodule_key' => null]);
        }
    }
}
