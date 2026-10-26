# Esquema de Base de Datos y Arquitectura

El sistema utiliza SQLite para facilitar la distribución en entornos locales sin requerir la instalación de motores de bases de datos pesados como MySQL o PostgreSQL.

## El Patrón "Ítem Genérico" (Single Table Design)

En lugar de tener tablas separadas para `ingredients`, `labors`, `packagings`, etc., se optó por unificados en una sola tabla principal: **`items`**.

### Tabla `items`
Esta tabla representa cualquier entidad que represente un costo para la pyme.
* `type`: (enum) Clasifica el comportamiento del ítem (`ingredient`, `labor`, `packaging`, `direct_cost`, `overhead`, `preparation`).
* `category`: (string) Agrupación visual y lógica (Carnes, Lácteos, Personal).
* `name`: Nombre descriptivo.
* `purchase_unit`: Cómo se compra o paga (Kg, Mes, Litro, Maple). Para preparaciones se usa `Lote`.
* `purchase_price`: Cuánto se paga por esa unidad de compra. En preparaciones el costo se calcula desde su receta, no se edita.
* `recipe_unit`: Cómo se descuenta o asigna en una receta (Gr, Minutos, Unidades).
* `conversion_factor`: (Decimal) Factor multiplicador que relaciona la unidad de compra con la de receta. (Si compras 1 Kg y usas Gr, el factor es 1000). En preparaciones equivale al rendimiento de la receta.
* `overhead_type`: (enum) Usado solo si el `type` es `overhead` (costo indirecto). Permite definir si es un porcentaje (`percentage`) sobre el costo total o un monto fijo (`fixed`) por receta.
* `recipe_id`: (FK nullable, único) Receta que produce el ítem cuando `type = preparation`.

## Formulación de Recetas

El costeo de recetas no se hace directamente relacionando Receta -> Ítem, sino que se introduce la obligatoriedad de **Etapas**. 

1. **`recipes`**: Tabla cabecera que guarda el nombre de la receta y metadatos.
   * `yield_quantity`: cuánto produce el lote (ej. 1000).
   * `yield_unit`: unidad en la que se consume el rendimiento (ej. `g`, `unidad`, `porción`).
2. **`stages`**: Cada receta tiene una o más etapas (Ej. Preparación, Horneado). 
   * Tienen un `order` (para mantener la secuencia) y `duration_minutes` (crucial para calcular proporciones de mano de obra y servicios).
3. **`recipe_items`**: Tabla pivote o de detalle. Relaciona un `Item` con una `Stage` específica de una `Recipe`. 
   * `quantity`: Almacena la cantidad consumida **expresada en la unidad de la receta** (`recipe_unit`). 

### Lógica del Motor de Costeo
El costo de un `RecipeItem` (en una receta) se calcula mediante métodos centralizados en el modelo `App\Models\Recipe`:

1. **Costos Directos (`calculateDirectCost`)**:
   Para los tipos `ingredient`, `labor`, `packaging` y `direct_cost`:
   ```php
   $costo_unidad_receta = $item->purchase_price / max($item->conversion_factor, 0.0001);
   $costo_item = $costo_unidad_receta * $recipeItem->quantity;
   ```

2. **Costos Indirectos (`overhead`) (`calculateOverheadCost`)**:
   - **Fijo (`fixed`)**: El monto fijado multiplicado por la cantidad aplicada:
     ```php
     $costo_item = $item->purchase_price * $recipeItem->quantity;
     ```
   - **Porcentual (`percentage`)**: Aplica el porcentaje asignado sobre el subtotal de costos directos de la receta:
     ```php
     $costo_item = $subtotal_directo * ($item->purchase_price / 100) * $recipeItem->quantity;
     ```

3. **Costo Total (`calculateTotalCost`)**:
   ```php
   $costo_total = $subtotal_directo + $total_overhead;
   ```

## Preparaciones (sub-recetas)

Una receta puede publicarse como ítem activando **"Usar esta receta como ítem"**. Esto crea un ítem de tipo `preparation` vinculado a la receta (`items.recipe_id`) con:

* `purchase_unit = Lote`
* `recipe_unit = recipes.yield_unit`
* `conversion_factor = recipes.yield_quantity`
* `purchase_price = 0` (el costo real es calculado)

El costo unitario se obtiene con `Item::unitCost()`:

```php
costo_unitario = receta.calculateTotalCost() / receta.yield_quantity
```

Como ese cálculo se hace en tiempo de render, cualquier cambio en el precio de un insumo se refleja automáticamente en todas las recetas que usan la preparación. El servicio `App\Services\CostCalculator` centraliza el cálculo recursivo (incluyendo overheads) y detecta dependencias circulares. El servicio `App\Services\PreparationService` mantiene sincronizado el ítem con su receta.

Reglas principales:

* No se puede usar una preparación dentro de sí misma ni generar un ciclo (A → B → A).
* Una receta cuya preparación está siendo usada por otra receta no puede eliminarse.
* El ítem de preparación no se edita desde `/items`: su nombre, unidad, rendimiento y costo provienen de la receta. Solo su categoría es editable.
* La exportación JSON incluye las recetas dependientes en `dependencies` y la importación las crea antes de la receta principal.

