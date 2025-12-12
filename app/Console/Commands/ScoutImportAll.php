<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ScoutImportAll extends Command
{
    protected $signature = 'scout:import-all';

    protected $description = 'Index and import all models configured for Laravel Scout';

    public function handle()
    {
        $models = config('scout.typesense.model-settings', []);
        foreach ($models as $class => $schema) {
            $this->call('scout:index', ['name' => $class]);
            $this->call('scout:import', ['model' => $class]);
        }
        $this->info('All Scout models imported!');
    }
}
