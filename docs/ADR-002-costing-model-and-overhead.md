# Architecture Decision Records (ADRs)

## ADR-002: Modelo de Costeo y Tratamiento de Costos Indirectos (Overhead)

### Contexto
En recetas gastronómicas y de producción artesanal, los costos se dividen en dos categorías principales:
1. **Costos Directos:** Insumos proporcionales a la elaboración que incluyen:
   - Materia Prima (`ingredient`)
   - Mano de Obra (`labor`)
   - Empaques y Envoltorios (`packaging`)
   - Insumos directos de cocción/operación (`direct_cost`, ej. gas de soplete, papel manteca, carbón vegetal, guantes).
2. **Costos Indirectos (`overhead`):** Gastos de soporte o márgenes que pueden cobrarse de dos maneras:
   - **Monto Fijo (`fixed`):** Un importe monetario fijo asignado a la receta o etapa (ej. luz/gas aproximado por lote, costo fijo de limpieza).
   - **Porcentaje (`percentage`):** Un coeficiente porcentual aplicado sobre el valor base de producción (ej. 5% de margen de merma/error, desperdicios o imprevistos).

Anteriormente, el cálculo de overhead porcentual se multiplicaba de forma errónea como un valor unitario directo ($5 × cantidad = $5.00), en lugar de representar un porcentaje real sobre el subtotal directo.

### Decisión
Se decidió:
1. **Centralizar la lógica de costeo en el modelo Eloquent `App\Models\Recipe`**, dotándolo de métodos reutilizables:
   - `calculateDirectCost()`: totaliza ingredientes, mano de obra, empaques y costos directos.
   - `calculateItemCost(RecipeItem $recipeItem, ?float $directCost = null)`: evalúa el costo individual de cualquier ítem; si es overhead porcentual, aplica `subtotal_directo * (purchase_price / 100) * quantity`.
   - `calculateOverheadCost(?float $directCost = null)`: totaliza fijos + porcentuales.
   - `calculateTotalCost()`: costo final consolidado (`directCost + overheadCost`).
2. **Reutilizar esta lógica en todas las interfaces:** tanto en la pantalla de detalle de receta ([`recipes.show`](file:///C:/qb/test/recetas/resources/views/livewire/recipes/show.blade.php)) como en el tablero principal ([`dashboard`](file:///C:/qb/test/recetas/resources/views/livewire/dashboard.blade.php)), garantizando una única fuente de verdad.

### Justificación
- **Consistencia Matemática:** Garantiza que el 5% de merma en una receta de $10.000 genere exactamente $500 de costo indirecto, tanto en el desglose de etapas como en las métricas ejecutivas del Dashboard.
- **Prevención de Código Muerto y Duplicación:** Evita tener algoritmos de costeo divergentes repartidos en múltiples vistas o controladores.
- **Transparencia en la UI:** El usuario observa claramente qué porción del costo corresponde a insumos y mano de obra directa y cuánto representan los márgenes/costos fijos asignados.
