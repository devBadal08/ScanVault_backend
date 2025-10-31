<x-filament::page>
    <form wire:submit.prevent="authenticate" class="space-y-6 max-w-sm mx-auto mt-12">
        {{ $this->form }}
        <x-filament::button type="submit" class="w-full">
            Login
        </x-filament::button>
    </form>
</x-filament::page>
