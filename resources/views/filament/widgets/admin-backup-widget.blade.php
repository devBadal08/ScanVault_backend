<x-filament::widget>
    <x-filament::card class="relative">

        {{-- Overlay loader with progress --}}
        @if($isProcessing)
            <div class="absolute inset-0 z-10 bg-white/70 flex items-center justify-center rounded-lg">
                <div class="flex flex-col items-center gap-2 text-sm font-medium text-gray-700">
                    <x-filament::loading-indicator class="h-5 w-5" />

                    <div>Preparing backup…</div>

                    @if($totalFiles > 0)
                        <div class="text-xs text-gray-600">
                            {{ number_format($processedFiles) }}
                            /
                            {{ number_format($totalFiles) }}
                            files prepared
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold">Company Backup</h2>
                <p class="text-sm text-gray-600">
                    Download all files of your company in a single ZIP archive.
                </p>
            </div>

            <x-filament::button
                wire:click="downloadAll"
                :disabled="$isProcessing"
                icon="heroicon-o-arrow-down-tray"
                color="primary"
            >
                {{ $isProcessing ? 'Preparing…' : 'Download Backup' }}
            </x-filament::button>
        </div>

    </x-filament::card>

    {{-- Poll progress every 2 seconds --}}
    <div wire:poll.2s="refreshProgress"></div>
</x-filament::widget>
