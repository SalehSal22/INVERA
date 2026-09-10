<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeServiceCommand extends Command
{
    // The command signature
    protected $signature = 'make:service {name}';

    protected $description = 'Create a new service class';

    public function handle()
    {
        $name = $this->argument('name');
        
        // Define the path (App/Services)
        $path = app_path("Services/{$name}.php");

        // Check if file already exists
        if (File::exists($path)) {
            $this->error("Service {$name} already exists!");
            return;
        }

        // Create the directory if it doesn't exist
        File::ensureDirectoryExists(app_path('Services'));

        // Define the content template
        $content = "<?php\n\nnamespace App\\Services;\n\nclass {$name}\n{\n    //\n}";

        // Write the file
        File::put($path, $content);

        $this->info("Service {$name} created successfully.");
    }
}