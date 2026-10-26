<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use ZipArchive;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use FilesystemIterator;

class CreatePortablePackage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-portable-package {--name=recetas-portable.zip : Nombre del archivo ZIP de salida}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Empaqueta la aplicación completa con FrankenPHP, vendor y base de datos en un ZIP listo para Windows sin dependencias.';

    /**
     * Carpetas y archivos a excluir del proyecto Laravel al empaquetar.
     */
    protected $exclusions = [
        'node_modules',
        '.git',
        '.github',
        '.idea',
        '.vscode',
        'dist',
        'tests',
        'storage/logs',
        'storage/framework/cache',
        'storage/framework/sessions',
        'storage/framework/views',
        'storage/framework/testing',
    ];

    /**
     * Archivos que se excluirán por coincidencia exacta.
     */
    protected $fileExclusions = [
        '.phpunit.result.cache',
        'phpunit.xml',
        'deploy.zip',
        'recetas-portable.zip',
        'hot', // Evita que Laravel busque el servidor de Vite dev
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $zipName = $this->option('name');
        $zipPath = base_path($zipName);
        $portableDir = base_path('dist/portable');

        if (!file_exists($portableDir . '/frankenphp.exe')) {
            $this->error("No se encontró frankenphp.exe en 'dist/portable'. Asegúrate de descargarlo primero.");
            return Command::FAILURE;
        }

        $this->info("📦 Preparando paquete portable '{$zipName}'...");

        if (file_exists($zipPath)) {
            $this->warn("Reemplazando paquete anterior: {$zipName}");
            unlink($zipPath);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->error("No se pudo crear el archivo ZIP.");
            return Command::FAILURE;
        }

        $count = 0;

        // 1. Agregar archivos binarios de FrankenPHP y configuración en la raíz del ZIP
        $this->info("⚙️ Empaquetando binarios de FrankenPHP y entorno de ejecución...");
        $portableIterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($portableDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($portableIterator as $file) {
            $relativePath = str_replace('\\', '/', str_replace($portableDir . DIRECTORY_SEPARATOR, '', $file->getPathname()));
            
            // Omitir descargas temporales si hubiese
            if (str_ends_with($relativePath, '.zip')) {
                continue;
            }

            if ($file->isDir()) {
                $zip->addEmptyDir($relativePath);
            } elseif ($file->isFile()) {
                $zip->addFile($file->getPathname(), $relativePath);
                $count++;
            }
        }

        // 2. Generar el lanzador Iniciar-Recetas.bat optimizado en la raíz del ZIP
        $batContent = "@echo off\r\n"
            . "title Recetas Pyme - Sistema de Costos\r\n"
            . "cd /d \"%~dp0\"\r\n\r\n"
            . "echo ========================================================\r\n"
            . "echo       Iniciando Sistema de Costeo de Recetas Pyme\r\n"
            . "echo ========================================================\r\n"
            . "echo.\r\n"
            . "echo Servidor iniciado en: http://localhost:8080\r\n"
            . "echo Para cerrar el sistema, simplemente cierra esta ventana.\r\n"
            . "echo.\r\n\r\n"
            . "start \"\" \"http://localhost:8080\"\r\n"
            . "frankenphp.exe php-server -r \"public\" -l \"127.0.0.1:8080\"\r\n";
        
        $zip->addFromString('Iniciar-Recetas.bat', $batContent);
        $count++;

        // 3. Generar el archivo .env de producción autónomo para el cliente
        $appKey = config('app.key') ?: 'base64:3mG/q3n/O+nB/5e6s1s+d7H9I3q4W7+s0eF1v3a4b5c=';
        $envContent = "APP_NAME=\"Recetas Pyme\"\r\n"
            . "APP_ENV=production\r\n"
            . "APP_KEY={$appKey}\r\n"
            . "APP_DEBUG=false\r\n"
            . "APP_TIMEZONE=America/Argentina/Buenos_Aires\r\n"
            . "APP_URL=http://localhost:8080\r\n\r\n"
            . "APP_LOCALE=es\r\n"
            . "APP_FALLBACK_LOCALE=es\r\n"
            . "APP_FAKER_LOCALE=es_ES\r\n\r\n"
            . "DB_CONNECTION=sqlite\r\n"
            . "DB_DATABASE=database/database.sqlite\r\n\r\n"
            . "SESSION_DRIVER=file\r\n"
            . "SESSION_LIFETIME=120\r\n"
            . "CACHE_STORE=file\r\n"
            . "QUEUE_CONNECTION=sync\r\n";
        
        $zip->addFromString('.env', $envContent);
        $count++;

        // 4. Empaquetar el proyecto Laravel (con vendor, base de datos SQLite poblada y assets)
        $this->info("📂 Empaquetando aplicación Laravel, base de datos SQLite y librerías...");
        $basePath = base_path();
        $projectIterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($basePath, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($projectIterator as $file) {
            $relativePath = str_replace('\\', '/', str_replace($basePath . DIRECTORY_SEPARATOR, '', $file->getPathname()));

            if ($this->shouldExclude($relativePath)) {
                continue;
            }

            if ($file->isDir()) {
                $zip->addEmptyDir($relativePath);
            } elseif ($file->isFile()) {
                $zip->addFile($file->getPathname(), $relativePath);
                $count++;
            }
        }

        // Aseguramos que existan los directorios de storage vacíos requeridos por Laravel
        $storageDirs = [
            'storage/framework/cache/data',
            'storage/framework/sessions',
            'storage/framework/views',
            'storage/logs',
        ];
        foreach ($storageDirs as $sDir) {
            $zip->addEmptyDir($sDir);
        }

        $zip->close();

        $sizeMb = number_format(filesize($zipPath) / 1048576, 2);

        $this->info("========================================================");
        $this->info("✅ Paquete portable generado exitosamente:");
        $this->info("📁 Archivo: {$zipPath}");
        $this->info("📦 Total de archivos empaquetados: {$count}");
        $this->info("💾 Tamaño: {$sizeMb} MB");
        $this->info("========================================================");
        $this->info("El cliente solo debe descomprimir el ZIP y hacer doble clic en 'Iniciar-Recetas.bat'.");

        return Command::SUCCESS;
    }

    /**
     * Determina si una ruta del proyecto Laravel debe excluirse.
     */
    protected function shouldExclude(string $relativePath): bool
    {
        foreach ($this->exclusions as $exclusion) {
            if (str_starts_with($relativePath, $exclusion)) {
                return true;
            }
        }

        $basename = basename($relativePath);

        if (in_array($basename, $this->fileExclusions)) {
            return true;
        }

        // Excluir cualquier archivo .zip de salida
        if (str_ends_with($basename, '.zip')) {
            return true;
        }

        // Excluir archivos ocultos (excepto requeridos)
        if (str_starts_with($basename, '.') && !in_array($basename, ['.editorconfig', '.gitattributes'])) {
            return true;
        }

        return false;
    }
}
