# 🍳 Calculadora de Costos de Recetas - Pyme

Un sistema web moderno y ligero desarrollado en **Laravel 13** para la gestión y cálculo exacto de costos gastronómicos en Pequeñas y Medianas Empresas (Pymes). 

Permite administrar materias primas, manos de obra, empaques, costos directos e indirectos, estructurar recetas divididas en etapas de producción y calcular el costo unitario y total en tiempo real.

---

## 🚀 Stack Tecnológico

- **Backend:** [Laravel 13](https://laravel.com/) (PHP 8.2+)
- **Base de Datos:** SQLite (Portabilidad ideal para entornos locales y despliegues sin dependencias de servicios externos)
- **Frontend / Reactividad:** [Livewire 3](https://livewire.laravel.com/) + [Volt](https://livewire.laravel.com/docs/volt) (Single-File Components)
- **UI Framework:** [MaryUI](https://mary-ui.com/) + [Tailwind CSS v4](https://tailwindcss.com/)
- **Iconos:** [Lucide Icons](https://lucide.dev/)
- **Bundler:** [Vite](https://vitejs.dev/)

---

## 📋 Características Principales

1. **Gestión Unificada de Ítems:** Catálogo general con 6 tipos: materias primas (`ingredient`), mano de obra (`labor`), empaques (`packaging`), costos directos de elaboración (`direct_cost`), costos indirectos (`overhead` fijos o porcentuales) y sub-recetas (`preparation`).
2. **Factores de Conversión de Unidades:** Compra en cajones, maples, kilogramos o bolsas, pero costea tus recetas en gramos, unidades o centímetros cúbicos de forma automática.
3. **Recetas por Etapas:** Estructura recetas por fases de elaboración (ej. Preparación, Cocción, Armado), asignando tiempos y vinculando ítems a cada fase con buscador interactivo y selector `x-choices`.
4. **Motor de Costeo en Tiempo Real:** Métodos centralizados en el modelo `Recipe` (`calculateDirectCost`, `calculateOverheadCost`, `calculateTotalCost` y `calculateItemCost`), soportando costos indirectos porcentuales aplicados sobre la base directa.
5. **Preparaciones / Sub-recetas:** Cualquier receta puede transformarse en un insumo (`type = preparation`) indicando su rendimiento para consumirse dentro de otras recetas (ej. pan de hamburguesa, salsa base), con recálculo dinámico de costos.
6. **Dashboard Ejecutivo:** Indicadores clave, desglose de costos directos vs indirectos y métricas de insumos más representativos.

---

## 🛠️ Requisitos Previos

Asegúrate de contar con lo siguiente en tu sistema:

- **PHP** >= 8.2 con extensiones habilitadas: `pdo_sqlite`, `mbstring`, `openssl`, `curl`, `fileinfo`.
- **Composer** >= 2.x
- **Node.js** >= 18.x y **npm**
- *(Opcional en Windows/Mac)*: [Laravel Herd](https://herd.laravel.com/)

---

## ⚙️ Instalación y Puesta en Marcha

Sigue estos pasos para compilar y ejecutar el proyecto localmente:

### 1. Clonar el Repositorio
```bash
git clone https://github.com/quileab/recetas.git
cd recetas
```

### 2. Instalar Dependencias de PHP
```bash
composer install
```

### 3. Configurar el Archivo de Entorno
Copia el archivo `.env.example` para crear tu `.env` y genera la clave de cifrado:
```bash
cp .env.example .env
php artisan key:generate
```

*(Nota en Windows PowerShell: `Copy-Item .env.example .env`)*

Por defecto, el archivo `.env` viene configurado para usar **SQLite**.

### 4. Inicializar la Base de Datos
Ejecuta las migraciones y carga los datos de ejemplo (semilla de insumos y recetas de demostración):
```bash
php artisan migrate --seed
```
*Si la base de datos `database/database.sqlite` no existe, Laravel te consultará para crearla automáticamente.*

### 5. Instalar Dependencias de Node y Compilar Assets
Instala los paquetes frontend y compila los estilos Tailwind v4 y componentes MaryUI:

- **Para desarrollo (con hot reload):**
  ```bash
  npm install
  npm run dev
  ```

- **Para producción:**
  ```bash
  npm install
  npm run build
  ```

### 6. Ejecutar el Servidor Local
En una nueva terminal, inicia el servidor de desarrollo de Laravel:
```bash
php artisan serve
```

Si deseas que la aplicación esté disponible para otras computadoras o dispositivos en la misma red local (LAN / Wi-Fi):
```bash
php artisan serve --host=0.0.0.0 --port=8000
```

Accede desde tu navegador a: **[http://localhost:8000](http://localhost:8000)**

---

## 📦 Generación de Paquete para Despliegue

El proyecto incluye un comando Artisan personalizado para generar paquetes de despliegue compactos en formato ZIP (excluyendo automáticamente la carpeta `vendor`, archivos ocultos y temporales):

```bash
php artisan app:create-deploy-package
```

---

## 📚 Documentación Técnica Adicional

En el directorio [`/docs`](docs/) encontrarás especificaciones de diseño y arquitectura:
- [Modelo de Base de Datos y Patrones](docs/DATABASE_SCHEMA.md)
- [ADR-001: Decisiones sobre Conversión de Unidades](docs/ADR-001-unit-conversions.md)
- [ADR-002: Modelo de Costeo y Tratamiento de Costos Indirectos (Overhead)](docs/ADR-002-costing-model-and-overhead.md)
- [ADR-003: Recetas como Ítems (Preparaciones / Sub-recetas)](docs/ADR-003-recipes-as-items.md)
- [Guía de Despliegue y Distribución](docs/DEPLOYMENT.md)

---

## 📄 Licencia

Este proyecto está bajo la licencia [MIT](LICENSE).
