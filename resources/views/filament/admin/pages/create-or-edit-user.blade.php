<x-filament::page>

    <div class="relative mb-6">
        <h2 class="text-xl font-bold text-center text-gray-900 dark:text-gray-100">
            Create New User
        </h2>
    </div>

    {{-- LIMIT MESSAGE --}}
    @if ($limitReached)
        <div class="mb-4 text-center text-red-600 dark:text-red-400 font-medium">
            Your user creation limit is reached. Please contact admin.
        </div>
    @endif

    <div class="shadow rounded-lg p-6
                bg-white dark:bg-gray-800
                text-gray-900 dark:text-gray-100">

        <form wire:submit.prevent="saveUser">
            {{ $this->form }}

            <div class="mt-4">
                <x-filament::button
                    type="submit"
                    :disabled="$limitReached"
                >
                    Create User
                </x-filament::button>
            </div>
        </form>

    </div>

</x-filament::page>
