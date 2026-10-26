<?php

use Livewire\Volt\Component;
use App\Models\Item;
use Illuminate\Validation\Rule;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public ?Item $item = null;

    // Form fields
    public string $type = 'ingredient';
    public string $category = '';
    public string $name = '';
    public string $purchase_unit = '';
    public string $purchase_price = '0';
    public string $recipe_unit = '';
    public string $conversion_factor = '1';
    public ?string $overhead_type = null;

    public function mount(?Item $item = null)
    {
        if ($item && $item->exists) {
            $this->item = $item;
            $this->type = $item->type;
            $this->category = $item->category ?? '';
            $this->name = $item->name;
            $this->purchase_unit = $item->purchase_unit ?? '';
            $this->purchase_price = (string) $item->purchase_price;
            $this->recipe_unit = $item->recipe_unit ?? '';
            $this->conversion_factor = (string) $item->conversion_factor;
            $this->overhead_type = $item->overhead_type;
        }
    }

    public function save()
    {
        $this->validate([
            'type' => ['required', Rule::in(['ingredient', 'labor', 'packaging', 'direct_cost', 'overhead', 'preparation'])],
            'category' => 'nullable|string|max:100',
            'name' => 'required|string|max:255',
            'purchase_unit' => 'nullable|string|max:50',
            'purchase_price' => 'required|numeric|min:0',
            'recipe_unit' => 'nullable|string|max:50',
            'conversion_factor' => 'required|numeric|min:0.0001',
            'overhead_type' => [
                Rule::requiredIf($this->type === 'overhead'),
                'nullable',
                Rule::in(['percentage', 'fixed'])
            ]
        ]);

        // Preparation items are fully managed from their source recipe.
        if ($this->type === 'preparation') {
            if (! $this->item?->recipe) {
                $this->error(
                    'Las preparaciones se crean y administran desde su receta.',
                    position: 'toast-bottom'
                );

                return;
            }

            $this->item->update(['category' => $this->category]);

            return redirect()->route('items.index');
        }

        $data = [
            'type' => $this->type,
            'category' => $this->category,
            'name' => $this->name,
            'purchase_unit' => $this->purchase_unit,
            'purchase_price' => $this->purchase_price,
            'recipe_unit' => $this->recipe_unit,
            'conversion_factor' => $this->conversion_factor,
            'overhead_type' => $this->type === 'overhead' ? $this->overhead_type : null,
        ];

        if ($this->item && $this->item->exists) {
            $this->item->update($data);
        } else {
            Item::create($data);
        }

        return redirect()->route('items.index');
    }

    public function delete()
    {
        if ($this->item && $this->item->exists) {
            if ($this->item->isPreparation()) {
                $this->error(
                    'Las preparaciones se eliminan desactivando "Usar esta receta como ítem" en su receta.',
                    position: 'toast-bottom'
                );

                return;
            }

            $this->item->delete();
        }

        return redirect()->route('items.index');
    }

    public function getTypeOptions(): array
    {
        if ($this->type === 'preparation') {
            return [
                ['id' => 'preparation', 'name' => 'Preparación (desde receta)'],
            ];
        }

        return [
            ['id' => 'ingredient', 'name' => 'Materia Prima'],
            ['id' => 'labor', 'name' => 'Mano de Obra'],
            ['id' => 'packaging', 'name' => 'Empaque'],
            ['id' => 'direct_cost', 'name' => 'Costo Directo'],
            ['id' => 'overhead', 'name' => 'Costo Indirecto'],
        ];
    }

    public function getOverheadOptions(): array
    {
        return [
            ['id' => 'percentage', 'name' => 'Porcentaje (%)'],
            ['id' => 'fixed', 'name' => 'Valor Fijo'],
        ];
    }
}; ?>

