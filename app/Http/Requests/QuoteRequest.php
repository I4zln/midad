<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// Shared rules for creating and editing a user quote. Ownership is enforced by QuotePolicy on the routes.
class QuoteRequest extends FormRequest
{
    public const CATEGORIES = ['تأمل', 'حياة', 'حب', 'أمل'];
    // How each category is written on screen (with its shadda).
    public const CATEGORY_LABELS = ['تأمل' => 'تأمّل', 'حياة' => 'حياة', 'حب' => 'حب', 'أمل' => 'أمل'];

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'min:3', 'max:500'],
            'author' => ['nullable', 'string', 'max:120'],
            'source' => ['nullable', 'string', 'max:160'],
            'category' => ['required', 'string', 'in:'.implode(',', self::CATEGORIES)],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('text'))) {
            $this->merge(['text' => trim($this->input('text'))]);
        }
    }
}
