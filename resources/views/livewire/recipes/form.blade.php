<?php

use Livewire\Volt\Component;
use App\Models\Recipe;
use App\Services\PreparationService;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public ?Recipe $recipe = null;

    public string $name = '';
    public string $description = '';
    public string $yield_quantity = '1';
    public string $yield_unit = 'unidad';
    public bool $use_as_item = false;

    public function mount(?Recipe $recipe = null)
    {
        if ($recipe && $recipe->exists) {
            $this->recipe = $recipe;
            $this->name = $recipe->name;
            $this->description = $recipe->description ?? '';
            $this->yield_quantity = (string) $recipe->yield_quantity;
            $this->yield_unit = $recipe->yield_unit ?? 'unidad';
            $this->use_as_item = $recipe->item()->exists();
        }
    }

    public function save(PreparationService $preparations)
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'yield_quantity' => 'required|numeric|min:0.001',
            'yield_unit' => 'required|string|max:50',
        ]);

        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'yield_quantity' => $this->yield_quantity,
            'yield_unit' => $this->yield_unit,
        ];

        if ($this->recipe && $this->recipe->exists) {
            $this->recipe->update($data);
        } else {
            $this->recipe = Recipe::create($data);
        }

        if ($this->use_as_item) {
            $preparations->publish($this->recipe->refresh());
        } elseif ($this->recipe->item()->exists() && ! $preparations->unpublish($this->recipe)) {
            $this->use_as_item = true;

            $this->error(
                'Esta preparación está siendo utilizada por otras recetas. Quita primero su uso antes de despublicarla.',
                position: 'toast-bottom'
            );

            return;
        }

        // On save, redirect to the show view to manage stages
        return redirect()->route('recipes.show', $this->recipe->id);
    }

    public function delete()
    {
        if ($this->recipe && $this->recipe->exists) {
            if ($this->recipe->isUsedAsItem()) {
                $this->error(
                    'No se puede eliminar: esta preparación se usa como ítem en otras recetas.',
                    position: 'toast-bottom'
                );

                return;
            }

            $this->recipe->delete();
        }

        return redirect()->route('recipes.index');
    }
}; ?>

<div>
    <x-header :title="isset($recipe) ? 'Editar Receta' : 'Nueva Receta'" subtitle="Dale nombre y una descripción antojable a tu creación" separator
        :icon="isset($recipe) ? 'lucide.notebook-pen' : 'lucide.cooking-pot'"
        icon-classes="w-9 h-9 text-orange-600 bg-orange-100 rounded-xl p-2">
        <x-slot:actions>
            <x-button icon="lucide.arrow-left" label="Volver" link="/recipes" class="btn-ghost" />
        </x-slot:actions>
    </x-header>

    <x-card>
        <div class="grid grid-cols-1 gap-6">
            <x-input label="Nombre de la Receta" wire:model="name" icon="lucide.chef-hat" placeholder="Ej. Pan amasado, Mermelada de mora..." />
            <x-textarea label="Descripción" wire:model="description" rows="3" placeholder="Cuenta que la hace especial..." />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <x-input label="Rendimiento del lote" wire:model="yield_quantity" type="number" step="0.001" min="0.001"
                    icon="lucide.layers" hint="Cuánto produce esta receta en total" placeholder="Ej. 1000" />
                <x-input label="Unidad de rendimiento" wire:model="yield_unit" icon="lucide.ruler"
                    hint="Unidad en la que se consume" placeholder="Ej. g, unidad, porción" />
            </div>

            <x-toggle label="Usar esta receta como ítem" wire:model.live="use_as_item"
                hint="Permite consumir esta preparación dentro de otras recetas" />

            @if($use_as_item)
                <div class="flex items-start gap-3 bg-emerald-50 border border-emerald-100 rounded-xl p-4 text-sm text-emerald-800">
                    <x-icon name="lucide.cooking-pot" class="w-6 h-6 text-emerald-500 shrink-0 mt-0.5" />
                    <div class="space-y-1">
                        <p><strong>Preparación publicada como ítem.</strong> Su costo se calcula automáticamente:</p>
                        <p class="font-mono text-xs bg-white/70 rounded-lg px-2 py-1 inline-block">
                            costo total del lote / {{ $yield_quantity ?: '?' }} {{ $yield_unit ?: 'unidad' }}
                        </p>
                        <p>Si cambia el precio de cualquier insumo, el costo se actualiza solo.</p>
                    </div>
                </div>
            @endif
        </div>

        <div class="mt-4 flex items-center gap-3 bg-orange-50 border border-orange-100 rounded-xl p-3 text-sm text-orange-800">
            <x-icon name="lucide.flame" class="w-6 h-6 text-orange-500 shrink-0" />
            <span>Después de guardar podrás agregar <strong>etapas</strong> e <strong>ingredientes</strong> a tu receta.</span>
        </div>

        <x-slot:actions>
            @if(isset($recipe) && $recipe->exists)
                <x-dropdown>
                    <x-slot:trigger>
                        <x-button label="Eliminar" class="btn-error btn-outline" icon="lucide.trash-2" />
                    </x-slot:trigger>
                    <div class="p-4 bg-base-100 rounded-box shadow w-64 border border-base-300">
                        <p class="text-sm mb-3 flex items-center gap-2">
                            <x-icon name="lucide.triangle-alert" class="w-5 h-5 text-error shrink-0" />
                            Estás seguro de que deseas eliminar esta receta y todas sus etapas?
                        </p>
                        <div class="flex justify-end gap-2">
                            <x-button label="Eliminar Definitivamente" class="btn-error w-full" wire:click="delete" icon="lucide.trash-2" />
                        </div>
                    </div>
                </x-dropdown>
            @endif
            <x-button label="Guardar y Continuar" class="btn-primary" wire:click="save" icon="lucide.save" />
        </x-slot:actions>
    </x-card>
</div>
