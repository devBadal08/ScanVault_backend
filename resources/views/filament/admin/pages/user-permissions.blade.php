<x-filament::page>

    {{-- BACK BUTTON --}}
    <div class="mb-6 flex items-center">
        <x-filament::button 
            tag="a"
            href="{{ route('filament.admin.pages.permissions') }}"
            color="primary"
            icon="heroicon-o-arrow-left"
        >
            Back
        </x-filament::button>
    </div>


    {{-- TITLE --}}
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
            Manage Permissions
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Company: <span class="font-semibold">{{ $company->company_name }}</span>
        </p>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">

        {{-- LEFT SIDE: USER LIST --}}
        <div class="space-y-3">
            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">Select User</h3>

            <div class="space-y-2">
                @foreach ($users as $user)
                    <button
                        wire:click="selectUser({{ $user->id }})"
                        class="w-full flex justify-between items-center p-3 rounded-lg transition border
                            {{ $selectedUser && $selectedUser->id == $user->id
                                ? 'bg-blue-100 dark:bg-blue-600 border-blue-500'
                                : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                    >
                        <div>
                            <p class="font-medium text-sm text-gray-900 dark:text-gray-100">{{ $user->name }}</p>
                            <p class="text-xs text-gray-600 dark:text-gray-300">{{ ucfirst($user->role) }}</p>
                        </div>

                        <x-heroicon-s-user class="w-5 h-5"/>
                    </button>
                @endforeach
            </div>
        </div>


        {{-- RIGHT SIDE: PERMISSIONS --}}
        <div class="lg:col-span-2">

            @if($selectedUser)

                {{-- Granted Permissions --}}
                @if(collect($permissions)->filter()->isNotEmpty())
                    <div class="mb-4 px-4 py-3 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-300 dark:border-green-700">
                        <h4 class="font-medium text-green-700 dark:text-green-300 mb-2">
                            Already Granted
                        </h4>

                        <div class="flex flex-wrap gap-2 text-xs">
                            @foreach ($permissions as $key => $value)
                                @if($value)
                                    <span class="px-2 py-1 rounded font-medium
                                        bg-green-200 text-green-800
                                        dark:bg-green-600 dark:text-white">
                                        {{ ucwords(str_replace('_', ' ', $key)) }}
                                    </span>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif


                {{-- Permissions toggles --}}
                @php
                    $items = [
                        'show_total_users'    => 'Show Total Users',
                        'show_total_managers' => 'Show Total Managers',
                        'show_total_admins'   => 'Show Total Admins',
                        'show_total_limit'    => 'Show Total Limit',
                        'show_total_storage'  => 'Show Total Storage',
                        'show_total_photos'   => 'Show Total Photos',
                    ];
                @endphp
                <div class="grid sm:grid-cols-2 gap-4 bg-white dark:bg-gray-800 p-5 rounded-xl shadow border border-gray-200 dark:border-gray-700">

                    @foreach ($items as $key => $label)
                        <label class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-900 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ $label }}
                            </span>

                            {{-- Toggle switch --}}
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox"
                                    wire:model="permissions.{{ $key }}"
                                    class="sr-only peer">

                                {{-- Track --}}
                                <div class="w-11 h-6 rounded-full transition-all
                                    bg-gray-300 dark:bg-gray-600
                                    peer-checked:bg-blue-600 peer-checked:dark:bg-blue-500"></div>

                                {{-- Knob --}}
                                <div class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full transition-all shadow
                                    peer-checked:translate-x-5 peer-checked:bg-white border"></div>
                            </label>
                        </label>
                    @endforeach

                </div>

                {{-- UPDATE BUTTON --}}
                <div class="mt-5">
                    <x-filament::button
                        wire:click="savePermissions"
                        color="success"
                        icon="heroicon-o-check-circle"
                        class="w-full sm:w-auto"
                    >
                        Save Changes
                    </x-filament::button>
                </div>

            @else
                <p class="text-gray-500 dark:text-gray-400 text-sm">
                    Select a user from the left to view permissions.
                </p>
            @endif

        </div>

    </div>

</x-filament::page>
