<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Item;

class ItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            // CARNES
            ['type' => 'ingredient', 'category' => 'Carnes', 'name' => 'Carne Picada Vacuna', 'purchase_unit' => 'Kg', 'purchase_price' => 7500, 'recipe_unit' => 'Gr', 'conversion_factor' => 1000],
            ['type' => 'ingredient', 'category' => 'Carnes', 'name' => 'Pechuga de Pollo', 'purchase_unit' => 'Kg', 'purchase_price' => 5500, 'recipe_unit' => 'Gr', 'conversion_factor' => 1000],
            ['type' => 'ingredient', 'category' => 'Carnes', 'name' => 'Bondiola de Cerdo', 'purchase_unit' => 'Kg', 'purchase_price' => 6200, 'recipe_unit' => 'Gr', 'conversion_factor' => 1000],

            // VERDURAS Y FRUTAS
            ['type' => 'ingredient', 'category' => 'Verduras', 'name' => 'Cebolla', 'purchase_unit' => 'Kg', 'purchase_price' => 1200, 'recipe_unit' => 'Gr', 'conversion_factor' => 1000],
            ['type' => 'ingredient', 'category' => 'Verduras', 'name' => 'Morrón Rojo', 'purchase_unit' => 'Kg', 'purchase_price' => 3500, 'recipe_unit' => 'Gr', 'conversion_factor' => 1000],
            ['type' => 'ingredient', 'category' => 'Verduras', 'name' => 'Papa', 'purchase_unit' => 'Kg', 'purchase_price' => 800, 'recipe_unit' => 'Gr', 'conversion_factor' => 1000],
            ['type' => 'ingredient', 'category' => 'Verduras', 'name' => 'Tomate Perita', 'purchase_unit' => 'Kg', 'purchase_price' => 1500, 'recipe_unit' => 'Gr', 'conversion_factor' => 1000],
            ['type' => 'ingredient', 'category' => 'Verduras', 'name' => 'Zanahoria', 'purchase_unit' => 'Kg', 'purchase_price' => 900, 'recipe_unit' => 'Gr', 'conversion_factor' => 1000],
            ['type' => 'ingredient', 'category' => 'Frutas', 'name' => 'Limón', 'purchase_unit' => 'Kg', 'purchase_price' => 1100, 'recipe_unit' => 'Unidad', 'conversion_factor' => 8], // Aprox 8 limones por kg
            ['type' => 'ingredient', 'category' => 'Frutas', 'name' => 'Manzana', 'purchase_unit' => 'Kg', 'purchase_price' => 1800, 'recipe_unit' => 'Gr', 'conversion_factor' => 1000],

            // LÁCTEOS Y HUEVOS
            ['type' => 'ingredient', 'category' => 'Lácteos', 'name' => 'Leche Entera', 'purchase_unit' => 'Litro', 'purchase_price' => 1200, 'recipe_unit' => 'Ml', 'conversion_factor' => 1000],
            ['type' => 'ingredient', 'category' => 'Lácteos', 'name' => 'Queso Mozzarella', 'purchase_unit' => 'Kg', 'purchase_price' => 8500, 'recipe_unit' => 'Gr', 'conversion_factor' => 1000],
            ['type' => 'ingredient', 'category' => 'Lácteos', 'name' => 'Manteca', 'purchase_unit' => 'Kg', 'purchase_price' => 9000, 'recipe_unit' => 'Gr', 'conversion_factor' => 1000],
            ['type' => 'ingredient', 'category' => 'Lácteos', 'name' => 'Crema de Leche', 'purchase_unit' => 'Litro', 'purchase_price' => 4500, 'recipe_unit' => 'Ml', 'conversion_factor' => 1000],
            ['type' => 'ingredient', 'category' => 'Huevos', 'name' => 'Huevo Blanco', 'purchase_unit' => 'Maple (30)', 'purchase_price' => 4500, 'recipe_unit' => 'Unidad', 'conversion_factor' => 30],

            // SECOS / ALMACÉN
            ['type' => 'ingredient', 'category' => 'Almacén', 'name' => 'Harina 0000', 'purchase_unit' => 'Kg', 'purchase_price' => 1100, 'recipe_unit' => 'Gr', 'conversion_factor' => 1000],
            ['type' => 'ingredient', 'category' => 'Almacén', 'name' => 'Harina 000', 'purchase_unit' => 'Kg', 'purchase_price' => 950, 'recipe_unit' => 'Gr', 'conversion_factor' => 1000],
            ['type' => 'ingredient', 'category' => 'Almacén', 'name' => 'Azúcar Blanca', 'purchase_unit' => 'Kg', 'purchase_price' => 1250, 'recipe_unit' => 'Gr', 'conversion_factor' => 1000],
            ['type' => 'ingredient', 'category' => 'Almacén', 'name' => 'Sal Fina', 'purchase_unit' => 'Kg', 'purchase_price' => 800, 'recipe_unit' => 'Gr', 'conversion_factor' => 1000],
            ['type' => 'ingredient', 'category' => 'Almacén', 'name' => 'Aceite de Girasol', 'purchase_unit' => 'Litro', 'purchase_price' => 2200, 'recipe_unit' => 'Ml', 'conversion_factor' => 1000],
            ['type' => 'ingredient', 'category' => 'Almacén', 'name' => 'Aceite de Oliva', 'purchase_unit' => 'Litro', 'purchase_price' => 9500, 'recipe_unit' => 'Ml', 'conversion_factor' => 1000],
            ['type' => 'ingredient', 'category' => 'Almacén', 'name' => 'Pan Rallado', 'purchase_unit' => 'Kg', 'purchase_price' => 2100, 'recipe_unit' => 'Gr', 'conversion_factor' => 1000],

            // CONDIMENTOS
            ['type' => 'ingredient', 'category' => 'Condimentos', 'name' => 'Pimienta Negra Molida', 'purchase_unit' => 'Kg', 'purchase_price' => 15000, 'recipe_unit' => 'Gr', 'conversion_factor' => 1000],
            ['type' => 'ingredient', 'category' => 'Condimentos', 'name' => 'Orégano', 'purchase_unit' => 'Kg', 'purchase_price' => 8500, 'recipe_unit' => 'Gr', 'conversion_factor' => 1000],
            ['type' => 'ingredient', 'category' => 'Condimentos', 'name' => 'Pimentón Dulce', 'purchase_unit' => 'Kg', 'purchase_price' => 9200, 'recipe_unit' => 'Gr', 'conversion_factor' => 1000],
            ['type' => 'ingredient', 'category' => 'Condimentos', 'name' => 'Ají Molido', 'purchase_unit' => 'Kg', 'purchase_price' => 8800, 'recipe_unit' => 'Gr', 'conversion_factor' => 1000],
            ['type' => 'ingredient', 'category' => 'Condimentos', 'name' => 'Ajo en Polvo', 'purchase_unit' => 'Kg', 'purchase_price' => 12000, 'recipe_unit' => 'Gr', 'conversion_factor' => 1000],
            ['type' => 'ingredient', 'category' => 'Condimentos', 'name' => 'Esencia de Vainilla', 'purchase_unit' => 'Litro', 'purchase_price' => 5500, 'recipe_unit' => 'Ml', 'conversion_factor' => 1000],

            // MANO DE OBRA
            ['type' => 'labor', 'category' => 'Personal', 'name' => 'Cocinero (Hora)', 'purchase_unit' => 'Mes', 'purchase_price' => 600000, 'recipe_unit' => 'Minuto', 'conversion_factor' => 9600], // Ej: 160 hs mensuales = 9600 min
            ['type' => 'labor', 'category' => 'Personal', 'name' => 'Ayudante de Cocina', 'purchase_unit' => 'Mes', 'purchase_price' => 450000, 'recipe_unit' => 'Minuto', 'conversion_factor' => 9600],
            ['type' => 'labor', 'category' => 'Personal', 'name' => 'Maestro Pastelero', 'purchase_unit' => 'Mes', 'purchase_price' => 750000, 'recipe_unit' => 'Minuto', 'conversion_factor' => 9600],

            // EMPAQUE
            ['type' => 'packaging', 'category' => 'Cajas', 'name' => 'Caja Pizza 33x33', 'purchase_unit' => 'Paquete (100)', 'purchase_price' => 15000, 'recipe_unit' => 'Unidad', 'conversion_factor' => 100],
            ['type' => 'packaging', 'category' => 'Bolsas', 'name' => 'Bolsa Kraft Delivery', 'purchase_unit' => 'Paquete (50)', 'purchase_price' => 8000, 'recipe_unit' => 'Unidad', 'conversion_factor' => 50],
            ['type' => 'packaging', 'category' => 'Descartables', 'name' => 'Bandeja Plástica 1050cc', 'purchase_unit' => 'Paquete (50)', 'purchase_price' => 9500, 'recipe_unit' => 'Unidad', 'conversion_factor' => 50],
            ['type' => 'packaging', 'category' => 'Descartables', 'name' => 'Film Adherente (300m)', 'purchase_unit' => 'Rollo', 'purchase_price' => 12000, 'recipe_unit' => 'Metro', 'conversion_factor' => 300],

            // COSTOS DIRECTOS (Insumos directos de elaboración y cocción)
            ['type' => 'direct_cost', 'category' => 'Costos Directos', 'name' => 'Gas Butano / Soplete Cocina', 'purchase_unit' => 'Cartucho', 'purchase_price' => 3800, 'recipe_unit' => 'Uso', 'conversion_factor' => 20], // 20 usos por cartucho
            ['type' => 'direct_cost', 'category' => 'Costos Directos', 'name' => 'Papel Manteca Antiadherente', 'purchase_unit' => 'Rollo (20m)', 'purchase_price' => 4200, 'recipe_unit' => 'Metro', 'conversion_factor' => 20],
            ['type' => 'direct_cost', 'category' => 'Costos Directos', 'name' => 'Carbón Vegetal Seleccionado', 'purchase_unit' => 'Bolsa (5kg)', 'purchase_price' => 4500, 'recipe_unit' => 'Kg', 'conversion_factor' => 5],
            ['type' => 'direct_cost', 'category' => 'Costos Directos', 'name' => 'Guantes de Nitrilo Manipulación', 'purchase_unit' => 'Caja (100u)', 'purchase_price' => 8900, 'recipe_unit' => 'Par', 'conversion_factor' => 50],
            ['type' => 'direct_cost', 'category' => 'Costos Directos', 'name' => 'Separadores Plásticos para Hamburguesas/Milanesas', 'purchase_unit' => 'Paquete (500u)', 'purchase_price' => 3200, 'recipe_unit' => 'Unidad', 'conversion_factor' => 500],

            // COSTOS INDIRECTOS (OVERHEAD)
            ['type' => 'overhead', 'category' => 'Servicios', 'name' => 'Luz y Gas (Aprox por Receta)', 'purchase_unit' => 'Fijo', 'purchase_price' => 150, 'recipe_unit' => 'Receta', 'conversion_factor' => 1, 'overhead_type' => 'fixed'],
            ['type' => 'overhead', 'category' => 'Márgenes', 'name' => 'Margen de Merma/Error', 'purchase_unit' => 'Porcentaje', 'purchase_price' => 5, 'recipe_unit' => '%', 'conversion_factor' => 1, 'overhead_type' => 'percentage'], // 5%
            ['type' => 'overhead', 'category' => 'Limpieza', 'name' => 'Artículos de Limpieza', 'purchase_unit' => 'Fijo', 'purchase_price' => 50, 'recipe_unit' => 'Receta', 'conversion_factor' => 1, 'overhead_type' => 'fixed'],
        ];

        foreach ($items as $item) {
            Item::create($item);
        }
    }
}
