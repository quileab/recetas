<?php

use Livewire\Volt\Component;
use App\Models\Recipe;
use App\Models\Stage;
use App\Models\Item;
use App\Models\RecipeItem;
use App\Services\PreparationService;
use App\Services\RecipeExportImportService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public Recipe $recipe;
    
    // Stage Form
    public bool $stageModal = false;
    public string $stage_name = '';
    public string $stage_duration = '0';
    
    // RecipeItem Form
    public bool $itemModal = false;
    public ?Stage $selectedStage = null;
    public ?string $selected_item_id = null;
    public string $item_quantity = '0';
    
    public Collection $availableItems;

    public function mount(Recipe $recipe)
    {
        $this->recipe = $recipe->load(['stages.recipeItems.item']);
        $this->availableItems = Item::orderBy('type')->orderBy('name')->get();
    }

    public function createStage()
    {
        $this->stage_name = '';
        $this->stage_duration = '0';
        $this->stageModal = true;
    }

    public function saveStage()
    {
        $this->validate([
            'stage_name' => 'required|string|max:255',
            'stage_duration' => 'required|numeric|min:0',
        ]);

        $this->recipe->stages()->create([
            'name' => $this->stage_name,
            'duration_minutes' => $this->stage_duration,
            'order' => $this->recipe->stages()->count() + 1,
        ]);

        $this->stageModal = false;
        $this->recipe->refresh();
    }
    
    public function deleteStage(Stage $stage)
    {
        $stage->delete();
        $this->recipe->refresh();
    }

    public function addItemToStage(Stage $stage)
    {
        $this->selectedStage = $stage;
        $this->selected_item_id = null;
        $this->item_quantity = '0';
        $this->itemModal = true;
    }

    public function saveRecipeItem(PreparationService $preparations)
    {
        $this->validate([
            'selected_item_id' => 'required|exists:items,id',
            'item_quantity' => 'required|numeric|min:0.01',
        ]);

        $item = Item::with('recipe')->find($this->selected_item_id);

        if ($item && $preparations->wouldCreateCycle($item, $this->recipe)) {
            $this->error(
                "No puedes usar '{$item->name}' aquí: generaría una dependencia circular entre recetas.",
                position: 'toast-bottom'
            );

            return;
        }

        $this->selectedStage->recipeItems()->create([
            'recipe_id' => $this->recipe->id,
            'item_id' => $this->selected_item_id,
            'quantity' => $this->item_quantity,
        ]);

        $this->itemModal = false;
        $this->recipe->refresh();
    }
    
    public function deleteRecipeItem(RecipeItem $recipeItem)
    {
        $recipeItem->delete();
        $this->recipe->refresh();
    }
    
    public function calculateItemCost(RecipeItem $recipeItem, ?float $directCost = null)
    {
        return $this->recipe->calculateItemCost($recipeItem, $directCost);
    }

    public function export(RecipeExportImportService $service)
    {
        $data = $service->exportRecipe($this->recipe);
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $slug = Str::slug($this->recipe->name);

        return response()->streamDownload(function () use ($json) {
            echo $json;
        }, "receta-{$slug}.json", [
            'Content-Type' => 'application/json',
        ]);
    }
}; ?>

