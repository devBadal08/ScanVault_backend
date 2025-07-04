<x-filament::page>
    <div class="max-w-xl mx-auto space-y-4">
        @if (session()->has('success'))
            <div class="p-4 mb-4 text-green-700 bg-green-100 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        <form wire:submit.prevent="create">
            {{ $this->form }}

            <x-filament::button type="submit" class="mt-4">
                Create User
            </x-filament::button>
        </form>
    </div>
</x-filament::page>
