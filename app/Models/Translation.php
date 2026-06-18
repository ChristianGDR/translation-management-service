<?php

namespace App\Models;

use App\Enums\TranslationContext;
use Database\Factories\TranslationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['key', 'description', 'tags'])]
#[Hidden(['search_blob'])]
class Translation extends Model
{
    /** @use HasFactory<TranslationFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tags' => AsEnumCollection::class.':'.TranslationContext::class,
        ];
    }

    /**
     * Per-locale values for this translation key.
     *
     * @return HasMany<TranslationLocale, $this>
     */
    public function locales(): HasMany
    {
        return $this->hasMany(TranslationLocale::class);
    }

    /**
     * Recompute the search haystack from key + tags + locale contents.
     * Called after writes; persisted quietly so it never re-triggers events.
     */
    public function rebuildSearchBlob(): void
    {
        $this->loadMissing('locales');

        $parts = array_filter([
            $this->key,
            collect($this->tags)->map(fn (TranslationContext $tag) => $tag->value)->implode(' '),
            $this->locales->pluck('content')->implode(' '),
        ]);

        // Set directly (not mass-assigned) so it stays out of fillable input.
        $this->search_blob = Str::lower(implode(' ', $parts));
        $this->saveQuietly();
    }
}
