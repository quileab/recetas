# ADR-003: Recetas como Ítems (Preparaciones / Sub-recetas)

## Contexto
Una receta puede ser un producto final (hamburguesa) o un insumo de otra receta (pan, salsa, masa). El modelo original solo permitía que `recipe_items` apuntara a `items`, por lo que una receta no podía consumirse dentro de otra sin duplicar su costo manualmente y desactualizarlo cuando cambiaran los precios.

## Decisión
1. **Mantener el patrón de Ítem Genérico.** Se agregó el tipo `preparation` a `items` y una relación opcional `items.recipe_id` (única) hacia la receta que lo produce.
2. **Agregar rendimiento a las recetas.** `recipes.yield_quantity` y `recipes.yield_unit` definen cuántas unidades produce un lote y en qué unidad se consume (g, unidad, porción).
3. **Calcular el costo, no almacenarlo.** El costo unitario de una preparación es:
   ```php
   recipe.calculateTotalCost() / recipe.yield_quantity
   ```
   Se calcula en tiempo de render mediante `App\Services\CostCalculator`, por lo que siempre refleja los precios actuales de los insumos.
4. **Incluir overheads.** El costo transferido a la receta padre incluye los costos directos e indirectos de la preparación, igual que si se tratara de un producto terminado.
5. **Sincronizar automáticamente.** `App\Services\PreparationService` crea/actualiza el ítem cuando la receta se guarda. El ítem no se edita desde el catálogo: su nombre, unidad, rendimiento y costo provienen de la receta.
6. **Prevenir ciclos.** La UI y los servicios rechazan usar una preparación que dependa (directa o transitivamente) de la receta actual. `CostCalculator` también detecta ciclos como última barrera.
7. **Proteger la integridad.** No se puede eliminar una receta cuya preparación esté siendo usada por otra receta. La importación crea las dependencias en orden topológico y detecta ciclos.

## Consecuencias
- El costo de las recetas anidadas se mantiene consistente sin intervención manual.
- El catálogo y los filtros de `/items` distinguen claramente las preparaciones.
- Exportar una receta incluye sus sub-recetas (`dependencies`), y el formato JSON sube a la versión `1.1`.
- El cálculo es recursivo: recetas muy profundas agregan costo de CPU, mitigado por la profundidad natural del recetario y la detección de ciclos.
- Las recetas existentes obtienen por defecto rendimiento `1 unidad`; conviene ajustarlo para mostrar un costo por porción significativo.
