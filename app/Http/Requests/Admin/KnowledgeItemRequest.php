<?php

namespace App\Http\Requests\Admin;

use App\Modules\Knowledge\Enums\KnowledgeCategory;
use App\Support\Text;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Create or update an entry of the cafe's approved knowledge base.
 */
class KnowledgeItemRequest extends FormRequest
{
    /**
     * A record of another cafe is "not found" before anything is validated.
     */
    public function authorize(): bool
    {
        $record = $this->route('knowledge_item');

        if ($record) {
            Gate::authorize('update', $record);
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category' => ['required', 'string', Rule::in(KnowledgeCategory::values())],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'translations.en.title' => ['nullable', 'string'],
            'translations.en.body' => ['nullable', 'string'],
            'translations.ja.title' => ['nullable', 'string'],
            'translations.ja.body' => ['nullable', 'string'],
            'tags' => ['nullable', 'string'],
            'image_url' => ['nullable', 'string', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function knowledgeItemAttributes(): array
    {
        $data = $this->validated();

        return [
            'category' => $data['category'],
            'title' => $data['title'],
            'body' => $data['body'],
            'translations' => [
                'en' => ['title' => $data['translations']['en']['title'] ?? null, 'body' => $data['translations']['en']['body'] ?? null],
                'ja' => ['title' => $data['translations']['ja']['title'] ?? null, 'body' => $data['translations']['ja']['body'] ?? null],
            ],
            'tags' => Text::list($data['tags'] ?? null),
            'image_url' => $data['image_url'] ?? null,
            'is_active' => $this->boolean('is_active'),
            'sort_order' => $data['sort_order'] ?? 0,
        ];
    }
}
