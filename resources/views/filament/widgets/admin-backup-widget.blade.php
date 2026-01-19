<x-filament::widget>
    <x-filament::card>
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold">Company Backup</h2>
                <p class="text-sm text-gray-600">
                    Download all files of your company in a single ZIP archive.
                </p>
            </div>

            <x-filament::button
                wire:click="downloadAll"
                icon="heroicon-o-arrow-down-tray"
                color="primary"
            >
                Download Backup
            </x-filament::button>
        </div>
    </x-filament::card>
</x-filament::widget>