<div>
    <x-header :title="isset($item) ? 'Editar Ítem' : 'Nuevo Ítem'" separator
        :icon="isset($item) ? 'lucide.pencil-line' : 'lucide.package-plus'"
        icon-classes="w-9 h-9 text-emerald-600 bg-emerald-100 rounded-xl p-2">
        <x-slot:actions>
            <x-button icon="lucide.arrow-left" label="Volver" link="/items" class="btn-ghost" />
        </x-slot:actions>
    </x-header>

    <x-card>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <x-select label="Tipo de Ítem" wire:model.live="type" :options="$this->getTypeOptions()" class="col-span-1 md:col-span-2" icon="lucide.shapes" />

            {{-- Preview del tipo elegido --}}
            <div class="col-span-1 md:col-span-2">
                @if($type === 'preparation')
                    <div class="flex items-center gap-3 bg-indigo-50 border border-indigo-100 rounded-xl p-3 text-sm text-indigo-800">
                        <x-icon name="lucide.cooking-pot" class="w-8 h-8 text-indigo-500 shrink-0" />
                        <span><strong>Preparación:</strong> producto elaborado por otra receta y usado como insumo. Su costo se calcula solo a partir de esa receta.</span>
                    </div>
                @elseif($type === 'ingredient')
                    <div class="flex items-center gap-3 bg-emerald-50 border border-emerald-100 rounded-xl p-3 text-sm text-emerald-800">
                        <x-icon name="lucide.carrot" class="w-8 h-8 text-emerald-500 shrink-0" />
                        <span><strong>Materia Prima:</strong> ingredientes que se transforman en tu receta (harina, azúcar, leche...).</span>
                    </div>
                @elseif($type === 'labor')
                    <div class="flex items-center gap-3 bg-sky-50 border border-sky-100 rounded-xl p-3 text-sm text-sky-800">
                        <x-icon name="lucide.hard-hat" class="w-8 h-8 text-sky-500 shrink-0" />
                        <span><strong>Mano de Obra:</strong> tiempo y trabajo humano por hora, turno o unidad producida.</span>
                    </div>
                @elseif($type === 'packaging')
                    <div class="flex items-center gap-3 bg-violet-50 border border-violet-100 rounded-xl p-3 text-sm text-violet-800">
                        <x-icon name="lucide.package" class="w-8 h-8 text-violet-500 shrink-0" />
                        <span><strong>Empaque:</strong> envases, etiquetas y presentación final del producto.</span>
                    </div>
                @elseif($type === 'direct_cost')
                    <div class="flex items-center gap-3 bg-teal-50 border border-teal-100 rounded-xl p-3 text-sm text-teal-800">
                        <x-icon name="lucide.flame" class="w-8 h-8 text-teal-500 shrink-0" />
                        <span><strong>Costo Directo:</strong> insumos consumidos directamente en la elaboración (gas de soplete, papel manteca, carbón, guantes...).</span>
                    </div>
                @else
                    <div class="flex items-center gap-3 bg-amber-50 border border-amber-100 rounded-xl p-3 text-sm text-amber-800">
                        <x-icon name="lucide.receipt-text" class="w-8 h-8 text-amber-500 shrink-0" />
                        <span><strong>Costo Indirecto:</strong> gas general, luz, arriendo u otros gastos prorrateados.</span>
                    </div>
                @endif
            </div>

            <x-input label="Categoría (Ej. Frutas, Condimentos)" wire:model="category" icon="lucide.tags" placeholder="Ej. Lacteos" />

            @if($type === 'preparation')
                <x-input label="Nombre" wire:model="name" icon="lucide.type" disabled
                    hint="El nombre se sincroniza desde la receta" />

                @if($item?->recipe)
                    <div class="col-span-1 md:col-span-2 rounded-xl border border-indigo-100 bg-gradient-to-br from-indigo-50/80 to-white p-4">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div class="space-y-1.5">
                                <p class="inline-flex items-center gap-2 text-sm font-semibold text-indigo-900">
                                    <x-icon name="lucide.cooking-pot" class="w-4 h-4 text-indigo-500" />
                                    Receta de origen:
                                    <a href="/recipes/{{ $item->recipe->id }}" class="underline hover:text-indigo-700">
                                        {{ $item->recipe->name }}
                                    </a>
                                </p>
                                <p class="text-sm text-gray-600">
                                    Rendimiento:
                                    <strong>{{ (float) $item->recipe->yield_quantity }} {{ $item->recipe->yield_unit }}</strong>
                                </p>
                                <p class="text-sm text-gray-600">
                                    Costo del lote:
                                    <strong>${{ number_format($item->batchCost(), 2) }}</strong>
                                </p>
                                <p class="text-sm text-gray-600">
                                    Costo por unidad:
                                    <strong class="text-emerald-700">${{ number_format($item->unitCost(), 2) }}</strong>
                                    por {{ $item->recipe_unit ?? 'unidad' }}
                                </p>
                            </div>
                            <x-button icon="lucide.arrow-up-right" label="Editar receta"
                                link="/recipes/{{ $item->recipe->id }}" class="btn-sm btn-outline border-indigo-200 text-indigo-700" />
                        </div>
                    </div>
                @endif
            @else
                <x-input label="Nombre" wire:model="name" icon="lucide.type" placeholder="Ej. Harina de trigo" />

                @if($type === 'overhead')
                    <x-select label="Tipo de Cálculo" wire:model="overhead_type" :options="$this->getOverheadOptions()" class="col-span-1 md:col-span-2" icon="lucide.calculator" />
                @endif

                <x-input label="Unidad de Compra (Ej. Kg)" wire:model="purchase_unit" icon="lucide.ruler" placeholder="Kg" />
                <x-input label="Costo de Compra ($)" wire:model="purchase_price" type="number" step="0.01" icon="lucide.circle-dollar-sign" />

                <x-input label="Unidad de Receta (Ej. Gr)" wire:model="recipe_unit" icon="lucide.cooking-pot" placeholder="Gr" />
                <x-input label="Factor de Conversión" wire:model="conversion_factor" type="number" step="0.0001" hint="1 U. Compra = X U. Receta" icon="lucide.arrow-left-right" />

                <div class="col-span-1 md:col-span-2 flex gap-3 text-sm text-amber-800 bg-amber-50 border border-amber-100 p-4 rounded-xl">
                    <x-icon name="lucide.lightbulb" class="w-6 h-6 text-amber-500 shrink-0 mt-0.5" />
                    <span><strong>Ejemplo:</strong> Si compras Harina por <strong>Kg</strong> a <strong>$1500</strong> y la usas en <strong>Gramos (Gr)</strong>, el factor de conversión es <strong>1000</strong>. (El sistema calculará automáticamente que 1 Gr cuesta $1.5).</span>
                </div>
            @endif
        </div>

        <x-slot:actions>
            @if(isset($item) && $item->exists && ! $item->isPreparation())
                <x-dropdown>
                    <x-slot:trigger>
                        <x-button label="Eliminar" class="btn-error btn-outline" icon="lucide.trash-2" />
                    </x-slot:trigger>
                    <div class="p-4 bg-base-100 rounded-box shadow w-64 border border-base-300">
                        <p class="text-sm mb-3 flex items-center gap-2">
                            <x-icon name="lucide.triangle-alert" class="w-5 h-5 text-error shrink-0" />
                            Estás seguro de que deseas eliminar este ítem?
                        </p>
                        <div class="flex justify-end gap-2">
                            <x-button label="Eliminar Definitivamente" class="btn-error w-full" wire:click="delete" icon="lucide.trash-2" />
                        </div>
                    </div>
                </x-dropdown>
            @endif
            <x-button label="Guardar" class="btn-primary" wire:click="save" icon="lucide.save" />
        </x-slot:actions>
    </x-card>
</div>
