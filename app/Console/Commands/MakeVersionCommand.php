<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeVersionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:version 
                            {version? : The new version number (e.g. v1.1.0). If empty, patch version will be incremented.} 
                            {--m|message= : The changelog message/description. Use "|" to separate multiple lines.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new version, update version.json, and append to CHANGELOG.md';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $versionJsonPath = base_path('version.json');
        $changelogPath = base_path('CHANGELOG.md');

        // 1. Get current version
        if (!File::exists($versionJsonPath)) {
            $this->error('version.json not found! Please create it first.');
            return 1;
        }

        $currentData = json_decode(File::get($versionJsonPath));
        $currentVersion = $currentData->version ?? 'v0.0.0';
        
        // 2. Determine new version
        $newVersion = $this->argument('version');
        
        if (!$newVersion) {
            // Auto-increment patch version
            // Remove 'v' prefix if exists
            $v = ltrim($currentVersion, 'v');
            $parts = explode('.', $v);
            
            if (count($parts) >= 3) {
                $parts[count($parts) - 1]++;
                $newVersion = 'v' . implode('.', $parts);
            } else {
                $newVersion = $currentVersion . '.1';
            }
        }

        if (!str_starts_with($newVersion, 'v')) {
            $newVersion = 'v' . $newVersion;
        }

        // 3. Get message (Multi-line support)
        $messageInput = $this->option('message');
        $messages = [];

        if ($messageInput) {
            // Split by pipe character if provided in argument
            $messages = explode('|', $messageInput);
        } else {
            // Interactive mode loop
            $this->info("Enter changelog details for version {$newVersion}.");
            $this->info("Enter one line at a time. Leave empty and press ENTER to finish.");
            
            while (true) {
                $line = $this->ask('Description');
                if (empty($line)) {
                    break;
                }
                $messages[] = $line;
            }

            if (empty($messages)) {
                $messages[] = 'Maintenance update.';
            }
        }

        // Format messages as bullet points
        $formattedMessage = "";
        foreach ($messages as $msg) {
            $formattedMessage .= "- " . trim($msg) . "\n";
        }

        // 4. Update version.json
        $newData = [
            'version' => $newVersion,
            'release_date' => date('Y-m-d'),
        ];
        
        File::put($versionJsonPath, json_encode($newData, JSON_PRETTY_PRINT));
        $this->info("Updated version.json to {$newVersion}");

        // 5. Update CHANGELOG.md
        if (File::exists($changelogPath)) {
            $content = File::get($changelogPath);
            
            $date = date('Y-m-d');
            $newEntry = "## [{$newVersion}] - {$date}\n{$formattedMessage}";
            
            // Insert after the header or at the top
            // Assuming standard format "# Changelog\n\n..."
            if (preg_match('/# Changelog\s+/', $content)) {
                $content = preg_replace('/(# Changelog\s+)/', "$1\n{$newEntry}\n", $content, 1);
            } else {
                $content = "# Changelog\n\n{$newEntry}\n" . $content;
            }

            File::put($changelogPath, $content);
            $this->info("Updated CHANGELOG.md");
        } else {
            // Create if not exists
            $date = date('Y-m-d');
            $content = "# Changelog\n\n## [{$newVersion}] - {$date}\n{$formattedMessage}";
            File::put($changelogPath, $content);
            $this->info("Created CHANGELOG.md");
        }

        $this->info("Version {$newVersion} created successfully!");
        
        return 0;
    }
}
