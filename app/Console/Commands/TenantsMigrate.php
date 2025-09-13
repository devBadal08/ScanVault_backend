<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class TenantsMigrate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:migrate {--fresh : Drop all tables and re-run all migrations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run tenant migrations for all company databases';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $companies = \App\Models\Company::all();

        foreach ($companies as $company) {
            $dbName = $company->database_name;

            if (!$dbName) {
                $this->warn("⚠️ Company {$company->company_name} has no database_name, skipping...");
                continue;
            }

            $this->info("Migrating tenant DB: {$dbName}");

            // Dynamically set tenant database
            config(['database.connections.tenant.database' => $dbName]);

            \Artisan::call('migrate', [
                '--database' => 'tenant',
                '--path' => '/database/migrations/tenant',
                '--force' => true,
            ]);

            $this->info("✅ Migrated {$dbName}");
        }
    }
}
