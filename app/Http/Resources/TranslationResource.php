<?php

namespace App\Http\Resources;

use App\Models\Translation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Translation
 */
class TranslationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'description' => $this->description,
            'tags' => collect($this->tags)->map(fn ($tag) => $tag->value)->values()->all(),
            'locales' => $this->locales->mapWithKeys(
                fn ($locale) => [$locale->locale => $locale->content]
            ),
        ];
    }
}
