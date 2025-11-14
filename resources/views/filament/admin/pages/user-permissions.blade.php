<x-filament::page>

    <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-gray-100">
        Users in {{ $company->company_name }}
    </h2>

    {{-- USER LIST --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 mb-8">
        @foreach ($users as $user)
            <button wire:click="selectUser({{ $user->id }})"
                class="p-4 w-full text-left rounded-lg border transition
                       text-gray-900 dark:text-gray-100
                       border-gray-300 dark:border-gray-700
                       {{ $selectedUser && $selectedUser->id == $user->id
                            ? 'bg-green-100 dark:bg-green-800 border-green-500'
                            : 'bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                
                <strong>{{ $user->name }}</strong>
                <span class="text-sm text-gray-500 dark:text-white">
                    ({{ ucfirst($user->role) }})
                </span>
            </button>
        @endforeach
    </div>

    @if($selectedUser)

        {{-- PERMISSION SECTION --}}
        <div class="mt-8">
            <h3 class="text-2xl font-bold mb-4 text-gray-900 dark:text-gray-100">
                Permissions for {{ $selectedUser->name }}
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                {{-- Checkbox Item Template --}}
                @php
                    $items = [
                        'show_total_users' => 'Show Total Users',
                        'show_total_managers' => 'Show Total Managers',
                        'show_total_admins' => 'Show Total Admins',
                        'show_total_limit' => 'Show Total Limit',
                        'show_total_storage' => 'Show Total Storage',
                        'show_total_photos' => 'Show Total Photos',
                    ];
                @endphp

                @foreach ($items as $key => $label)
                    <label class="flex items-center p-4 rounded-lg shadow transition cursor-pointer
                                  bg-white dark:bg-gray-800
                                  hover:shadow-lg
                                  text-gray-900 dark:text-gray-100">
                        <input type="checkbox"
                               wire:model.defer="permissions.{{ $key }}"
                               class="form-checkbox h-5 w-5 text-blue-600 mr-3
                                      bg-white dark:bg-gray-700
                                      border-gray-300 dark:border-gray-600">
                        <span class="text-base">{{ $label }}</span>
                    </label>
                @endforeach

            </div>

            {{-- UPDATE BUTTON --}}
            <x-filament::button wire:click="savePermissions"
                class="mt-6 px-6 py-3 font-semibold rounded-lg transition
                       bg-blue-600 text-white hover:bg-blue-700
                       dark:bg-blue-700 dark:hover:bg-blue-800">
                Update Permissions
            </x-filament::button>
        </div>
    @endif

</x-filament::page>
