<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class AddParentIdToTenantFolders extends Command
{
    protected $signature = 'tenant:add-parent-id';
    protected $description = 'Add parent_id column to folders table in all tenant databases';

    public function handle()
    {
        // 1️⃣ Get all company databases
        $companies = DB::connection('mysql')->table('companies')->pluck('database_name');

        foreach ($companies as $databaseName) {
            $this->info("Processing database: $databaseName");

            // 2️⃣ Switch tenant connection dynamically
            config(['database.connections.tenant.database' => $databaseName]);
            DB::purge('tenant');
            DB::reconnect('tenant');

            // 3️⃣ Add parent_id column if it does not exist
            if (!Schema::connection('tenant')->hasColumn('folders', 'parent_id')) {
                Schema::connection('tenant')->table('folders', function (Blueprint $table) {
                    $table->unsignedBigInteger('parent_id')->nullable()->after('name');
                });
                $this->info("Added parent_id column to folders table in $databaseName ✅");
            } else {
                $this->info("folders table already has parent_id in $databaseName, skipping ⚠️");
            }
        }

        $this->info('All tenant databases processed successfully!');
        return 0;
    }
}
