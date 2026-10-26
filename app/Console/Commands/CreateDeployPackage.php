<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use ZipArchive;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use FilesystemIterator;

class CreateDeployPackage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-deploy-package {--name=deploy.zip : El nombre del archivo zip}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crea un paquete ZIP de despliegue excluyendo archivos innecesarios.';

    /**
     * Carpetas y archivos a excluir de forma exacta o parcial.
     */
    protected $exclusions = [
        'vendor',
        'node_modules',
        '.git',
        '.github',
        '.idea',
        '.vscode',
        'storage/logs',
        'storage/framework/cache',
        'storage/framework/sessions',
        'storage/framework/views',
        'storage/framework/testing',
        'database/database.sqlite',
        'tests',
    ];

    /**
     * Archivos que se excluirán por coincidencia exacta o por extensión.
     */
    protected $fileExclusions = [
        '.env',
        '.phpunit.result.cache',
        'phpunit.xml',
        'deploy.zip' // En caso de que se genere con el nombre por defecto
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $zipName = $this->option('name');
        $zipPath = base_path($zipName);

        $this->info("Iniciando la creación del paquete de despliegue: {$zipName}...");

        if (file_exists($zipPath)) {
            $this->warn("El archivo {$zipName} ya existe. Será reemplazado.");
            unlink($zipPath);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->error("No se pudo crear el archivo ZIP.");
            return Command::FAILURE;
        }

        $basePath = base_path();
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($basePath, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $count = 0;

        foreach ($iterator as $file) {
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

        $zip->close();

        $size = number_format(filesize($zipPath) / 1048576, 2);
        
        $this->info("✅ Paquete de despliegue creado con éxito: {$zipPath}");
        $this->info("📦 Se empaquetaron {$count} archivos. Tamaño: {$size} MB.");

        return Command::SUCCESS;
    }

    /**
     * Determina si un archivo o directorio debe ser excluido.
     */
    private function shouldExclude(string $relativePath): bool
    {
        // Revisar exclusiones de carpetas
        foreach ($this->exclusions as $exclusion) {
            if (str_starts_with($relativePath, $exclusion)) {
                return true;
            }
        }

        // Revisar exclusiones de archivos específicos o dotfiles
        $basename = basename($relativePath);
        
        if (in_array($basename, $this->fileExclusions)) {
            return true;
        }

        // Excluir cualquier archivo oculto (dot file) excepto configuraciones necesarias
        // Permitimos .env.example, .editorconfig, etc.
        if (str_starts_with($basename, '.') && !in_array($basename, ['.env.example', '.editorconfig', '.gitattributes', '.gitignore'])) {
            return true;
        }

        return false;
    }
}
