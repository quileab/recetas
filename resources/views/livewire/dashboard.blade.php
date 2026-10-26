<?php

use Livewire\Volt\Component;
use App\Models\Item;
use App\Models\Recipe;

new class extends Component {
    public function with(): array
    {
        $recipes = Recipe::with(['stages.recipeItems.item'])->get();
        $totalItems = Item::count();
        $totalRecipes = $recipes->count();

        // Conteo por tipo de ítem
        $itemsByType = [
            'ingredient' => Item::where('type', 'ingredient')->count(),
            'labor' => Item::where('type', 'labor')->count(),
            'packaging' => Item::where('type', 'packaging')->count(),
            'direct_cost' => Item::where('type', 'direct_cost')->count(),
            'overhead' => Item::where('type', 'overhead')->count(),
            'preparation' => Item::where('type', 'preparation')->count(),
        ];

        // Conteo específico de ítems de Costos Directos
        $directCostItemsCount = Item::where('type', 'direct_cost')->count();

        // Conteo de categorías únicas
        $totalCategories = Item::whereNotNull('category')->distinct()->count('category');

        // Cálculo de costos directos e indirectos acumulados
        $overallDirectCost = 0;
        $overallIndirectCost = 0;

        // Cálculo de costos por receta
        $recipeStats = $recipes->map(function ($recipe) use (&$overallDirectCost, &$overallIndirectCost) {
            $directCost = $recipe->calculateDirectCost();
            $indirectCost = $recipe->calculateOverheadCost($directCost);
            $cost = $directCost + $indirectCost;

            $totalMinutes = $recipe->stages->sum('duration_minutes');
            $ingredientsCount = $recipe->stages->flatMap->recipeItems->count();

            $overallDirectCost += $directCost;
            $overallIndirectCost += $indirectCost;

            return [
                'id' => $recipe->id,
                'name' => $recipe->name,
                'description' => $recipe->description,
                'stages_count' => $recipe->stages->count(),
                'ingredients_count' => $ingredientsCount,
                'total_minutes' => $totalMinutes,
                'direct_cost' => $directCost,
                'indirect_cost' => $indirectCost,
                'total_cost' => $cost,
            ];
        });

        $averageCost = $totalRecipes > 0 ? $recipeStats->avg('total_cost') : 0;
        $totalStages = $recipes->sum(fn ($r) => $r->stages->count());

        // Top categorías con cantidad de ítems
        $topCategories = Item::selectRaw('category, count(*) as count')
            ->whereNotNull('category')
            ->groupBy('category')
            ->orderByDesc('count')
            ->take(5)
            ->get();

        return [
            'totalItems' => $totalItems,
            'totalRecipes' => $totalRecipes,
            'totalCategories' => $totalCategories,
            'totalStages' => $totalStages,
            'averageCost' => $averageCost,
            'directCostItemsCount' => $directCostItemsCount,
            'overallDirectCost' => $overallDirectCost,
            'overallIndirectCost' => $overallIndirectCost,
            'itemsByType' => $itemsByType,
            'topCategories' => $topCategories,
            'recentRecipes' => $recipeStats->take(5),
        ];
    }
}; ?>

