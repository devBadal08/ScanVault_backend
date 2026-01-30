<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Folder;

class BackfillFolderPaths extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:backfill-folder-paths';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Folder::orderBy('id')->chunk(100, function ($folders) {
            foreach ($folders as $folder) {
                if ($folder->path) {
                    continue;
                }

                $parts = [];
                $current = $folder;

                while ($current) {
                    array_unshift($parts, $current->name);
                    $current = $current->parent_id
                        ? Folder::find($current->parent_id)
                        : null;
                }

                $folder->path = $folder->company_id . '/' . $folder->user_id . '/' . implode('/', $parts);
                $folder->save();
            }
        });

        $this->info('Folder paths backfilled successfully.');
    }
}
