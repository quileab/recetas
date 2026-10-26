<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Recipe;
use App\Services\PreparationService;
use App\Services\RecipeExportImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class PreparationTest extends TestCase
{
    use RefreshDatabase;

    private PreparationService $preparations;

    protected function setUp(): void
    {
        parent::setUp();

        $this->preparations = app(PreparationService::class);
    }

    public function test_recipe_can_be_published_as_a_preparation_item(): void
    {
        $flour = $this->ingredient('Harina', 1000, 1000);
        $bread = $this->recipe('Pan', 1000, 'g');
        $this->addItem($bread, $flour, 500);

        $item = $this->preparations->publish($bread);

        $this->assertSame('preparation', $item->type);
        $this->assertSame($bread->id, $item->recipe_id);
        $this->assertSame('g', $item->recipe_unit);
        $this->assertEqualsWithDelta(1000, $item->conversion_factor, 0.001);
        $this->assertEqualsWithDelta(0.5, $item->unitCost(), 0.001);
    }

    public function test_preparation_cost_includes_overhead_and_nests_into_parent_recipe(): void
    {
        $flour = $this->ingredient('Harina', 1000, 1000);

        $bread = $this->recipe('Pan', 1000, 'g');
        $this->addItem($bread, $flour, 500);

        $waste = Item::create([
            'type' => 'overhead',
            'name' => 'Merma 10%',
            'purchase_price' => 10,
            'overhead_type' => 'percentage',
            'recipe_unit' => '%',
            'conversion_factor' => 1,
        ]);
        $this->addItem($bread, $waste, 1);

        $breadItem = $this->preparations->publish($bread);

        $this->assertEqualsWithDelta(550, $bread->calculateTotalCost(), 0.01);
        $this->assertEqualsWithDelta(0.55, $breadItem->unitCost(), 0.001);

        $burger = $this->recipe('Hamburguesa');
        $this->addItem($burger, $breadItem, 80);

        $this->assertEqualsWithDelta(44, $burger->calculateDirectCost(), 0.01);
    }

    public function test_circular_dependencies_are_detected(): void
    {
        $bread = $this->recipe('Pan');
        $breadItem = $this->preparations->publish($bread);

        $burger = $this->recipe('Hamburguesa');
        $this->addItem($burger, $breadItem, 1);
        $burgerItem = $this->preparations->publish($burger);

        $this->assertTrue($burger->dependsOn($bread));
        $this->assertFalse($bread->dependsOn($burger));

        $this->assertTrue($this->preparations->wouldCreateCycle($burgerItem, $bread));
        $this->assertFalse($this->preparations->wouldCreateCycle($breadItem, $burger));
    }

    public function test_recipe_used_as_item_cannot_be_deleted(): void
    {
        $bread = $this->recipe('Pan');
        $breadItem = $this->preparations->publish($bread);

        $burger = $this->recipe('Hamburguesa');
        $this->addItem($burger, $breadItem, 1);

        $this->assertTrue($bread->isUsedAsItem());
        $this->assertFalse($bread->delete());
        $this->assertDatabaseHas('recipes', ['id' => $bread->id]);
    }

    public function test_export_includes_dependencies_and_import_restores_them(): void
    {
        $flour = $this->ingredient('Harina', 1000, 1000);

        $bread = $this->recipe('Pan', 1000, 'g');
        $this->addItem($bread, $flour, 500);
        $breadItem = $this->preparations->publish($bread);

        $burger = $this->recipe('Hamburguesa');
        $this->addItem($burger, $breadItem, 80);

        $service = app(RecipeExportImportService::class);
        $payload = $service->exportRecipe($burger);

        $this->assertSame('1.1', $payload['version']);
        $this->assertCount(1, $payload['dependencies']);
        $this->assertSame('Pan', $payload['dependencies'][0]['recipe']['name']);
        $this->assertEqualsWithDelta(1000, $payload['dependencies'][0]['recipe']['yield_quantity'], 0.001);

        DB::table('recipe_items')->delete();
        DB::table('stages')->delete();
        DB::table('items')->delete();
        DB::table('recipes')->delete();

        $imported = $service->importRecipe($payload);

        $this->assertSame('Hamburguesa', $imported->name);
        $this->assertSame(2, Recipe::count());

        $importedBread = Recipe::where('name', 'Pan')->firstOrFail();
        $this->assertNotNull($importedBread->item);
        $this->assertEqualsWithDelta(0.5, $importedBread->item->unitCost(), 0.001);
        $this->assertEqualsWithDelta(40, $imported->calculateDirectCost(), 0.01);
    }

    public function test_recipe_form_publishes_the_recipe_as_an_item(): void
    {
        Livewire::test('recipes.form')
            ->set('name', 'Pan')
            ->set('yield_quantity', '1000')
            ->set('yield_unit', 'g')
            ->set('use_as_item', true)
            ->call('save')
            ->assertHasNoErrors();

        $recipe = Recipe::where('name', 'Pan')->firstOrFail();
        $item = $recipe->item;

        $this->assertNotNull($item);
        $this->assertSame('preparation', $item->type);
        $this->assertSame('g', $item->recipe_unit);
        $this->assertEqualsWithDelta(1000, $item->conversion_factor, 0.001);
    }

    public function test_recipe_show_rejects_a_circular_preparation(): void
    {
        $bread = $this->recipe('Pan');
        $breadItem = $this->preparations->publish($bread);

        $burger = $this->recipe('Hamburguesa');
        $this->addItem($burger, $breadItem, 1);
        $burgerItem = $this->preparations->publish($burger);

        $breadStage = $bread->stages()->create([
            'name' => 'Etapa',
            'order' => 1,
            'duration_minutes' => 0,
        ]);

        $itemsBefore = DB::table('recipe_items')->count();

        Livewire::test('recipes.show', ['recipe' => $bread])
            ->call('addItemToStage', $breadStage->id)
            ->set('selected_item_id', $burgerItem->id)
            ->set('item_quantity', '1')
            ->call('saveRecipeItem');

        $this->assertSame($itemsBefore, DB::table('recipe_items')->count());
    }

    private function ingredient(string $name, float $price, float $conversion): Item
    {
        return Item::create([
            'type' => 'ingredient',
            'name' => $name,
            'purchase_unit' => 'Kg',
            'purchase_price' => $price,
            'recipe_unit' => 'g',
            'conversion_factor' => $conversion,
        ]);
    }

    private function recipe(string $name, float $yield = 1, string $unit = 'unidad'): Recipe
    {
        return Recipe::create([
            'name' => $name,
            'yield_quantity' => $yield,
            'yield_unit' => $unit,
        ]);
    }

    private function addItem(Recipe $recipe, Item $item, float $quantity): void
    {
        $stage = $recipe->stages()->create([
            'name' => 'Etapa única',
            'order' => 1,
            'duration_minutes' => 0,
        ]);

        $stage->recipeItems()->create([
            'recipe_id' => $recipe->id,
            'item_id' => $item->id,
            'quantity' => $quantity,
        ]);
    }
}
