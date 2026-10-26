<?php

use Livewire\Volt\Component;
use App\Models\Item;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';

    public ?string $type = null;

    public function filterBy(?string $type = null): void
    {
        $this->type = $this->type === $type ? null : $type;
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        return [
            'items' => Item::query()
                ->with('recipe')
                ->when($this->type, fn ($query) => $query->where('type', $this->type))
                ->where(function ($query) {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('type', 'like', "%{$this->search}%")
                        ->orWhere('category', 'like', "%{$this->search}%");
                })
                ->orderBy('type')
                ->orderBy('category')
                ->orderBy('name')
                ->paginate(15),
            'headers' => [
                ['key' => 'name', 'label' => 'Nombre'],
                ['key' => 'category', 'label' => 'Categoría'],
                ['key' => 'type', 'label' => 'Tipo'],
                ['key' => 'purchase_price', 'label' => 'Costo Compra'],
                ['key' => 'purchase_unit', 'label' => 'U. Compra'],
                ['key' => 'recipe_unit', 'label' => 'U. Receta'],
                ['key' => 'conversion_factor', 'label' => 'Conv.'],
            ]
        ];
    }

    public function delete(Item $item)
    {
        $item->delete();
    }
}; ?>

<div>
    <!-- HEADER -->
    <x-header title="Ítems y Costos" subtitle="Gestiona materias primas, mano de obra y otros costos" separator
        icon="lucide.shopping-basket" icon-classes="w-9 h-9 text-emerald-600 bg-emerald-100 rounded-xl p-2">
        <x-slot:middle class="!justify-end">
            <x-input placeholder="Buscar..." wire:model.live.debounce="search" clearable icon="lucide.search" />
        </x-slot:middle>
        <x-slot:actions>
            <x-button icon="lucide.plus" label="Nuevo Ítem" class="btn-primary" link="/items/create" />
        </x-slot:actions>
    </x-header>

    <!-- FILTERS -->
    @php
        $filters = [
            ['value' => null, 'label' => 'Todos', 'icon' => 'lucide.list-filter', 'color' => 'badge-neutral'],
            ['value' => 'ingredient', 'label' => 'Materia Prima', 'icon' => 'lucide.carrot', 'color' => 'badge-success'],
            ['value' => 'labor', 'label' => 'Mano de Obra', 'icon' => 'lucide.hard-hat', 'color' => 'badge-info'],
            ['value' => 'packaging', 'label' => 'Empaque', 'icon' => 'lucide.package', 'color' => 'badge-secondary'],
            ['value' => 'direct_cost', 'label' => 'Costo Directo', 'icon' => 'lucide.flame', 'color' => 'badge-accent'],
            ['value' => 'overhead', 'label' => 'Costo Indirecto', 'icon' => 'lucide.receipt-text', 'color' => 'badge-warning'],
            ['value' => 'preparation', 'label' => 'Preparaciones', 'icon' => 'lucide.cooking-pot', 'color' => 'badge-primary'],
        ];
    @endphp

    <div class="flex flex-wrap gap-2 mb-4">
        @foreach ($filters as $filter)
            @php
                $active = $this->type === $filter['value'];
                $action = $filter['value'] ? "filterBy('{$filter['value']}')" : 'filterBy';
            @endphp
            <button type="button" wire:click="{!! $action !!}"
                wire:key="filter-{{ $filter['value'] ?? 'all' }}" aria-pressed="{{ $active ? 'true' : 'false' }}"
                title="{{ $active && $filter['value'] ? 'Quitar filtro' : 'Filtrar por ' . $filter['label'] }}"
                class="badge {{ $filter['color'] }} {{ $active ? 'shadow-sm' : 'badge-soft opacity-60 hover:opacity-100' }} gap-1.5 py-3 px-3 cursor-pointer transition-all duration-150">
                <x-icon :name="$filter['icon']" class="w-4 h-4" />
                {{ $filter['label'] }}
            </button>
        @endforeach
    </div>

    <!-- TABLE -->
    <x-card>
        <x-table :headers="$headers" :rows="$items" with-pagination link="/items/{id}/edit" show-empty-text
            empty-text="{{ $type || $search ? 'Sin resultados para los filtros seleccionados.' : 'Sin resultados. Crea tu primer ítem con el botón Nuevo Ítem.' }}">
            @scope('cell_name', $item)
                <div class="flex items-center gap-2.5">
                    @if($item->type === 'ingredient')
                        <span class="bg-emerald-100 text-emerald-600 rounded-lg p-1.5 inline-flex">
                            <x-icon name="lucide.carrot" class="w-4 h-4" />
                        </span>
                    @elseif($item->type === 'labor')
                        <span class="bg-sky-100 text-sky-600 rounded-lg p-1.5 inline-flex">
                            <x-icon name="lucide.hard-hat" class="w-4 h-4" />
                        </span>
                    @elseif($item->type === 'packaging')
                        <span class="bg-violet-100 text-violet-600 rounded-lg p-1.5 inline-flex">
                            <x-icon name="lucide.package" class="w-4 h-4" />
                        </span>
                    @elseif($item->type === 'direct_cost')
                        <span class="bg-teal-100 text-teal-600 rounded-lg p-1.5 inline-flex">
                            <x-icon name="lucide.flame" class="w-4 h-4" />
                        </span>
                    @elseif($item->type === 'preparation')
                        <span class="bg-indigo-100 text-indigo-600 rounded-lg p-1.5 inline-flex">
                            <x-icon name="lucide.cooking-pot" class="w-4 h-4" />
                        </span>
                    @else
                        <span class="bg-amber-100 text-amber-600 rounded-lg p-1.5 inline-flex">
                            <x-icon name="lucide.receipt-text" class="w-4 h-4" />
                        </span>
                    @endif
                    <span class="font-semibold">{{ $item->name }}</span>
                </div>
            @endscope

            @scope('cell_type', $item)
                @if($item->type === 'ingredient')
                    <x-badge value="Materia Prima" icon="lucide.carrot" class="badge-success badge-soft badge-sm whitespace-nowrap" />
                @elseif($item->type === 'labor')
                    <x-badge value="Mano de Obra" icon="lucide.hard-hat" class="badge-info badge-soft badge-sm whitespace-nowrap" />
                @elseif($item->type === 'packaging')
                    <x-badge value="Empaque" icon="lucide.package" class="badge-secondary badge-soft badge-sm whitespace-nowrap" />
                @elseif($item->type === 'direct_cost')
                    <x-badge value="Costo Directo" icon="lucide.flame" class="badge-accent badge-soft badge-sm whitespace-nowrap" />
                @elseif($item->type === 'preparation')
                    <x-badge value="Preparación" icon="lucide.cooking-pot" class="badge-primary badge-soft badge-sm whitespace-nowrap" />
                @else
                    <x-badge value="Costo Indirecto" icon="lucide.receipt-text" class="badge-warning badge-soft badge-sm whitespace-nowrap" />
                @endif
            @endscope

            @scope('cell_category', $item)
                <span class="inline-flex items-center gap-1.5 text-sm text-gray-500">
                    <x-icon name="lucide.tag" class="w-3.5 h-3.5 text-gray-400" />
                    {{ $item->category ?? '-' }}
                </span>
            @endscope

            @scope('cell_purchase_price', $item)
                @if($item->isPreparation())
                    <div class="flex flex-col">
                        <span class="inline-flex items-center gap-1 font-bold text-emerald-700">
                            <x-icon name="lucide.circle-dollar-sign" class="w-4 h-4 text-emerald-500" />
                            ${{ number_format($item->unitCost(), 2) }}
                        </span>
                        <span class="text-xs text-gray-500 ml-5">por {{ $item->recipe_unit ?? 'unidad' }}</span>
                    </div>
                @else
                    <span class="inline-flex items-center gap-1 font-bold text-emerald-700">
                        <x-icon name="lucide.circle-dollar-sign" class="w-4 h-4 text-emerald-500" />
                        ${{ number_format($item->purchase_price, 2) }}
                    </span>
                @endif
            @endscope

            @scope('cell_purchase_unit', $item)
                <span class="inline-flex items-center gap-1.5 text-sm">
                    <x-icon name="lucide.ruler" class="w-3.5 h-3.5 text-sky-500" />
                    {{ $item->purchase_unit ?? '-' }}
                </span>
            @endscope

            @scope('cell_recipe_unit', $item)
                <span class="inline-flex items-center gap-1.5 text-sm">
                    <x-icon name="lucide.cooking-pot" class="w-3.5 h-3.5 text-orange-500" />
                    {{ $item->recipe_unit ?? '-' }}
                </span>
            @endscope

            @scope('cell_conversion_factor', $item)
                @if($item->isPreparation())
                    <span class="inline-flex items-center gap-1.5 text-sm">
                        <x-icon name="lucide.layers" class="w-3.5 h-3.5 text-violet-500" />
                        Rinde {{ (float) $item->conversion_factor }} {{ $item->recipe_unit }}
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 text-sm font-mono">
                        <x-icon name="lucide.arrow-left-right" class="w-3.5 h-3.5 text-violet-500" />
                        {{ $item->conversion_factor }}
                    </span>
                @endif
            @endscope

        </x-table>
    </x-card>
</div>
