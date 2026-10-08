<?php

namespace App\Modules\Knowledge\Queries;

use App\Models\Cafe;
use App\Models\CafeKnowledgeItem;
use App\Support\SearchText;
use Illuminate\Support\Collection;

/**
 * The cafe's approved knowledge base, searched by keyword. The only source the
 * AI Barista may state cafe facts from.
 */
class SearchKnowledge
{
    /**
     * @return Collection<int, CafeKnowledgeItem>
     */
    public function handle(Cafe $cafe, string $query, ?string $category = null, int $limit = 5): Collection
    {
        $query = SearchText::normalize($query);

        return $cafe->knowledgeItems()
            ->where('is_active', true)
            ->when($category, fn ($q) => $q->where('category', $category))
            ->get()
            ->filter(fn (CafeKnowledgeItem $item) => SearchText::matches(
                $item->title.' '.$item->body.' '.implode(' ', $item->tags ?? []),
                $query,
            ))
            ->take($limit);
    }
}
