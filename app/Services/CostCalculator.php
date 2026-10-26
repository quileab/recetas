<?php

namespace App\Services;

use App\Exceptions\CircularRecipeDependencyException;
use App\Models\Item;
use App\Models\Recipe;
use App\Models\RecipeItem;

/**
 * Single source of truth for recipe and item costs.
 *
 * A "preparation" item represents the output of another recipe, so its unit
 * cost is the total cost of that recipe divided by its yield. The stack of
 * recipe IDs passed through the recursion prevents circular dependencies.
 */
class CostCalculator
{
    /**
     * Cost of one recipe unit of the given item.
     */
    public function unitCost(Item $item, array $stack = []): float
    {
        if ($item->isPreparation()) {
            $recipe = $item->recipe;

            if (! $recipe) {
                return 0.0;
            }

            return $this->totalInternal($recipe, $stack) / $this->yieldQuantity($recipe);
        }

        return (float) $item->purchase_price / max((float) $item->conversion_factor, 0.0001);
    }

    /**
     * Total cost (direct + overhead) of a recipe.
     */
    public function recipeTotal(Recipe $recipe): float
    {
        return $this->totalInternal($recipe, []);
    }

    /**
     * Direct cost of a recipe (ingredients, labor, packaging and direct costs).
     */
    public function recipeDirectCost(Recipe $recipe): float
    {
        return $this->directInternal($recipe, []);
    }

    /**
     * Overhead cost of a recipe.
     */
    public function recipeOverheadCost(Recipe $recipe, ?float $directCost = null): float
    {
        return $this->overheadInternal($recipe, $directCost, []);
    }

    /**
     * Cost of a single recipe line, handling percentage overheads.
     */
    public function recipeItemCost(Recipe $recipe, RecipeItem $recipeItem, ?float $directCost = null): float
    {
        return $this->itemInternal($recipe, $recipeItem, $directCost, []);
    }

    /**
     * Unit cost of the recipe output according to its declared yield.
     */
    public function recipeUnitCost(Recipe $recipe): float
    {
        return $this->recipeTotal($recipe) / $this->yieldQuantity($recipe);
    }

    private function totalInternal(Recipe $recipe, array $stack): float
    {
        $stack = $this->push($recipe, $stack);
        $directCost = $this->directInternal($recipe, $stack);

        return $directCost + $this->overheadInternal($recipe, $directCost, $stack);
    }

    private function directInternal(Recipe $recipe, array $stack): float
    {
        $recipe->loadMissing('stages.recipeItems.item.recipe');

        $directCost = 0.0;

        foreach ($recipe->stages as $stage) {
            foreach ($stage->recipeItems as $recipeItem) {
                $item = $recipeItem->item;

                if (! $item || $item->type === 'overhead') {
                    continue;
                }

                $directCost += $this->unitCost($item, $stack) * (float) $recipeItem->quantity;
            }
        }

        return $directCost;
    }

    private function overheadInternal(Recipe $recipe, ?float $directCost, array $stack): float
    {
        $directCost ??= $this->directInternal($recipe, $stack);
        $overheadCost = 0.0;

        foreach ($recipe->stages as $stage) {
            foreach ($stage->recipeItems as $recipeItem) {
                $item = $recipeItem->item;

                if (! $item || $item->type !== 'overhead') {
                    continue;
                }

                $overheadCost += $this->itemInternal($recipe, $recipeItem, $directCost, $stack);
            }
        }

        return $overheadCost;
    }

    private function itemInternal(Recipe $recipe, RecipeItem $recipeItem, ?float $directCost, array $stack): float
    {
        $item = $recipeItem->item;

        if (! $item) {
            return 0.0;
        }

        if ($item->type === 'overhead') {
            if ($item->overhead_type === 'fixed') {
                return (float) $item->purchase_price * (float) $recipeItem->quantity;
            }

            if ($item->overhead_type === 'percentage') {
                $base = $directCost ?? $this->directInternal($recipe, $stack);

                return $base * ((float) $item->purchase_price / 100.0) * (float) $recipeItem->quantity;
            }
        }

        return $this->unitCost($item, $stack) * (float) $recipeItem->quantity;
    }

    private function push(Recipe $recipe, array $stack): array
    {
        if (in_array($recipe->id, $stack, true)) {
            throw CircularRecipeDependencyException::detected();
        }

        $stack[] = $recipe->id;

        return $stack;
    }

    private function yieldQuantity(Recipe $recipe): float
    {
        return max((float) $recipe->yield_quantity, 0.0001);
    }
}
