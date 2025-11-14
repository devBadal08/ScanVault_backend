<x-filament::page>

    <!-- Logo Section -->
    <div class="flex justify-center mb-6 mt-6">
        <img src="{{ asset('logo.png') }}" class="h-20" alt="App Logo">
    </div>

    <form wire:submit.prevent="authenticate" class="space-y-6 max-w-sm mx-auto">
        {{ $this->form }}

        <x-filament::button type="submit" class="w-full">
            Login
        </x-filament::button>
    </form>

</x-filament::page>
