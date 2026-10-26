<?php

namespace App\Services;

use App\Models\Item;
use App\Models\Recipe;
use App\Models\Stage;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RecipeExportImportService
{
    public function __construct(private PreparationService $preparations)
    {
    }

    /**
     * Export recipe as a structured array ready for JSON encoding.
     */
    public function exportRecipe(Recipe $recipe): array
    {
        $payload = $this->recipePayload($recipe);
        $dependencies = $this->dependencyPayloads($recipe);

        return [
            'version' => '1.1',
            'exported_at' => now()->toIso8601String(),
            'recipe' => $payload['recipe'],
            'stages' => $payload['stages'],
            'dependencies' => $dependencies,
        ];
    }

    /**
     * Export several recipes at once into a single collection file.
     *
     * @param  iterable<Recipe>  $recipes
     */
    public function exportRecipes(iterable $recipes): array
    {
        $recipes = collect($recipes);

        return [
            'version' => '1.1',
            'exported_at' => now()->toIso8601String(),
            'count' => $recipes->count(),
            'recipes' => $recipes
                ->map(fn (Recipe $recipe) => $this->recipePayload($recipe) + [
                    'dependencies' => $this->dependencyPayloads($recipe),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * Build the portable representation of a single recipe (header + stages).
     */
    private function recipePayload(Recipe $recipe): array
    {
        $recipe->loadMissing(['stages.recipeItems.item.recipe']);

        $stagesData = [];
        foreach ($recipe->stages as $stage) {
            $itemsData = [];
            foreach ($stage->recipeItems as $recipeItem) {
                $item = $recipeItem->item;

                $itemData = [
                    'name' => $item->name,
                    'type' => $item->type,
                    'category' => $item->category,
                ];

                if ($item->isPreparation()) {
                    $itemData['recipe_name'] = $item->recipe?->name;
                } else {
                    $itemData += [
                        'purchase_unit' => $item->purchase_unit,
                        'purchase_price' => (float) $item->purchase_price,
                        'recipe_unit' => $item->recipe_unit,
                        'conversion_factor' => (float) $item->conversion_factor,
                        'overhead_type' => $item->overhead_type,
                    ];
                }

                $itemsData[] = [
                    'quantity' => (float) $recipeItem->quantity,
                    'item' => $itemData,
                ];
            }

            $stagesData[] = [
                'name' => $stage->name,
                'order' => (int) $stage->order,
                'duration_minutes' => (int) $stage->duration_minutes,
                'items' => $itemsData,
            ];
        }

        return [
            'recipe' => [
                'name' => $recipe->name,
                'description' => $recipe->description,
                'yield_quantity' => (float) $recipe->yield_quantity,
                'yield_unit' => $recipe->yield_unit,
                'is_preparation' => $recipe->isPreparation(),
            ],
            'stages' => $stagesData,
        ];
    }

    /**
     * Portable payloads for every preparation recipe this recipe depends on.
     * Dependencies are returned in post-order so nested recipes come first.
     *
     * @return array<int, array>
     */
    private function dependencyPayloads(Recipe $recipe): array
    {
        $dependencies = [];
        $visited = [$recipe->id => true];

        $this->collectDependencies($recipe, $dependencies, $visited);

        return $dependencies;
    }

    private function collectDependencies(Recipe $recipe, array &$dependencies, array &$visited): void
    {
        $recipe->loadMissing('stages.recipeItems.item.recipe');

        foreach ($recipe->stages as $stage) {
            foreach ($stage->recipeItems as $recipeItem) {
                $item = $recipeItem->item;

                if (! $item?->isPreparation() || ! $item->recipe) {
                    continue;
                }

                $dependency = $item->recipe;

                if (isset($visited[$dependency->id])) {
                    continue;
                }

                $visited[$dependency->id] = true;
                $this->collectDependencies($dependency, $dependencies, $visited);
                $dependencies[] = $this->recipePayload($dependency);
            }
        }
    }

    /**
     * Inspect file content and extract recipe header and metadata without saving.
     *
     * Detects both the single recipe format ({recipe, stages}) and the
     * collection format ({recipes: [...]}) produced by a bulk export.
     */
    public function inspectJsonContent(string $jsonContent): array
    {
        $data = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            throw new InvalidArgumentException('El archivo no contiene un formato JSON válido.');
        }

        if (isset($data['recipes']) && is_array($data['recipes'])) {
            return $this->inspectCollection($data);
        }

        if (empty($data['recipe']['name'])) {
            throw new InvalidArgumentException('El archivo no contiene el nombre de la receta en su encabezado.');
        }

        $recipeName = trim($data['recipe']['name']);
        $recipeDescription = $data['recipe']['description'] ?? null;

        $existingRecipe = Recipe::where('name', $recipeName)->first();

        return [
            'mode' => 'single',
            'name' => $recipeName,
            'description' => $recipeDescription,
            'version' => $data['version'] ?? '1.0',
            'stages_count' => $this->countStages($data),
            'items_count' => $this->countItems($data),
            'dependencies_count' => count($data['dependencies'] ?? []),
            'exists' => $existingRecipe !== null,
            'existing_recipe_id' => $existingRecipe?->id,
            'raw_data' => $data,
        ];
    }

    /**
     * Inspect a bulk export file holding several recipes.
     */
    private function inspectCollection(array $data): array
    {
        $recipes = [];

        foreach ($data['recipes'] as $position => $entry) {
            if (!is_array($entry) || empty($entry['recipe']['name'])) {
                throw new InvalidArgumentException(
                    "La receta en la posición " . ($position + 1) . " del archivo no tiene un encabezado válido."
                );
            }

            $name = trim($entry['recipe']['name']);
            $recipes[] = [
                'name' => $name,
                'description' => $entry['recipe']['description'] ?? null,
                'stages_count' => $this->countStages($entry),
                'items_count' => $this->countItems($entry),
                'exists' => Recipe::where('name', $name)->exists(),
            ];
        }

        if ($recipes === []) {
            throw new InvalidArgumentException('El archivo no contiene ninguna receta para importar.');
        }

        return [
            'mode' => 'collection',
            'version' => $data['version'] ?? '1.0',
            'recipes' => $recipes,
            'recipes_count' => count($recipes),
            'stages_count' => array_sum(array_column($recipes, 'stages_count')),
            'items_count' => array_sum(array_column($recipes, 'items_count')),
            'dependencies_count' => array_sum(array_map(
                fn ($entry) => is_array($entry) ? count($entry['dependencies'] ?? []) : 0,
                $data['recipes']
            )),
            'conflicts_count' => count(array_filter($recipes, fn ($r) => $r['exists'])),
            'exists' => count(array_filter($recipes, fn ($r) => $r['exists'])) > 0,
            'raw_data' => $data,
        ];
    }

    private function countStages(array $entry): int
    {
        return isset($entry['stages']) && is_array($entry['stages']) ? count($entry['stages']) : 0;
    }

    private function countItems(array $entry): int
    {
        $total = 0;

        if (isset($entry['stages']) && is_array($entry['stages'])) {
            foreach ($entry['stages'] as $stage) {
                if (isset($stage['items']) && is_array($stage['items'])) {
                    $total += count($stage['items']);
                }
            }
        }

        return $total;
    }

    /**
     * Import recipe data into the database.
     */
    public function importRecipe(array $data, bool $overwrite = false): Recipe
    {
        return DB::transaction(fn () => $this->importPayload($data, $overwrite, []));
    }

    /**
     * Import a recipe payload, creating any missing preparation dependencies first.
     */
    private function importPayload(array $data, bool $overwrite, array $visiting): Recipe
    {
        if (empty($data['recipe']['name'])) {
            throw new InvalidArgumentException('Estructura de receta inválida.');
        }

        $recipeName = trim($data['recipe']['name']);

        if (isset($visiting[$recipeName])) {
            throw new InvalidArgumentException(
                "Se detectó una dependencia circular en la receta '{$recipeName}'."
            );
        }

        $visiting[$recipeName] = true;

        // Dependencies are only created when missing; existing sub-recipes are reused.
        foreach ($data['dependencies'] ?? [] as $dependency) {
            if (! is_array($dependency) || empty($dependency['recipe']['name'])) {
                continue;
            }

            $dependencyName = trim($dependency['recipe']['name']);

            if (Recipe::where('name', $dependencyName)->exists()) {
                continue;
            }

            $this->importPayload($dependency, false, $visiting);
        }

        $recipeDescription = $data['recipe']['description'] ?? null;
        $yieldQuantity = max((float) ($data['recipe']['yield_quantity'] ?? 1), 0.001);
        $yieldUnit = $data['recipe']['yield_unit'] ?? 'unidad';
        $isPreparation = (bool) ($data['recipe']['is_preparation'] ?? false);

        $recipe = Recipe::where('name', $recipeName)->first();

        if ($recipe) {
            if (! $overwrite) {
                throw new InvalidArgumentException("La receta '{$recipeName}' ya existe y no se autorizó la sobreescritura.");
            }

            $recipe->update([
                'description' => $recipeDescription,
                'yield_quantity' => $yieldQuantity,
                'yield_unit' => $yieldUnit,
            ]);

            // Clear existing stages & items
            $recipe->stages()->delete();
        } else {
            $recipe = Recipe::create([
                'name' => $recipeName,
                'description' => $recipeDescription,
                'yield_quantity' => $yieldQuantity,
                'yield_unit' => $yieldUnit,
            ]);
        }

        if (! empty($data['stages']) && is_array($data['stages'])) {
            foreach ($data['stages'] as $index => $stageData) {
                $stage = $recipe->stages()->create([
                    'name' => $stageData['name'] ?? 'Etapa ' . ($index + 1),
                    'order' => $stageData['order'] ?? ($index + 1),
                    'duration_minutes' => $stageData['duration_minutes'] ?? 0,
                ]);

                if (! empty($stageData['items']) && is_array($stageData['items'])) {
                    foreach ($stageData['items'] as $itemWrapper) {
                        $itemDef = $itemWrapper['item'] ?? [];
                        if (empty($itemDef['name'])) {
                            continue;
                        }

                        $item = $this->resolveItem($itemDef, $recipeName);

                        $stage->recipeItems()->create([
                            'recipe_id' => $recipe->id,
                            'item_id' => $item->id,
                            'quantity' => $itemWrapper['quantity'] ?? 0,
                        ]);
                    }
                }
            }
        }

        if ($recipe->dependsOn($recipe)) {
            throw new InvalidArgumentException(
                "La receta '{$recipeName}' generaría una dependencia circular con sus preparaciones."
            );
        }

        if ($isPreparation) {
            $this->preparations->publish($recipe->refresh());
        } elseif ($recipe->item()->exists() && ! $recipe->isUsedAsItem()) {
            $this->preparations->unpublish($recipe);
        }

        return $recipe;
    }

    /**
     * Resolve a recipe line into a catalog item, linking preparations to their recipe.
     */
    private function resolveItem(array $itemDef, string $ownerRecipeName): Item
    {
        if (($itemDef['type'] ?? 'ingredient') === 'preparation') {
            $sourceName = trim((string) ($itemDef['recipe_name'] ?? ''));
            $sourceRecipe = $sourceName !== '' ? Recipe::where('name', $sourceName)->first() : null;

            if (! $sourceRecipe) {
                throw new InvalidArgumentException(
                    "La receta '{$ownerRecipeName}' usa la preparación '{$itemDef['name']}', "
                    . "pero no se encontró su receta de origen ('{$sourceName}') ni venía incluida en el archivo."
                );
            }

            return $sourceRecipe->item()->first() ?? $this->preparations->publish($sourceRecipe);
        }

        $name = trim($itemDef['name']);

        $item = Item::where('name', $name)->where('type', '!=', 'preparation')->first();

        if ($item) {
            return $item;
        }

        return Item::create([
            'name' => $name,
            'type' => $itemDef['type'] ?? 'ingredient',
            'category' => $itemDef['category'] ?? null,
            'purchase_unit' => $itemDef['purchase_unit'] ?? null,
            'purchase_price' => $itemDef['purchase_price'] ?? 0,
            'recipe_unit' => $itemDef['recipe_unit'] ?? null,
            'conversion_factor' => $itemDef['conversion_factor'] ?? 1,
            'overhead_type' => $itemDef['overhead_type'] ?? null,
        ]);
    }

    /**
     * Import every recipe contained in a bulk export file.
     *
     * The whole batch is applied inside a single transaction, so a failure
     * halfway through does not leave the catalog in a partial state.
     *
     * @return array<int, Recipe>
     */
    public function importRecipes(array $data, bool $overwrite = false): array
    {
        if (empty($data['recipes']) || !is_array($data['recipes'])) {
            throw new InvalidArgumentException('El archivo no contiene recetas para importar.');
        }

        $entries = [];

        foreach ($data['recipes'] as $position => $entry) {
            if (!is_array($entry) || empty($entry['recipe']['name'])) {
                throw new InvalidArgumentException(
                    "La receta en la posición " . ($position + 1) . " del archivo no tiene un encabezado válido."
                );
            }

            $entries[] = $entry;
        }

        if (! $overwrite) {
            $conflicts = collect($entries)
                ->map(fn ($entry) => trim($entry['recipe']['name']))
                ->filter(fn ($name) => Recipe::where('name', $name)->exists())
                ->values();

            if ($conflicts->isNotEmpty()) {
                throw new InvalidArgumentException(
                    'Ya existen ' . $conflicts->count() . ' receta(s) en el sistema: '
                    . $conflicts->implode(', ') . '. Autoriza la sobreescritura para reemplazarlas.'
                );
            }
        }

        return DB::transaction(function () use ($entries) {
            return collect($entries)
                ->map(fn ($entry) => $this->importRecipe($entry, true))
                ->all();
        });
    }
}
