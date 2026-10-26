# Architecture Decision Records (ADRs)

## ADR-001: Modelo de Conversión de Unidades y Costeo

### Contexto
El sistema requiere calcular el costo de los ítems (materias primas, mano de obra, empaques y costos indirectos) cuando son asignados a una receta. Existen casos donde la unidad de compra difiere de la unidad utilizada en la receta (ej. comprar en Kilos, usar en Gramos; comprar un Maple, usar en Unidades). 

Se consideró la opción de implementar un "Helper" o motor estricto de conversión de unidades en el backend (ej. un conversor que sepa que 1 Kg = 1000 Gr).

### Decisión
Se decidió **NO** implementar un conversor estricto de unidades en el código y, en su lugar, utilizar un modelo de **Factor de Conversión Numérico Manual** en la base de datos (columna `conversion_factor` en la tabla `items`).

### Justificación
La realidad operativa de la industria gastronómica es altamente irregular y no obedece a conversiones físicas estándar en todos los casos:
1. **Unidades de Compra Arbitrarias:** Los restaurantes y pymes compran insumos en formatos no estándar como "Cajones" (que pueden pesar 18kg), "Maples" (que traen 30 unidades) o "Bidones".
2. **Mano de Obra y Overhead:** Un sueldo se paga de forma "Mensual", pero en la receta se costea por "Minuto". El factor de conversión mensual a minutos depende del contrato del empleado y horas de trabajo, no es una constante matemática universal.
3. **Mantenibilidad:** Programar y mantener todas las posibles unidades personalizadas en un motor estricto añadiría una complejidad técnica innecesaria ("Overhead").

### Implementación
La tabla `items` almacena:
- `purchase_price` (El precio que paga la pyme, ej: $15000).
- `purchase_unit` (Texto descriptivo de cómo lo compra, ej: "Cajón", "Kg", "Mes").
- `recipe_unit` (Texto descriptivo de cómo lo usa, ej: "Gr", "Unidad", "Minuto").
- `conversion_factor` (La cantidad de unidades de receta que contiene la unidad de compra, ej: 18000 para un cajón de 18kg usado en gramos).

La matemática del motor de costeo (en el modelo/componente) siempre es:
`Costo por Unidad de Receta = purchase_price / conversion_factor`.

Esto permite flexibilidad absoluta, manteniendo el código limpio, escalable y cubriendo el 100% de los casos de uso (desde gramos de harina hasta horas de cocinero y luz).
