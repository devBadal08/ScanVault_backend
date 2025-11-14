<x-filament-panels::page>

    @if($showFormPage)
        {{-- BACK BUTTON + HEADING --}}
        <div class="relative mb-6">
            <x-filament::button 
                wire:click="back" 
                class="absolute left-0 bg-blue-600 hover:bg-blue-700 text-white 
                       dark:bg-blue-700 dark:hover:bg-blue-800
                       inline-flex items-center px-4 py-2 rounded transition">
                ← Back
            </x-filament::button>

            <h2 class="text-xl font-bold text-center 
                       text-gray-900 dark:text-gray-100">
                {{ $editingUserId ? 'Edit User' : 'Create New User' }}
            </h2>
        </div>

        {{-- FORM CONTAINER --}}
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

    {{-- BACK BUTTON + TITLE --}}
    <div class="relative mb-6">
        <x-filament::button 
            wire:click="goBack" 
            class="absolute left-0 bg-blue-600 hover:bg-blue-700 text-white 
                   dark:bg-blue-700 dark:hover:bg-blue-800
                   inline-flex items-center px-4 py-2 rounded transition">
            ← Back
        </x-filament::button>

        <h1 class="text-2xl font-bold text-center 
                   text-gray-900 dark:text-gray-100">
            {{ $company->company_name }} Dashboard
        </h1>
    </div>

    {{-- SUMMARY CARDS --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-6">
        <div class="rounded-lg p-6 text-center shadow 
                    bg-white dark:bg-gray-800
                    text-gray-900 dark:text-gray-100
                    border border-gray-200 dark:border-gray-700">
            <div class="text-3xl font-bold">{{ $totalAdmins }}</div>
            <div class="text-gray-600 dark:text-white mt-2">Total Admins</div>
        </div>

        <div class="rounded-lg p-6 text-center shadow 
                    bg-white dark:bg-gray-800
                    text-gray-900 dark:text-gray-100
                    border border-gray-200 dark:border-gray-700">
            <div class="text-3xl font-bold">{{ $totalManagers }}</div>
            <div class="text-gray-600 dark:text-white mt-2">Total Managers</div>
        </div>

        <div class="rounded-lg p-6 text-center shadow 
                    bg-white dark:bg-gray-800
                    text-gray-900 dark:text-gray-100
                    border border-gray-200 dark:border-gray-700">
            <div class="text-3xl font-bold">{{ $totalUsers }}</div>
            <div class="text-gray-600 dark:text-white mt-2">Total Users</div>
        </div>

        <div class="rounded-lg p-6 text-center shadow 
                    bg-white dark:bg-gray-800
                    text-gray-900 dark:text-gray-100
                    border border-gray-200 dark:border-gray-700">
            <div class="text-xl font-bold text-green-600 dark:text-green-400">
                {{ $totalStorageUsed }}
            </div>
            <div class="text-gray-600 dark:text-white mt-2">Total Storage Used</div>
        </div>

        <div class="rounded-lg p-6 text-center shadow 
                    bg-white dark:bg-gray-800
                    text-gray-900 dark:text-gray-100
                    border border-gray-200 dark:border-gray-700">
            <div class="text-3xl font-bold">{{ $totalPhotos }}</div>
            <div class="text-gray-600 dark:text-white mt-2">Total Photos</div>
        </div>
    </div>

    {{-- TITLE + ADD USER --}}
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Users</h2>

        <x-filament::button wire:click="createNewUserPage" color="primary">
            + New User
        </x-filament::button>
    </div>

    {{-- USER TABLE --}}
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
                        <x-filament::button 
                            color="primary" 
                            size="sm"
                            wire:click="editUserPage({{ $user->id }})">
                            Edit
                        </x-filament::button>
                    </td>
                </tr>
            @endforeach
        </tbody>

    </table>

    @endif

</x-filament-panels::page>
