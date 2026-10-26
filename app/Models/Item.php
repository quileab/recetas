<?php

namespace App\Models;

use App\Services\CostCalculator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Item extends Model
{
    protected $guarded = [];

    public function recipeItems()
    {
        return $this->hasMany(RecipeItem::class);
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function isPreparation(): bool
    {
        return $this->type === 'preparation';
    }

    /**
     * Cost of one recipe unit (g, unit, minute...).
     */
    public function unitCost(): float
    {
        return app(CostCalculator::class)->unitCost($this);
    }

    /**
     * Full cost of the batch for a preparation item.
     */
    public function batchCost(): float
    {
        if ($this->isPreparation() && $this->recipe) {
            return $this->recipe->calculateTotalCost();
        }

        return (float) $this->purchase_price;
    }

    public function isUsedInRecipes(): bool
    {
        return $this->recipeItems()->exists();
    }

    protected static function booted(): void
    {
        static::deleting(function (Item $item) {
            if ($item->isPreparation() && $item->isUsedInRecipes()) {
                return false;
            }

            return true;
        });
    }
}
