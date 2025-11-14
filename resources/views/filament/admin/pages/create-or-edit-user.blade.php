<x-filament::page>

    @if ($showFormPage)
        {{-- BACK BUTTON + HEADING --}}
        <div class="relative mb-6">
            <x-filament::button wire:click="goBack" color="primary">
                ← Back
            </x-filament::button>

            <h2 class="text-xl font-bold text-center text-gray-900 dark:text-gray-100">
                {{ $editingUserId ? 'Edit User' : 'Create New User' }}
            </h2>
        </div>

        {{-- FORM CARD --}}
        <div class="shadow rounded-lg p-6
                    bg-white dark:bg-gray-800
                    text-gray-900 dark:text-gray-100">
            <form wire:submit.prevent="saveUser">
                {{ $form }}

                <div class="mt-4">
                    <x-filament::button type="submit">
                        {{ $editingUserId ? 'Update User' : 'Create User' }}
                    </x-filament::button>
                </div>
            </form>
        </div>

    @else

        {{-- HEADER + NEW USER BUTTON --}}
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Users</h2>

            @if (is_null($remainingLimit) || $remainingLimit > 0)
                <x-filament::button wire:click="createNewUserPage" color="primary">
                    New User
                </x-filament::button>
            @else
                <x-filament::button color="gray" disabled
                    title="You have reached your maximum user creation limit.">
                    New User
                </x-filament::button>
            @endif
        </div>

        {{-- LIMIT INFO --}}
        @if (!is_null($remainingLimit))
            <div class="mb-4
                {{ $remainingLimit <= 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                You have created {{ $totalUsers }} of your {{ auth()->user()->max_limit }} allowed users and managers.
            </div>
        @endif

        {{-- TABLE --}}
        <table class="min-w-full rounded-lg shadow overflow-hidden mb-6
                      bg-white dark:bg-gray-800
                      text-gray-900 dark:text-gray-100
                      border border-gray-200 dark:border-gray-700">

            <thead>
                <tr class="bg-gray-100 dark:bg-gray-700
                           text-gray-600 dark:text-gray-200
                           uppercase text-sm leading-normal">
                    <th class="py-3 px-6 text-left">Name</th>
                    <th class="py-3 px-6 text-left">Email</th>
                    <th class="py-3 px-6 text-left">Role</th>
                    <th class="py-3 px-6 text-left">Actions</th>
                </tr>
            </thead>

            <tbody class="text-gray-600 dark:text-white text-sm">
                @foreach ($users as $user)
                    <tr class="border-b border-gray-200 dark:border-gray-700
                               hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="py-3 px-6">{{ $user->name }}</td>
                        <td class="py-3 px-6">{{ $user->email }}</td>
                        <td class="py-3 px-6 capitalize">{{ $user->role }}</td>
                        <td class="py-3 px-6">
                            <x-filament::button color="primary" size="sm"
                                wire:click="editUserPage({{ $user->id }})">
                                Edit
                            </x-filament::button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

    @endif

</x-filament::page>
