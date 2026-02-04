<?php

namespace App\Console\Commands;

use App\Models\Photo;
use Illuminate\Console\Command;

class FixPhotoCompanyId extends Command
{
    protected $signature = 'photos:fix-company';
    protected $description = 'Backfill company_id for photos';

    public function handle()
    {
        Photo::whereNull('company_id')
            ->chunkById(1000, function ($photos) {
                foreach ($photos as $photo) {
                    if ($photo->user && $photo->user->company_id) {
                        $photo->company_id = $photo->user->company_id;
                        $photo->save();
                    }
                }
            });

        $this->info('Photo company_id backfill completed.');
    }
}
