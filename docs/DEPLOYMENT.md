# Estrategia de Despliegue y Distribución

Dado que esta aplicación está diseñada para Pymes que pueden no contar con un servidor en la nube, el despliegue está enfocado en **Entornos Locales (On-Premise)** y redes LAN.

## Comandos de Empaquetado

### 1. Paquete Portable Completo (Para el Cliente Final - Zero Dependencias)
Para entregar al cliente final que **no tiene PHP ni Composer instalados**:

```bash
php artisan app:create-portable-package
```

**¿Qué hace este comando?**
Genera un archivo `recetas-portable.zip` (~76 MB) que incluye:
- El binario oficial de **FrankenPHP + PHP 8.5** para Windows x64.
- La carpeta **`vendor/`** completa con todas las dependencias de Laravel y Livewire.
- La base de datos SQLite poblada (`database/database.sqlite`).
- Los assets frontend ya compilados en `public/build/`.
- El archivo de entorno `.env` de producción preconfigurado con SQLite.
- El lanzador **`Iniciar-Recetas.bat`**.

El cliente final solo descomprime el ZIP en cualquier carpeta y hace **doble clic en `Iniciar-Recetas.bat`**.

---

### 2. Paquete de Código Fuente Liviano (Para Servidores / Developers)
Si vas a desplegar en un servidor que ya dispone de PHP y Composer:

```bash
php artisan app:create-deploy-package
```

Genera un `.zip` liviano excluyendo `vendor`, `node_modules` y dotfiles.

3. Se crea el archivo de base de datos SQLite y se ejecutan las migraciones: `php artisan migrate --force`.
4. (Opcional, si no se usó NativePHP o Inno Setup), se levanta el servidor local o se configura con un web server ligero o portátil (como un binario PHP empaquetado).

## Opciones de Ejecución Final
Recomendamos las siguientes vías para el cliente final en Windows:

1. **FrankenPHP Portable (Recomendada - Cero Dependencias):**
   - Se distribuye la carpeta empaquetada que contiene `frankenphp.exe` + `php.ini` + `Caddyfile` + `Iniciar-Recetas.bat` + la aplicación.
   - El cliente **no necesita tener instalado PHP, Composer ni servidores**.
   - Solo hace doble clic en `Iniciar-Recetas.bat`: levanta el servidor Caddy/FrankenPHP en el puerto 8080 y abre automáticamente el navegador en `http://localhost:8080`.
   - Peso total comprimido en ZIP: ~40-50 MB.
2. **Inno Setup + FrankenPHP / Portable PHP:**
   - Permite compilar la carpeta portable en un único archivo instalador clásico `Instalador-Recetas.exe`.
   - Crea un acceso directo en el Escritorio con icono personalizado.
3. **Laravel Herd:**
   - La forma más amigable para desarrolladores o administradores en Windows/Mac.
