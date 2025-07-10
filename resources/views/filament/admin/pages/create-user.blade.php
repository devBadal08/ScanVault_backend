<x-filament::page>
    <div class="max-w-xl mx-auto space-y-4">
        @if (session()->has('success'))
            <div class="p-4 mb-4 text-green-700 bg-green-100 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        {{-- ✅ Show alert if max limit reached --}}
        @if(isset($remainingSlots) && $remainingSlots === 0)
            <div class="p-4 mb-4 text-red-700 bg-red-100 rounded-lg">
                You have reached your maximum user creation limit. You cannot create more users.
            </div>
        @endif

        <form wire:submit.prevent="create">
            {{ $this->form }}

            <x-filament::button 
                type="submit" 
                class="mt-4"
                :disabled="isset($remainingSlots) && $remainingSlots === 0"
                :class="(isset($remainingSlots) && $remainingSlots === 0) ? 'opacity-50 cursor-not-allowed' : ''"
            >
                Create User
            </x-filament::button>
        </form>
    </div>
</x-filament::page>
