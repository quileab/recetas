<?php

use Livewire\Volt\Component;
use App\Models\Recipe;
use App\Services\RecipeExportImportService;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;
use Illuminate\Support\Str;

new class extends Component {
    use WithPagination;
    use WithFileUploads;
    use Toast;

    public string $search = '';

    // Bulk selection
    public array $selection = [];

    // Import modal state
    public bool $importModal = false;
    public $importFile = null;
    public ?array $previewData = null;
    public string $importError = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->reset('selection');
    }

    public function clearSelection(): void
    {
        $this->selection = [];
    }

    public function with(): array
    {
        return [
            'recipes' => Recipe::where('name', 'like', "%{$this->search}%")
                ->orderBy('name')
                ->paginate(10),
            'headers' => [
                ['key' => 'name', 'label' => 'Nombre de la Receta'],
                ['key' => 'description', 'label' => 'Descripción'],
            ]
        ];
    }

    public function delete(Recipe $recipe)
    {
        if ($recipe->isUsedAsItem()) {
            $this->error('No se puede eliminar: esta preparación se usa como ítem en otras recetas.');
            return;
        }

        $recipe->delete();
        $this->success('Receta eliminada exitosamente.');
    }

    public function export(int $recipeId, RecipeExportImportService $service)
    {
        $recipe = Recipe::findOrFail($recipeId);
        $data = $service->exportRecipe($recipe);
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $slug = Str::slug($recipe->name);

        return response()->streamDownload(function () use ($json) {
            echo $json;
        }, "receta-{$slug}.json", [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Export every selected recipe into a single portable file.
     */
    public function exportSelected(RecipeExportImportService $service)
    {
        $recipes = Recipe::whereIn('id', $this->selection)
            ->with(['stages.recipeItems.item'])
            ->orderBy('name')
            ->get();

        if ($recipes->isEmpty()) {
            $this->error('No hay recetas seleccionadas para exportar.');
            return;
        }

        $data = $service->exportRecipes($recipes);
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $fileName = $recipes->count() === 1
            ? 'receta-' . Str::slug($recipes->first()->name) . '.json'
            : 'recetas-' . $recipes->count() . '-' . now()->format('Ymd-His') . '.json';

        $this->clearSelection();

        return response()->streamDownload(function () use ($json) {
            echo $json;
        }, $fileName, [
            'Content-Type' => 'application/json',
        ]);
    }

    public function openImportModal()
    {
        $this->resetImport();
        $this->importModal = true;
    }

    public function resetImport()
    {
        $this->importFile = null;
        $this->previewData = null;
        $this->importError = '';
    }

    public function updatedImportFile(RecipeExportImportService $service)
    {
        $this->importError = '';
        $this->previewData = null;

        $this->validate([
            'importFile' => 'required|file|max:5120', // max 5MB
        ]);

        try {
            $content = file_get_contents($this->importFile->getRealPath());
            $this->previewData = $service->inspectJsonContent($content);
        } catch (\Throwable $e) {
            $this->importError = $e->getMessage();
        }
    }

    public function executeImport(RecipeExportImportService $service, bool $overwrite = false)
    {
        if (!$this->previewData || empty($this->previewData['raw_data'])) {
            $this->error('No hay datos válidos de receta para importar.');
            return;
        }

        try {
            $isCollection = ($this->previewData['mode'] ?? 'single') === 'collection';

            if ($isCollection) {
                $recipes = $service->importRecipes($this->previewData['raw_data'], $overwrite);
                $total = count($recipes);

                $this->importModal = false;
                $this->resetImport();

                $this->success(
                    $total === 1
                        ? "Se importó 1 receta correctamente."
                        : "Se importaron {$total} recetas correctamente."
                );

                return;
            }

            $recipe = $service->importRecipe($this->previewData['raw_data'], $overwrite);
            $this->importModal = false;
            $this->resetImport();

            if ($overwrite) {
                $this->success("Receta '{$recipe->name}' sobreescrita correctamente.");
            } else {
                $this->success("Receta '{$recipe->name}' importada correctamente.");
            }
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
        }
    }
}; ?>

<div>
    <x-header title="Recetas" subtitle="Administra tus recetas y sus formulaciones" separator
        icon="lucide.soup" icon-classes="w-9 h-9 text-orange-600 bg-orange-100 rounded-xl p-2">
        <x-slot:middle class="!justify-end">
            <x-input placeholder="Buscar receta..." wire:model.live.debounce="search" clearable icon="lucide.search" />
        </x-slot:middle>
        <x-slot:actions>
            <x-button icon="lucide.upload" label="Importar Receta" class="btn-outline border-orange-200 text-orange-700 hover:bg-orange-50 hover:border-orange-300" wire:click="openImportModal" />
            <x-button icon="lucide.plus" label="Nueva Receta" class="btn-primary" link="/recipes/create" />
        </x-slot:actions>
    </x-header>

    {{-- BARRA DE ACCIONES MASIVAS --}}
    @if(count($selection))
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4 px-4 py-3 rounded-xl bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200">
            <div class="flex items-center gap-2.5 text-amber-900">
                <span class="bg-amber-500 text-white rounded-lg p-1.5 inline-flex">
                    <x-icon name="lucide.check-check" class="w-4 h-4" />
                </span>
                <span class="font-semibold text-sm">
                    {{ count($selection) }} {{ count($selection) === 1 ? 'receta seleccionada' : 'recetas seleccionadas' }}
                </span>
            </div>
            <div class="flex items-center gap-2">
                <x-button icon="lucide.x" label="Limpiar" class="btn-xs btn-ghost" wire:click="clearSelection" />
                <x-button icon="lucide.download" label="Exportar JSON" class="btn-sm btn-primary" wire:click="exportSelected"
                    wire:loading.attr="disabled" wire:target="exportSelected" />
            </div>
        </div>
    @endif

    <x-card>
        <x-table :headers="$headers" :rows="$recipes" with-pagination selectable wire:model="selection"
            show-empty-text empty-text="Aún no tienes recetas. Crea la primera con el botón Nueva Receta o importa un archivo JSON.">
            @scope('cell_name', $recipe)
                <div class="flex items-center gap-2.5">
                    <span class="bg-gradient-to-br from-amber-100 to-orange-100 text-orange-600 rounded-lg p-1.5 inline-flex ring-1 ring-orange-200/60">
                        <x-icon name="lucide.soup" class="w-4 h-4" />
                    </span>
                    <span class="font-semibold">{{ $recipe->name }}</span>
                    @if($recipe->item)
                        <x-badge value="Preparación" icon="lucide.cooking-pot" class="badge-primary badge-soft badge-sm whitespace-nowrap" />
                    @endif
                </div>
            @endscope

            @scope('cell_description', $recipe)
                <span class="inline-flex items-center gap-1.5 text-sm text-gray-500 max-w-md truncate">
                    <x-icon name="lucide.align-left" class="w-3.5 h-3.5 text-gray-400 shrink-0" />
                    {{ $recipe->description ?? '— Sin descripción —' }}
                </span>
            @endscope

            @scope('actions', $recipe)
                <div class="flex items-center justify-end gap-1">
                    <x-button icon="lucide.layers" link="/recipes/{{ $recipe->id }}" class="btn-sm btn-info btn-soft" tooltip="Gestionar Etapas e Ingredientes" />
                    <x-button icon="lucide.pencil-line" link="/recipes/{{ $recipe->id }}/edit" class="btn-sm btn-ghost text-sky-600 hover:bg-sky-50" tooltip="Editar Datos" />
                    <x-button icon="lucide.download" label="Exportar" wire:click="export({{ $recipe->id }})"
                        class="btn-xs btn-ghost text-amber-700 hover:bg-amber-50 gap-1" tooltip="Descargar esta receta como JSON" />
                    <x-button icon="lucide.trash-2" wire:click="delete({{ $recipe->id }})" wire:confirm="Eliminar esta receta y todas sus etapas?" class="btn-sm btn-ghost text-error hover:bg-red-50" tooltip="Eliminar" />
                </div>
            @endscope
        </x-table>
    </x-card>

    {{-- MODAL DE IMPORTACIÓN --}}
    <x-modal wire:model="importModal" title="Importar Receta desde JSON" separator>
        <div class="space-y-4">
            <x-file wire:model="importFile" label="Selecciona el archivo JSON de la receta" accept=".json" icon="lucide.file-json" hint="Solo archivos con extensión .json" />

            @if($importError)
                <x-alert icon="lucide.alert-circle" class="alert-error text-sm">
                    {{ $importError }}
                </x-alert>
            @endif

            @if($previewData)
                @if(($previewData['mode'] ?? 'single') === 'collection')
                    <div class="bg-base-200/60 p-4 rounded-xl space-y-3 border border-base-300">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-gray-500">
                                {{ $previewData['recipes_count'] }} recetas en el archivo
                            </span>
                            <x-badge :value="'v' . $previewData['version']" class="badge-ghost badge-sm" />
                        </div>

                        <div class="flex gap-2">
                            <x-badge :value="$previewData['stages_count'] . ' etapas'" icon="lucide.flame" class="badge-sm badge-outline text-gray-600" />
                            <x-badge :value="$previewData['items_count'] . ' ítems en total'" icon="lucide.boxes" class="badge-sm badge-outline text-gray-600" />
                        </div>

                        <ul class="divide-y divide-base-300 max-h-56 overflow-y-auto">
                            @foreach($previewData['recipes'] as $previewRecipe)
                                <li class="flex items-center justify-between gap-3 py-2">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <span class="p-1.5 rounded-lg shrink-0 {{ $previewRecipe['exists'] ? 'bg-amber-100 text-amber-600' : 'bg-emerald-100 text-emerald-600' }}">
                                            <x-icon :name="$previewRecipe['exists'] ? 'lucide.refresh-cw' : 'lucide.plus'" class="w-4 h-4" />
                                        </span>
                                        <div class="min-w-0">
                                            <p class="font-semibold text-sm truncate">{{ $previewRecipe['name'] }}</p>
                                            <p class="text-xs text-gray-500">
                                                {{ $previewRecipe['stages_count'] }} etapas · {{ $previewRecipe['items_count'] }} ítems
                                            </p>
                                        </div>
                                    </div>
                                    <x-badge :value="$previewRecipe['exists'] ? 'Ya existe' : 'Nueva'"
                                        class="badge-sm whitespace-nowrap {{ $previewRecipe['exists'] ? 'badge-warning badge-soft' : 'badge-success badge-soft' }}" />
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    <div class="bg-base-200/60 p-4 rounded-xl space-y-3 border border-base-300">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Encabezado de la Receta</span>
                            <x-badge :value="'v' . $previewData['version']" class="badge-ghost badge-sm" />
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="p-2 bg-orange-100 text-orange-600 rounded-lg shrink-0 mt-0.5">
                                <x-icon name="lucide.soup" class="w-5 h-5" />
                            </span>
                            <div>
                                <h4 class="font-bold text-base text-gray-800">{{ $previewData['name'] }}</h4>
                                <p class="text-sm text-gray-500 mt-0.5">{{ $previewData['description'] ?? '— Sin descripción —' }}</p>
                                <div class="flex gap-2 mt-2">
                                    <x-badge :value="$previewData['stages_count'] . ' etapas'" icon="lucide.flame" class="badge-sm badge-outline text-gray-600" />
                                    <x-badge :value="$previewData['items_count'] . ' ítems en total'" icon="lucide.boxes" class="badge-sm badge-outline text-gray-600" />
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @if($previewData['exists'])
                    <x-alert :icon="($previewData['mode'] ?? 'single') === 'collection' ? 'lucide.alert-triangle' : 'lucide.triangle-alert'"
                        class="alert-warning text-sm">
                        <div>
                            <p class="font-bold">¡Receta ya existente!</p>
                            @if(($previewData['mode'] ?? 'single') === 'collection')
                                <p>
                                    {{ $previewData['conflicts_count'] }} de {{ $previewData['recipes_count'] }} recetas ya existen en el sistema.
                                    Si continúas, se <strong>sobreescribirán</strong> reemplazando sus etapas e ingredientes actuales.
                                </p>
                            @else
                                <p>Ya existe una receta registrada con el nombre <strong class="underline">{{ $previewData['name'] }}</strong> en el sistema. Si continúas, se <strong>sobreescribirá</strong> reemplazando sus etapas e ingredientes actuales.</p>
                            @endif
                        </div>
                    </x-alert>
                @else
                    <x-alert icon="lucide.circle-check" class="alert-success alert-soft text-sm">
                        {{ ($previewData['mode'] ?? 'single') === 'collection'
                            ? 'Todas las recetas del archivo son nuevas y están listas para importarse.'
                            : 'La receta es nueva en el catálogo y está lista para importarse.' }}
                    </x-alert>
                @endif
            @endif
        </div>

        <x-slot:actions>
            <x-button label="Cancelar" wire:click="$set('importModal', false)" class="btn-ghost" />
            @if($previewData)
                @if($previewData['exists'])
                    <x-button icon="lucide.refresh-cw" label="Sobreescribir" class="btn-warning" wire:click="executeImport(true)" />
                @else
                    <x-button icon="lucide.upload" :label="($previewData['mode'] ?? 'single') === 'collection'
                        ? 'Importar ' . $previewData['recipes_count'] . ' Recetas'
                        : 'Importar Receta'"
                        class="btn-primary" wire:click="executeImport(false)" />
                @endif
            @endif
        </x-slot:actions>
    </x-modal>
</div>
