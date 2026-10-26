<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Item;
use App\Models\Recipe;
use App\Services\PreparationService;

class RecipeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Traemos algunos ítems para usar en las recetas
        $harina = Item::where('name', 'Harina 0000')->first();
        $sal = Item::where('name', 'Sal Fina')->first();
        $aceite = Item::where('name', 'Aceite de Girasol')->first();
        $queso = Item::where('name', 'Queso Mozzarella')->first();
        $tomate = Item::where('name', 'Tomate Perita')->first();
        $oregano = Item::where('name', 'Orégano')->first();
        $cocinero = Item::where('name', 'Cocinero (Hora)')->first();
        $caja = Item::where('name', 'Caja Pizza 33x33')->first();
        $luz = Item::where('name', 'Luz y Gas (Aprox por Receta)')->first();
        $merma = Item::where('name', 'Margen de Merma/Error')->first();

        $leche = Item::where('name', 'Leche Entera')->first();
        $azucar = Item::where('name', 'Azúcar Blanca')->first();

        $carne = Item::where('name', 'Carne Picada Vacuna')->first();
        $papa = Item::where('name', 'Papa')->first();
        $cebolla = Item::where('name', 'Cebolla')->first();
        $huevo = Item::where('name', 'Huevo Blanco')->first();
        $panRallado = Item::where('name', 'Pan Rallado')->first();
        $bandeja = Item::where('name', 'Bandeja Plástica 1050cc')->first();
        $papelManteca = Item::where('name', 'Papel Manteca Antiadherente')->first();
        $guantes = Item::where('name', 'Guantes de Nitrilo Manipulación')->first();

        // ----------------------------------------------------
        // RECETA 1: Pizza de Mozzarella
        // ----------------------------------------------------
        if ($harina && $queso) {
            $pizza = Recipe::create([
                'name' => 'Pizza de Mozzarella Clásica',
                'description' => 'Receta estándar para una pizza grande de 8 porciones. Incluye preparación de masa, salsa y horneado.',
                'yield_quantity' => 8,
                'yield_unit' => 'porción',
            ]);

            // Etapa 1: Masa
            $etapaMasa = $pizza->stages()->create([
                'name' => 'Preparación de Masa',
                'duration_minutes' => 15,
                'order' => 1,
            ]);
            $etapaMasa->recipeItems()->createMany([
                ['recipe_id' => $pizza->id, 'item_id' => $harina->id, 'quantity' => 300], // 300 gramos de harina
                ['recipe_id' => $pizza->id, 'item_id' => $sal->id, 'quantity' => 10],     // 10 gramos de sal
                ['recipe_id' => $pizza->id, 'item_id' => $aceite->id, 'quantity' => 20],  // 20 ml de aceite
                ['recipe_id' => $pizza->id, 'item_id' => $cocinero->id, 'quantity' => 15], // 15 minutos de cocinero
            ]);

            // Etapa 2: Salsa y Armado
            $etapaSalsa = $pizza->stages()->create([
                'name' => 'Salsa y Armado',
                'duration_minutes' => 5,
                'order' => 2,
            ]);
            $etapaSalsa->recipeItems()->createMany([
                ['recipe_id' => $pizza->id, 'item_id' => $tomate->id, 'quantity' => 150],
                ['recipe_id' => $pizza->id, 'item_id' => $queso->id, 'quantity' => 250],
                ['recipe_id' => $pizza->id, 'item_id' => $oregano->id, 'quantity' => 5],
                ['recipe_id' => $pizza->id, 'item_id' => $cocinero->id, 'quantity' => 5],
            ]);

            // Etapa 3: Horneado y Empaque
            $etapaHorno = $pizza->stages()->create([
                'name' => 'Horneado y Empaque',
                'duration_minutes' => 12,
                'order' => 3,
            ]);
            if ($papelManteca) $etapaHorno->recipeItems()->create(['recipe_id' => $pizza->id, 'item_id' => $papelManteca->id, 'quantity' => 0.5]); // 0.5m de papel manteca
            if ($luz) $etapaHorno->recipeItems()->create(['recipe_id' => $pizza->id, 'item_id' => $luz->id, 'quantity' => 1]); // 1 Costo fijo de luz
            if ($caja) $etapaHorno->recipeItems()->create(['recipe_id' => $pizza->id, 'item_id' => $caja->id, 'quantity' => 1]); // 1 Caja
            if ($merma) $etapaHorno->recipeItems()->create(['recipe_id' => $pizza->id, 'item_id' => $merma->id, 'quantity' => 1]); // 1 Aplicación de Merma
        }

        // ----------------------------------------------------
        // RECETA 2: Albóndigas con Puré
        // ----------------------------------------------------
        if ($carne && $papa) {
            $albondigas = Recipe::create([
                'name' => 'Porción de Albóndigas con Puré',
                'description' => 'Plato principal clásico. Porción abundante servida en vianda plástica.',
                'yield_quantity' => 1,
                'yield_unit' => 'porción',
            ]);

            // Etapa 1: Mezcla de Albóndigas
            $etapaMezcla = $albondigas->stages()->create([
                'name' => 'Mezcla y Formado',
                'duration_minutes' => 10,
                'order' => 1,
            ]);
            $etapaMezcla->recipeItems()->createMany([
                ['recipe_id' => $albondigas->id, 'item_id' => $carne->id, 'quantity' => 250],
                ['recipe_id' => $albondigas->id, 'item_id' => $cebolla->id, 'quantity' => 50],
                ['recipe_id' => $albondigas->id, 'item_id' => $huevo->id, 'quantity' => 0.5], // Medio huevo por porción aprox
                ['recipe_id' => $albondigas->id, 'item_id' => $panRallado->id, 'quantity' => 30],
                ['recipe_id' => $albondigas->id, 'item_id' => $sal->id, 'quantity' => 5],
                ['recipe_id' => $albondigas->id, 'item_id' => $cocinero->id, 'quantity' => 10],
            ]);

            // Etapa 2: Puré de Papas
            $etapaPure = $albondigas->stages()->create([
                'name' => 'Hervido y Puré',
                'duration_minutes' => 20,
                'order' => 2,
            ]);
            $etapaPure->recipeItems()->createMany([
                ['recipe_id' => $albondigas->id, 'item_id' => $papa->id, 'quantity' => 300], // 300gr de papa
                ['recipe_id' => $albondigas->id, 'item_id' => $sal->id, 'quantity' => 5],
                ['recipe_id' => $albondigas->id, 'item_id' => $cocinero->id, 'quantity' => 8],
            ]);

            // Etapa 3: Emplatado
            $etapaEmplatado = $albondigas->stages()->create([
                'name' => 'Emplatado (Delivery)',
                'duration_minutes' => 3,
                'order' => 3,
            ]);
            if ($bandeja) $etapaEmplatado->recipeItems()->create(['recipe_id' => $albondigas->id, 'item_id' => $bandeja->id, 'quantity' => 1]);
            if ($guantes) $etapaEmplatado->recipeItems()->create(['recipe_id' => $albondigas->id, 'item_id' => $guantes->id, 'quantity' => 1]); // 1 par de guantes
            if ($luz) $etapaEmplatado->recipeItems()->create(['recipe_id' => $albondigas->id, 'item_id' => $luz->id, 'quantity' => 1]);
        }

        // ----------------------------------------------------
        // RECETA 3: Menú Completo Hamburguesa Gourmet con Papas Rústicas
        // Contiene la mayor cantidad de ítems y tipos combinados
        // ----------------------------------------------------
        $bondiola = Item::where('name', 'Bondiola de Cerdo')->first();
        $morron = Item::where('name', 'Morrón Rojo')->first();
        $pimienta = Item::where('name', 'Pimienta Negra Molida')->first();
        $pimenton = Item::where('name', 'Pimenton Dulce')->orWhere('name', 'Pimentón Dulce')->first();
        $ajiMolido = Item::where('name', 'Ají Molido')->first();
        $ajoPolvo = Item::where('name', 'Ajo en Polvo')->first();
        $manteca = Item::where('name', 'Manteca')->first();
        $ayudante = Item::where('name', 'Ayudante de Cocina')->first();
        $soplete = Item::where('name', 'Gas Butano / Soplete Cocina')->first();
        $carbon = Item::where('name', 'Carbón Vegetal Seleccionado')->first();
        $separadores = Item::where('name', 'Separadores Plásticos para Hamburguesas/Milanesas')->first();
        $bolsaKraft = Item::where('name', 'Bolsa Kraft Delivery')->first();
        $film = Item::where('name', 'Film Adherente (300m)')->first();
        $limpieza = Item::where('name', 'Artículos de Limpieza')->first();

        // ----------------------------------------------------
        // PREPARACIÓN: Pan Brioche Casero (se usa como ítem)
        // ----------------------------------------------------
        $pan = null;

        if ($harina && $leche && $manteca && $huevo) {
            $panRecipe = Recipe::create([
                'name' => 'Pan Brioche Casero',
                'description' => 'Pan de hamburguesa esponjoso. Rinde 8 unidades y se usa como preparación dentro del combo.',
                'yield_quantity' => 8,
                'yield_unit' => 'unidad',
            ]);

            $etapaAmasado = $panRecipe->stages()->create([
                'name' => 'Amasado y Fermentado',
                'duration_minutes' => 40,
                'order' => 1,
            ]);
            $etapaAmasado->recipeItems()->createMany([
                ['recipe_id' => $panRecipe->id, 'item_id' => $harina->id, 'quantity' => 500],
                ['recipe_id' => $panRecipe->id, 'item_id' => $leche->id, 'quantity' => 200],
                ['recipe_id' => $panRecipe->id, 'item_id' => $manteca->id, 'quantity' => 60],
                ['recipe_id' => $panRecipe->id, 'item_id' => $huevo->id, 'quantity' => 1],
                ['recipe_id' => $panRecipe->id, 'item_id' => $sal->id, 'quantity' => 8],
                ['recipe_id' => $panRecipe->id, 'item_id' => $cocinero->id, 'quantity' => 40],
            ]);

            if ($azucar) {
                $etapaAmasado->recipeItems()->create(['recipe_id' => $panRecipe->id, 'item_id' => $azucar->id, 'quantity' => 40]);
            }

            $etapaHorneadoPan = $panRecipe->stages()->create([
                'name' => 'Horneado y Enfriado',
                'duration_minutes' => 25,
                'order' => 2,
            ]);
            $etapaHorneadoPan->recipeItems()->create([
                'recipe_id' => $panRecipe->id,
                'item_id' => $cocinero->id,
                'quantity' => 25,
            ]);

            $pan = app(PreparationService::class)->publish($panRecipe);
        }

        if ($carne && $bondiola && $papa) {
            $burger = Recipe::create([
                'name' => 'Combo Hamburguesa Gourmet con Papas Rústicas',
                'description' => 'Menú completo premium. Blend vacuno y bondiola de cerdo especiado, queso gratinado a soplete, papas al horno con hierbas y empaque térmico kraft para delivery.',
                'yield_quantity' => 1,
                'yield_unit' => 'combo',
            ]);

            // Etapa 1: Elaboración del Medallón (Blend & Especias)
            $etapaBlend = $burger->stages()->create([
                'name' => 'Blend y Modelado de Medallones',
                'duration_minutes' => 15,
                'order' => 1,
            ]);
            $etapaBlend->recipeItems()->createMany([
                ['recipe_id' => $burger->id, 'item_id' => $carne->id, 'quantity' => 140],      // 140g carne picada
                ['recipe_id' => $burger->id, 'item_id' => $bondiola->id, 'quantity' => 60],     // 60g bondiola
                ['recipe_id' => $burger->id, 'item_id' => $sal->id, 'quantity' => 4],           // 4g sal
                ['recipe_id' => $burger->id, 'item_id' => $pimienta->id, 'quantity' => 1.5],   // 1.5g pimienta
                ['recipe_id' => $burger->id, 'item_id' => $ajoPolvo->id, 'quantity' => 2],      // 2g ajo en polvo
                ['recipe_id' => $burger->id, 'item_id' => $ayudante->id, 'quantity' => 10],     // 10 min ayudante
            ]);
            if ($separadores) {
                $etapaBlend->recipeItems()->create(['recipe_id' => $burger->id, 'item_id' => $separadores->id, 'quantity' => 1]); // Separador plástico
            }
            if ($film) {
                $etapaBlend->recipeItems()->create(['recipe_id' => $burger->id, 'item_id' => $film->id, 'quantity' => 0.4]); // 40cm de film para reposo
            }

            // Etapa 2: Guarnición de Papas Rústicas al Horno
            $etapaPapas = $burger->stages()->create([
                'name' => 'Papas Rústicas Especiadas',
                'duration_minutes' => 25,
                'order' => 2,
            ]);
            $etapaPapas->recipeItems()->createMany([
                ['recipe_id' => $burger->id, 'item_id' => $papa->id, 'quantity' => 250],        // 250g de papas
                ['recipe_id' => $burger->id, 'item_id' => $aceite->id, 'quantity' => 25],       // 25ml aceite girasol
                ['recipe_id' => $burger->id, 'item_id' => $sal->id, 'quantity' => 3],           // 3g sal
                ['recipe_id' => $burger->id, 'item_id' => $pimenton->id, 'quantity' => 2],      // 2g pimentón dulce
                ['recipe_id' => $burger->id, 'item_id' => $oregano->id, 'quantity' => 1],       // 1g orégano
                ['recipe_id' => $burger->id, 'item_id' => $ayudante->id, 'quantity' => 8],      // 8 min ayudante
            ]);
            if ($papelManteca) {
                $etapaPapas->recipeItems()->create(['recipe_id' => $burger->id, 'item_id' => $papelManteca->id, 'quantity' => 0.3]); // 30cm papel manteca en asadera
            }

            // Etapa 3: Cocción a la Parrilla y Gratinado
            $etapaCoccion = $burger->stages()->create([
                'name' => 'Cocción a la Brasa y Gratinado',
                'duration_minutes' => 12,
                'order' => 3,
            ]);
            $etapaCoccion->recipeItems()->createMany([
                ['recipe_id' => $burger->id, 'item_id' => $queso->id, 'quantity' => 60],        // 60g mozzarella
                ['recipe_id' => $burger->id, 'item_id' => $manteca->id, 'quantity' => 15],      // 15g manteca para tostado de pan
                ['recipe_id' => $burger->id, 'item_id' => $cocinero->id, 'quantity' => 12],     // 12 min cocinero
            ]);
            if ($pan) {
                $etapaCoccion->recipeItems()->create(['recipe_id' => $burger->id, 'item_id' => $pan->id, 'quantity' => 1]); // 1 pan brioche (preparación)
            }
            if ($carbon) {
                $etapaCoccion->recipeItems()->create(['recipe_id' => $burger->id, 'item_id' => $carbon->id, 'quantity' => 0.4]); // 400g carbón vegetal
            }
            if ($soplete) {
                $etapaCoccion->recipeItems()->create(['recipe_id' => $burger->id, 'item_id' => $soplete->id, 'quantity' => 1]); // 1 golpe de soplete de butano
            }

            // Etapa 4: Emplatado, Empaque y Despacho Delivery
            $etapaDespacho = $burger->stages()->create([
                'name' => 'Emplatado y Empaque Delivery',
                'duration_minutes' => 5,
                'order' => 4,
            ]);
            $etapaDespacho->recipeItems()->createMany([
                ['recipe_id' => $burger->id, 'item_id' => $ayudante->id, 'quantity' => 4],      // 4 min ayudante
            ]);
            if ($guantes) {
                $etapaDespacho->recipeItems()->create(['recipe_id' => $burger->id, 'item_id' => $guantes->id, 'quantity' => 1]); // 1 par guantes nitrilo
            }
            if ($bandeja) {
                $etapaDespacho->recipeItems()->create(['recipe_id' => $burger->id, 'item_id' => $bandeja->id, 'quantity' => 1]); // Bandeja para papas
            }
            if ($bolsaKraft) {
                $etapaDespacho->recipeItems()->create(['recipe_id' => $burger->id, 'item_id' => $bolsaKraft->id, 'quantity' => 1]); // Bolsa kraft delivery
            }
            if ($luz) {
                $etapaDespacho->recipeItems()->create(['recipe_id' => $burger->id, 'item_id' => $luz->id, 'quantity' => 1]); // Luz y gas fijo
            }
            if ($limpieza) {
                $etapaDespacho->recipeItems()->create(['recipe_id' => $burger->id, 'item_id' => $limpieza->id, 'quantity' => 1]); // Limpieza fija
            }
            if ($merma) {
                $etapaDespacho->recipeItems()->create(['recipe_id' => $burger->id, 'item_id' => $merma->id, 'quantity' => 1]); // 5% de merma / error sobre costos directos
            }
        }
    }
}

