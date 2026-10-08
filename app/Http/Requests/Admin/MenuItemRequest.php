<?php

namespace App\Http\Requests\Admin;

use App\Modules\Menu\Enums\MenuCategory;
use App\Support\Text;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Create or update a menu item. The route model binding and the policy decide
 * whose item it is; this decides what may be written.
 */
class MenuItemRequest extends FormRequest
{
    /**
     * A record of another cafe is "not found" before anything is validated.
     */
    public function authorize(): bool
    {
        $record = $this->route('menu_item');

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
            'category' => ['required', 'string', Rule::in(MenuCategory::values())],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'translations.en.name' => ['nullable', 'string'],
            'translations.en.description' => ['nullable', 'string'],
            'translations.ja.name' => ['nullable', 'string'],
            'translations.ja.description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'image_url' => ['nullable', 'string', 'max:2048'],
            'tags' => ['nullable', 'string'],
            'allergens' => ['nullable', 'string'],
            'serving' => ['nullable', 'string', 'max:30'],
            'calories' => ['nullable', 'integer', 'min:0', 'max:5000'],
            'is_featured' => ['sometimes', 'boolean'],
            'is_sold_out' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * The columns to write, without the slug (which only a new item gets).
     *
     * @return array<string, mixed>
     */
    public function menuItemAttributes(): array
    {
        $data = $this->validated();

        return [
            'category' => $data['category'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'translations' => [
                'en' => ['name' => $data['translations']['en']['name'] ?? null, 'description' => $data['translations']['en']['description'] ?? null],
                'ja' => ['name' => $data['translations']['ja']['name'] ?? null, 'description' => $data['translations']['ja']['description'] ?? null],
            ],
            'price' => $data['price'],
            'image_url' => $data['image_url'] ?? null,
            'tags' => Text::slugList($data['tags'] ?? null),
            'allergens' => Text::slugList($data['allergens'] ?? null),
            'serving' => filled($data['serving'] ?? null) ? Str::of($data['serving'])->trim()->slug('_')->toString() : null,
            'calories' => $data['calories'] ?? null,
            'is_featured' => $this->boolean('is_featured'),
            'is_sold_out' => $this->boolean('is_sold_out'),
            'is_active' => $this->boolean('is_active'),
            'sort_order' => $data['sort_order'] ?? 0,
        ];
    }
}
