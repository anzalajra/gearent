<?php
# php artisan project:export

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ExportProject extends Command
{
    protected $signature = 'project:export {--output=project-export.txt}';
    protected $description = 'Export project structure and file contents';

    public function handle()
    {
        $output = $this->option('output');
        $content = "PROJECT EXPORT: " . config('app.name') . "\n";
        $content .= "Generated: " . now()->toDateTimeString() . "\n";
        $content .= str_repeat('=', 80) . "\n\n";

        $folders = [
            'app/Models',
            'app/Filament',
            'app/Livewire',
            'app/Enums',
            'database/migrations',
            'routes',
        ];

        foreach ($folders as $folder) {
            $path = base_path($folder);
            
            if (!File::isDirectory($path)) {
                continue;
            }

            $files = File::allFiles($path);
            
            foreach ($files as $file) {
                if ($file->getExtension() === 'php') {
                    $relativePath = str_replace(base_path() . '/', '', $file->getPathname());
                    $content .= "\n" . str_repeat('=', 80) . "\n";
                    $content .= "FILE: {$relativePath}\n";
                    $content .= str_repeat('=', 80) . "\n\n";
                    $content .= File::get($file->getPathname());
                    $content .= "\n";
                }
            }
        }

        File::put(base_path($output), $content);
        
        $this->info("Project exported to: {$output}");
        $this->info("File size: " . number_format(filesize(base_path($output))) . " bytes");
    }
}