<div>
    <x-header :title="$recipe->name" subtitle="Fórmula y Etapas" separator
        icon="lucide.cooking-pot" icon-classes="w-9 h-9 text-orange-600 bg-orange-100 rounded-xl p-2">
        <x-slot:actions>
            <x-button icon="lucide.arrow-left" label="Volver" link="/recipes" class="btn-ghost" />
            <x-button icon="lucide.download" class="btn-square btn-ghost text-amber-600 hover:bg-amber-50"
                wire:click="export" tooltip="Exportar receta en JSON" />
            <x-button icon="lucide.plus" label="Nueva Etapa" class="btn-primary" wire:click="createStage" />
        </x-slot:actions>
    </x-header>

    @php
        $recipeDirectCost = $recipe->calculateDirectCost();
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <div class="lg:col-span-2 space-y-6">
            @forelse($recipe->stages as $index => $stage)
                <x-card class="shadow-sm border border-base-300 overflow-hidden">
                    <x-slot:title>
                        <div class="flex items-center gap-2.5">
                            <span class="rounded-xl p-2 inline-flex {{ $index % 3 === 0 ? 'bg-orange-100 text-orange-600' : ($index % 3 === 1 ? 'bg-emerald-100 text-emerald-600' : 'bg-sky-100 text-sky-600') }}">
                                <x-icon name="lucide.flame" class="w-5 h-5" />
                            </span>
                            <span>{{ $stage->name }}</span>
                        </div>
                    </x-slot:title>
                    <x-slot:menu>
                        <x-badge :value="$stage->duration_minutes . ' min.'" icon="lucide.timer" class="badge-ghost badge-soft whitespace-nowrap" />
                        <x-button icon="lucide.plus-circle" class="btn-sm btn-primary btn-soft" wire:click="addItemToStage({{ $stage->id }})" tooltip="Agregar Ítem a esta etapa" />
                        <x-button icon="lucide.trash-2" class="btn-sm btn-ghost text-error" wire:click="deleteStage({{ $stage->id }})" wire:confirm="Eliminar esta etapa y sus ítems?" tooltip="Eliminar etapa" />
                    </x-slot:menu>
                    
                    <div class="overflow-x-auto">
                        <table class="table w-full table-zebra">
                            <thead>
                                <tr>
                                    <th><span class="inline-flex items-center gap-1.5"><x-icon name="lucide.carrot" class="w-4 h-4 text-emerald-500" />Ítem</span></th>
                                    <th><span class="inline-flex items-center gap-1.5"><x-icon name="lucide.shapes" class="w-4 h-4 text-violet-500" />Tipo</span></th>
                                    <th><span class="inline-flex items-center gap-1.5"><x-icon name="lucide.scale" class="w-4 h-4 text-sky-500" />Cantidad</span></th>
                                    <th><span class="inline-flex items-center gap-1.5"><x-icon name="lucide.piggy-bank" class="w-4 h-4 text-emerald-600" />Costo Est.</span></th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($stage->recipeItems as $ri)
                                    <tr>
                                        <td>
                                            <span class="font-medium">{{ $ri->item->name }}</span>
                                            @if($ri->item->isPreparation() && $ri->item->recipe)
                                                <a href="/recipes/{{ $ri->item->recipe->id }}"
                                                    class="inline-flex items-center gap-1 text-xs text-orange-600 hover:text-orange-700 hover:underline">
                                                    <x-icon name="lucide.arrow-up-right" class="w-3 h-3" />
                                                    ver receta
                                                </a>
                                            @endif
                                        </td>
                                        <td>
                                            @if($ri->item->type === 'ingredient')
                                                <x-badge value="Materia Prima" icon="lucide.carrot" class="badge-sm badge-success badge-soft" />
                                            @elseif($ri->item->type === 'labor')
                                                <x-badge value="Mano de Obra" icon="lucide.hard-hat" class="badge-sm badge-info badge-soft" />
                                            @elseif($ri->item->type === 'packaging')
                                                <x-badge value="Empaque" icon="lucide.package" class="badge-sm badge-secondary badge-soft" />
                                            @elseif($ri->item->type === 'direct_cost')
                                                <x-badge value="Costo Directo" icon="lucide.flame" class="badge-sm badge-accent badge-soft" />
                                            @elseif($ri->item->type === 'preparation')
                                                <x-badge value="Preparación" icon="lucide.cooking-pot" class="badge-sm badge-primary badge-soft" />
                                            @else
                                                <x-badge value="Costo Indirecto" icon="lucide.receipt-text" class="badge-sm badge-warning badge-soft" />
                                            @endif
                                        </td>
                                        <td><span class="font-mono">{{ (float)$ri->quantity }} {{ $ri->item->recipe_unit }}</span></td>
                                        <td>
                                            @php
                                                $itemCost = $this->calculateItemCost($ri, $recipeDirectCost);
                                            @endphp
                                            <span class="font-bold text-emerald-700">${{ number_format($itemCost, 2) }}</span>
                                            @if($ri->item->type === 'overhead' && $ri->item->overhead_type === 'percentage')
                                                <span class="text-xs text-amber-700 block font-normal">({{ $ri->item->purchase_price }}% de ${{ number_format($recipeDirectCost, 2) }})</span>
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            <x-button icon="lucide.x" class="btn-xs btn-ghost text-error" wire:click="deleteRecipeItem({{ $ri->id }})" tooltip="Quitar ítem" />
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5">
                                            <div class="flex items-center justify-center gap-2 text-sm text-gray-400 py-2">
                                                 <x-icon name="lucide.shopping-basket" class="w-4 h-4" />
                                                 No hay ítems en esta etapa. Agrega el primero con el botón +.
                                             </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-card>
            @empty
                <div class="text-center p-10 bg-gradient-to-br from-amber-50 to-orange-50 border border-orange-100 rounded-2xl">
                    <span class="bg-white rounded-2xl p-4 inline-flex shadow-sm mb-3">
                        <x-icon name="lucide.cooking-pot" class="w-12 h-12 text-orange-400" />
                    </span>
                    <p class="font-semibold text-orange-900">No hay etapas creadas.</p>
                    <p class="text-sm text-orange-700/70 mb-2">Divide tu receta en pasos: mezcla, coccion, reposo...</p>
                    <x-button label="Crear Primera Etapa" icon="lucide.plus" class="btn-primary mt-4" wire:click="createStage" />
                </div>
            @endforelse
        </div>
        
        <!-- RESUMEN -->
        <div class="lg:col-span-1">
            <x-card class="bg-gradient-to-b from-white to-amber-50/60 border border-orange-100">
                <x-slot:title>
                    <div class="flex items-center gap-2">
                        <span class="bg-emerald-100 text-emerald-600 rounded-xl p-2 inline-flex">
                            <x-icon name="lucide.wallet" class="w-5 h-5" />
                        </span>
                        Resumen de Costos
                    </div>
                </x-slot:title>
                @php
                    $totalRecipeCost = $recipe->calculateTotalCost();
                    $overheadCost = $recipe->calculateOverheadCost($recipeDirectCost);
                    $unitCost = $recipe->calculateUnitCost();
                @endphp
                <div class="flex items-center gap-2 text-4xl font-bold text-emerald-700 mb-1">
                    <x-icon name="lucide.circle-dollar-sign" class="w-8 h-8 text-emerald-500" />
                    ${{ number_format($totalRecipeCost, 2) }}
                </div>
                <p class="text-xs text-gray-500 mb-4">
                    ${{ number_format($unitCost, 2) }} por {{ $recipe->yield_unit }}
                    <span class="text-gray-400">·</span>
                    rinde {{ (float) $recipe->yield_quantity }} {{ $recipe->yield_unit }}
                </p>

                @if($recipe->isPreparation())
                    <a href="/items/{{ $recipe->item->id }}/edit"
                        class="inline-flex items-center gap-1.5 mb-4 text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-100 rounded-full px-3 py-1 hover:bg-emerald-100 transition-colors">
                        <x-icon name="lucide.cooking-pot" class="w-3.5 h-3.5" />
                        Publicada como ítem
                        <x-icon name="lucide.arrow-up-right" class="w-3 h-3" />
                    </a>
                @endif

                <ul class="space-y-2.5 text-sm">
                    <li class="flex justify-between items-center bg-white/70 rounded-lg px-3 py-2 border border-base-200">
                        <span class="inline-flex items-center gap-2 text-gray-600">
                            <x-icon name="lucide.coins" class="w-4 h-4 text-emerald-600" /> Costo Directo:
                        </span>
                        <span class="font-bold text-emerald-700">${{ number_format($recipeDirectCost, 2) }}</span>
                    </li>
                    <li class="flex justify-between items-center bg-white/70 rounded-lg px-3 py-2 border border-base-200">
                        <span class="inline-flex items-center gap-2 text-gray-600">
                            <x-icon name="lucide.receipt-text" class="w-4 h-4 text-amber-500" /> Costo Indirecto (Overhead):
                        </span>
                        <span class="font-bold text-amber-700">${{ number_format($overheadCost, 2) }}</span>
                    </li>
                    <li class="flex justify-between items-center bg-white/70 rounded-lg px-3 py-2 border border-base-200">
                        <span class="inline-flex items-center gap-2 text-gray-600">
                            <x-icon name="lucide.layers" class="w-4 h-4 text-orange-500" /> Etapas:
                        </span>
                        <span class="font-bold">{{ $recipe->stages->count() }}</span>
                    </li>
                    <li class="flex justify-between items-center bg-white/70 rounded-lg px-3 py-2 border border-base-200">
                        <span class="inline-flex items-center gap-2 text-gray-600">
                            <x-icon name="lucide.timer" class="w-4 h-4 text-sky-500" /> Tiempo Total:
                        </span>
                        <span class="font-bold">{{ $recipe->stages->sum('duration_minutes') }} min</span>
                    </li>
                    <li class="flex justify-between items-center bg-white/70 rounded-lg px-3 py-2 border border-base-200">
                        <span class="inline-flex items-center gap-2 text-gray-600">
                            <x-icon name="lucide.shopping-basket" class="w-4 h-4 text-emerald-500" /> Ítems Totales:
                        </span>
                        <span class="font-bold">{{ $recipe->stages->flatMap->recipeItems->count() }}</span>
                    </li>
                    <li class="flex justify-between items-center bg-white/70 rounded-lg px-3 py-2 border border-base-200">
                        <span class="inline-flex items-center gap-2 text-gray-600">
                            <x-icon name="lucide.layers" class="w-4 h-4 text-orange-500" /> Rendimiento:
                        </span>
                        <span class="font-bold">{{ (float) $recipe->yield_quantity }} {{ $recipe->yield_unit }}</span>
                    </li>
                </ul>
            </x-card>
        </div>
    </div>

    <!-- MODAL ETAPA -->
    <x-modal wire:model="stageModal" title="Nueva Etapa" subtitle="Ej. Coccion, Mezcla, Horneado..." separator>
        <div class="grid grid-cols-1 gap-4">
            <x-input label="Nombre de la Etapa (Ej. Coccion)" wire:model="stage_name" icon="lucide.flame" placeholder="Ej. Coccion lenta" />
            <x-input label="Duracion (Minutos)" wire:model="stage_duration" type="number" icon="lucide.timer" />
        </div>
        <x-slot:actions>
            <x-button label="Cancelar" wire:click="stageModal = false" class="btn-ghost" icon="lucide.x" />
            <x-button label="Guardar" class="btn-primary" wire:click="saveStage" icon="lucide.save" />
        </x-slot:actions>
    </x-modal>

    <!-- MODAL ITEM -->
    <x-modal wire:model="itemModal" title="Agregar Ítem" subtitle="Busca y define la cantidad exacta" separator>
        <div class="grid grid-cols-1 gap-4">
            <x-choices label="Selecciona un Ítem" wire:model.live="selected_item_id" 
                :options="$availableItems" option-label="name" option-value="id" 
                placeholder="Busca por nombre..." single searchable icon="lucide.search" />
            
            @if($selected_item_id)
                @php 
                    $selItem = $availableItems->firstWhere('id', $selected_item_id); 
                @endphp
                <x-input label="Cantidad (en {{ $selItem->recipe_unit ?? 'unidades' }})" wire:model="item_quantity" type="number" step="0.01" icon="lucide.scale" />
                
                @if($selItem->isPreparation())
                    <div class="flex items-start gap-2 text-sm text-emerald-800 bg-emerald-50 border border-emerald-100 p-3 rounded-xl">
                        <x-icon name="lucide.cooking-pot" class="w-5 h-5 text-emerald-500 shrink-0 mt-0.5" />
                        <div class="space-y-1">
                            <p>
                                Preparación elaborada con la receta
                                <a href="/recipes/{{ $selItem->recipe?->id }}" class="font-semibold underline hover:text-emerald-900">
                                    {{ $selItem->recipe?->name ?? 'desconocida' }}
                                </a>.
                            </p>
                            <p>
                                Costo calculado: <strong>${{ number_format($selItem->unitCost(), 2) }}</strong>
                                por {{ $selItem->recipe_unit ?? 'unidad' }}.
                            </p>
                        </div>
                    </div>
                @elseif($selItem->type === 'overhead' && $selItem->overhead_type === 'fixed')
                    <div class="flex items-center gap-2 text-sm text-sky-800 bg-sky-50 border border-sky-100 p-3 rounded-xl">
                        <x-icon name="lucide.info" class="w-5 h-5 text-sky-500 shrink-0" />
                        Este ítem tiene un costo fijo de ${{ $selItem->purchase_price }}.
                    </div>
                @elseif($selItem->type === 'overhead' && $selItem->overhead_type === 'percentage')
                    <div class="flex items-center gap-2 text-sm text-amber-800 bg-amber-50 border border-amber-100 p-3 rounded-xl">
                        <x-icon name="lucide.percent" class="w-5 h-5 text-amber-500 shrink-0" />
                        Aplica {{ $selItem->purchase_price }}% sobre el total de costos directos de la receta.
                    </div>
                @endif
            @endif
        </div>
        <x-slot:actions>
            <x-button label="Cancelar" wire:click="itemModal = false" class="btn-ghost" icon="lucide.x" />
            <x-button label="Agregar" class="btn-primary" wire:click="saveRecipeItem" icon="lucide.plus" />
        </x-slot:actions>
    </x-modal>
</div>