<div class="space-y-8">
    {{-- HERO WELCOME CARD --}}
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-amber-500 via-orange-500 to-rose-600 text-white shadow-xl shadow-orange-500/20 p-8 md:p-10">
        {{-- Background decorative shapes --}}
        <div class="absolute -right-10 -bottom-10 w-64 h-64 rounded-full bg-white/10 blur-2xl pointer-events-none"></div>
        <div class="absolute right-1/4 -top-12 w-48 h-48 rounded-full bg-yellow-300/20 blur-xl pointer-events-none"></div>

        <div class="relative z-10 max-w-3xl space-y-4">
            <div class="inline-flex items-center gap-2.5 px-3.5 py-1.5 rounded-full bg-white/15 backdrop-blur-md border border-white/20 text-xs font-semibold uppercase tracking-wider text-orange-100">
                <x-icon name="lucide.sparkles" class="w-4 h-4 text-amber-200" />
                <span>Panel de Control Gastronómico</span>
            </div>

            <h1 class="text-3xl md:text-5xl font-extrabold tracking-tight text-white drop-shadow-sm">
                Bienvenido a <span class="underline decoration-amber-300/60 decoration-wavy decoration-2">Recetas Pyme</span>
            </h1>

            <p class="text-lg md:text-xl text-orange-50 font-medium leading-relaxed max-w-2xl">
                “Cocina con amor, costea con precisión”
            </p>

            <p class="text-sm md:text-base text-orange-100/90 leading-relaxed max-w-2xl">
                Calcula con certeza el costo real de cada preparación integrando materias primas, mano de obra, empaques, insumos directos y costos indirectos etapa por etapa.
            </p>

            <div class="pt-2 flex flex-wrap gap-3">
                <x-button label="Ver Recetas" icon="lucide.soup" link="/recipes" class="btn bg-white text-orange-700 hover:bg-orange-50 border-0 shadow-md font-semibold" />
                <x-button label="Gestionar Ítems y Costos" icon="lucide.shopping-basket" link="/items" class="btn bg-black/20 text-white hover:bg-black/30 border border-white/30 backdrop-blur-sm" />
                <x-button label="Nueva Receta" icon="lucide.plus" link="/recipes/create" class="btn bg-amber-400 text-orange-950 hover:bg-amber-300 border-0 font-bold shadow-md" />
            </div>
        </div>
    </div>

    {{-- KEY METRICS STATS --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        {{-- Card Recetas --}}
        <x-card class="bg-base-100 shadow-sm border border-base-200 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Recetas Activas</p>
                    <p class="text-3xl font-extrabold text-orange-600 mt-1">{{ $totalRecipes }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ $totalStages }} etapas formuladas</p>
                </div>
                <span class="p-3.5 rounded-2xl bg-orange-100 text-orange-600">
                    <x-icon name="lucide.cooking-pot" class="w-7 h-7" />
                </span>
            </div>
        </x-card>

        {{-- Card Ítems --}}
        <x-card class="bg-base-100 shadow-sm border border-base-200 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Catálogo Total</p>
                    <p class="text-3xl font-extrabold text-emerald-600 mt-1">{{ $totalItems }}</p>
                    <p class="text-xs text-gray-400 mt-1">En {{ $totalCategories }} categorías</p>
                </div>
                <span class="p-3.5 rounded-2xl bg-emerald-100 text-emerald-600">
                    <x-icon name="lucide.shopping-basket" class="w-7 h-7" />
                </span>
            </div>
        </x-card>

        {{-- Card Costos Directos Catálogo --}}
        <x-card class="bg-base-100 shadow-sm border border-base-200 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Insumos Costo Directo</p>
                    <p class="text-3xl font-extrabold text-teal-600 mt-1">{{ $directCostItemsCount }}</p>
                    <p class="text-xs text-gray-400 mt-1">Gas, papel, carbón, guantes...</p>
                </div>
                <span class="p-3.5 rounded-2xl bg-teal-100 text-teal-600">
                    <x-icon name="lucide.flame" class="w-7 h-7" />
                </span>
            </div>
        </x-card>

        {{-- Card Costo Promedio --}}
        <x-card class="bg-base-100 shadow-sm border border-base-200 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Costo Promedio</p>
                    <p class="text-3xl font-extrabold text-violet-600 mt-1">${{ number_format($averageCost, 2) }}</p>
                    <p class="text-xs text-gray-400 mt-1">Por receta calculada</p>
                </div>
                <span class="p-3.5 rounded-2xl bg-violet-100 text-violet-600">
                    <x-icon name="lucide.circle-dollar-sign" class="w-7 h-7" />
                </span>
            </div>
        </x-card>
    </div>

    {{-- SECOND ROW: DISTRIBUTION & RECENT RECIPES --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Distribución de Catálogo y Categorías --}}
        <div class="space-y-6">
            <x-card title="Composición del Catálogo" subtitle="Distribución por tipo de ítem de costo" class="bg-base-100 shadow-sm border border-base-200">
                <div class="space-y-4 pt-2">
                    {{-- Materia Prima --}}
                    <div>
                        <div class="flex justify-between items-center text-sm font-medium mb-1">
                            <span class="flex items-center gap-2 text-gray-700">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                                Materias Primas / Ingredientes
                            </span>
                            <span class="font-bold text-gray-900">{{ $itemsByType['ingredient'] }}</span>
                        </div>
                        <div class="w-full bg-base-200 rounded-full h-2 overflow-hidden">
                            <div class="bg-emerald-500 h-2 rounded-full" style="width: {{ $totalItems > 0 ? ($itemsByType['ingredient'] / $totalItems) * 100 : 0 }}%"></div>
                        </div>
                    </div>

                    {{-- Mano de Obra --}}
                    <div>
                        <div class="flex justify-between items-center text-sm font-medium mb-1">
                            <span class="flex items-center gap-2 text-gray-700">
                                <span class="w-2.5 h-2.5 rounded-full bg-sky-500"></span>
                                Mano de Obra
                            </span>
                            <span class="font-bold text-gray-900">{{ $itemsByType['labor'] }}</span>
                        </div>
                        <div class="w-full bg-base-200 rounded-full h-2 overflow-hidden">
                            <div class="bg-sky-500 h-2 rounded-full" style="width: {{ $totalItems > 0 ? ($itemsByType['labor'] / $totalItems) * 100 : 0 }}%"></div>
                        </div>
                    </div>

                    {{-- Empaque --}}
                    <div>
                        <div class="flex justify-between items-center text-sm font-medium mb-1">
                            <span class="flex items-center gap-2 text-gray-700">
                                <span class="w-2.5 h-2.5 rounded-full bg-purple-500"></span>
                                Empaques / Descartables
                            </span>
                            <span class="font-bold text-gray-900">{{ $itemsByType['packaging'] }}</span>
                        </div>
                        <div class="w-full bg-base-200 rounded-full h-2 overflow-hidden">
                            <div class="bg-purple-500 h-2 rounded-full" style="width: {{ $totalItems > 0 ? ($itemsByType['packaging'] / $totalItems) * 100 : 0 }}%"></div>
                        </div>
                    </div>

                    {{-- Costos Directos --}}
                    <div>
                        <div class="flex justify-between items-center text-sm font-medium mb-1">
                            <span class="flex items-center gap-2 text-gray-700">
                                <span class="w-2.5 h-2.5 rounded-full bg-teal-500"></span>
                                Costos Directos (Insumos/Cocción)
                            </span>
                            <span class="font-bold text-gray-900">{{ $itemsByType['direct_cost'] }}</span>
                        </div>
                        <div class="w-full bg-base-200 rounded-full h-2 overflow-hidden">
                            <div class="bg-teal-500 h-2 rounded-full" style="width: {{ $totalItems > 0 ? ($itemsByType['direct_cost'] / $totalItems) * 100 : 0 }}%"></div>
                        </div>
                    </div>

                    {{-- Costos Indirectos --}}
                    <div>
                        <div class="flex justify-between items-center text-sm font-medium mb-1">
                            <span class="flex items-center gap-2 text-gray-700">
                                <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                                Costos Indirectos (Overhead)
                            </span>
                            <span class="font-bold text-gray-900">{{ $itemsByType['overhead'] }}</span>
                        </div>
                        <div class="w-full bg-base-200 rounded-full h-2 overflow-hidden">
                            <div class="bg-amber-500 h-2 rounded-full" style="width: {{ $totalItems > 0 ? ($itemsByType['overhead'] / $totalItems) * 100 : 0 }}%"></div>
                        </div>
                    </div>

                    {{-- Preparaciones --}}
                    <div>
                        <div class="flex justify-between items-center text-sm font-medium mb-1">
                            <span class="flex items-center gap-2 text-gray-700">
                                <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
                                Preparaciones / Sub-recetas
                            </span>
                            <span class="font-bold text-gray-900">{{ $itemsByType['preparation'] }}</span>
                        </div>
                        <div class="w-full bg-base-200 rounded-full h-2 overflow-hidden">
                            <div class="bg-indigo-500 h-2 rounded-full" style="width: {{ $totalItems > 0 ? ($itemsByType['preparation'] / $totalItems) * 100 : 0 }}%"></div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 pt-4 border-t border-base-200 text-center">
                    <x-button label="Administrar Catálogo" icon="lucide.arrow-right" link="/items" class="btn-sm btn-ghost text-emerald-600 hover:bg-emerald-50" />
                </div>
            </x-card>

            {{-- Desglose Costo Directo vs Indirecto en Producción --}}
            <x-card title="Estructura de Costeo Global" subtitle="Proporción directa vs indirecta calculada" class="bg-base-100 shadow-sm border border-base-200">
                @php
                    $grandTotal = $overallDirectCost + $overallIndirectCost;
                    $directPercent = $grandTotal > 0 ? round(($overallDirectCost / $grandTotal) * 100) : 0;
                    $indirectPercent = $grandTotal > 0 ? (100 - $directPercent) : 0;
                @endphp
                <div class="space-y-4 pt-1">
                    <div class="flex items-center justify-between p-3 rounded-xl bg-teal-50/70 border border-teal-100">
                        <div class="flex items-center gap-2.5">
                            <span class="p-2 rounded-lg bg-teal-500 text-white">
                                <x-icon name="lucide.trending-up" class="w-4 h-4" />
                            </span>
                            <div>
                                <p class="text-xs font-semibold text-teal-900 uppercase tracking-wider">Costos Directos</p>
                                <p class="text-xs text-teal-700/80">Insumos, carnes, mano de obra...</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="font-extrabold text-teal-900 text-base">${{ number_format($overallDirectCost, 2) }}</span>
                            <span class="block text-[11px] font-bold text-teal-700">{{ $directPercent }}%</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between p-3 rounded-xl bg-amber-50/70 border border-amber-100">
                        <div class="flex items-center gap-2.5">
                            <span class="p-2 rounded-lg bg-amber-500 text-white">
                                <x-icon name="lucide.receipt-text" class="w-4 h-4" />
                            </span>
                            <div>
                                <p class="text-xs font-semibold text-amber-900 uppercase tracking-wider">Costos Indirectos</p>
                                <p class="text-xs text-amber-700/80">Luz, gas general, mermas, limpieza</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="font-extrabold text-amber-900 text-base">${{ number_format($overallIndirectCost, 2) }}</span>
                            <span class="block text-[11px] font-bold text-amber-700">{{ $indirectPercent }}%</span>
                        </div>
                    </div>

                    {{-- Mini lista de categorías destacadas --}}
                    <div class="pt-2">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Categorías con más insumos</p>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach($topCategories as $cat)
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium bg-base-200 text-gray-700">
                                    <x-icon name="lucide.tag" class="w-3 h-3 text-orange-500" />
                                    {{ $cat->category }}
                                    <span class="font-bold text-gray-900">({{ $cat->count }})</span>
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>
            </x-card>
        </div>

        {{-- Recetas y Costeos Recientes --}}
        <div class="lg:col-span-2">
            <x-card title="Recetas Calculadas" subtitle="Costos estimados y formulación por etapas" class="bg-base-100 shadow-sm border border-base-200">
                <x-slot:menu>
                    <x-button label="Ver todas" link="/recipes" class="btn-xs btn-ghost text-orange-600" />
                </x-slot:menu>

                <div class="divide-y divide-base-200">
                    @forelse($recentRecipes as $item)
                        <div class="py-4 first:pt-0 last:pb-0 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="space-y-1.5">
                                <a href="/recipes/{{ $item['id'] }}" class="font-bold text-base hover:text-orange-600 transition-colors flex items-center gap-2">
                                    <span class="bg-orange-100 text-orange-600 rounded-lg p-1.5 inline-flex">
                                        <x-icon name="lucide.soup" class="w-4 h-4" />
                                    </span>
                                    {{ $item['name'] }}
                                </a>
                                <p class="text-xs text-gray-500 line-clamp-1">
                                    {{ $item['description'] ?: 'Sin descripción detallada' }}
                                </p>
                                <div class="flex flex-wrap items-center gap-3 text-xs text-gray-500 pt-1">
                                    <span class="inline-flex items-center gap-1">
                                        <x-icon name="lucide.layers" class="w-3.5 h-3.5 text-orange-500" />
                                        {{ $item['stages_count'] }} etapas
                                    </span>
                                    <span class="inline-flex items-center gap-1">
                                        <x-icon name="lucide.shopping-basket" class="w-3.5 h-3.5 text-emerald-500" />
                                        {{ $item['ingredients_count'] }} ítems
                                    </span>
                                    <span class="inline-flex items-center gap-1">
                                        <x-icon name="lucide.timer" class="w-3.5 h-3.5 text-sky-500" />
                                        {{ $item['total_minutes'] }} min
                                    </span>
                                </div>
                                <div class="flex items-center gap-3 text-xs text-gray-600 pt-0.5">
                                    <span class="inline-flex items-center gap-1 text-teal-700 font-medium">
                                        <span class="w-1.5 h-1.5 rounded-full bg-teal-500"></span>
                                        Directos: ${{ number_format($item['direct_cost'], 2) }}
                                    </span>
                                    <span class="inline-flex items-center gap-1 text-amber-700 font-medium">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        Indirectos: ${{ number_format($item['indirect_cost'], 2) }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex sm:flex-col items-center sm:items-end justify-between sm:justify-center shrink-0">
                                <div class="text-right">
                                    <span class="text-xs text-gray-400 block">Costo total</span>
                                    <span class="text-2xl font-black text-emerald-600">
                                        ${{ number_format($item['total_cost'], 2) }}
                                    </span>
                                </div>
                                <div class="mt-2">
                                    <x-button label="Ver Fórmula" icon="lucide.chevron-right" link="/recipes/{{ $item['id'] }}" class="btn-xs btn-outline btn-primary" />
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center text-gray-400">
                            <x-icon name="lucide.cooking-pot" class="w-10 h-10 mx-auto text-gray-300 mb-2" />
                            <p>No hay recetas registradas todavía.</p>
                            <x-button label="Crear primera receta" link="/recipes/create" class="btn-sm btn-primary mt-3" />
                        </div>
                    @endforelse
                </div>
            </x-card>
        </div>
    </div>
</div>
