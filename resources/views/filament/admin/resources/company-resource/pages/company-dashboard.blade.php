<x-filament-panels::page>
    @if($showFormPage)
        <div class="relative mb-6">
            <x-filament::button 
                wire:click="back" 
                class="absolute left-0 bg-blue-600 hover:bg-blue-700 text-white inline-flex items-center px-4 py-2 rounded transition">
                ← Back
            </x-filament::button>

            <h2 class="text-xl font-bold text-center">
                {{ $editingUserId ? 'Edit User' : 'Create New User' }}
            </h2>
        </div>

        <div class="bg-white shadow rounded-lg p-6">
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
    <div class="relative mb-6">
        <x-filament::button 
            wire:click="goBack" 
            class="absolute left-0 bg-blue-600 hover:bg-blue-700 text-white inline-flex items-center px-4 py-2 rounded transition">
            ← Back
        </x-filament::button>

        <h1 class="text-2xl font-bold text-center">{{ $company->company_name }} Dashboard</h1>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-6">
        <div class="bg-white shadow rounded-lg p-6 text-center">
            <div class="text-3xl font-bold">{{ $totalAdmins }}</div>
            <div class="text-gray-600 mt-2">Total Admins</div>
        </div>
        <div class="bg-white shadow rounded-lg p-6 text-center">
            <div class="text-3xl font-bold">{{ $totalManagers }}</div>
            <div class="text-gray-600 mt-2">Total Managers</div>
        </div>
        <div class="bg-white shadow rounded-lg p-6 text-center">
            <div class="text-3xl font-bold">{{ $totalUsers }}</div>
            <div class="text-gray-600 mt-2">Total Users</div>
        </div>
        <div class="bg-white shadow rounded-lg p-6 text-center">
            <div class="text-xl font-bold text-green-600">{{ $totalStorageUsed }}</div>
            <div class="text-gray-600 mt-2">Total Storage Used</div>
        </div>
        <div class="bg-white shadow rounded-lg p-6 text-center">
            <div class="text-3xl font-bold">{{ $totalPhotos }}</div>
            <div class="text-gray-600 mt-2">Total Photos</div>
        </div>
    </div>

    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">Users</h2>
        <x-filament::button wire:click="createNewUserPage" color="primary">
            + New User
        </x-filament::button>
    </div>

    <table class="min-w-full bg-white rounded-lg shadow overflow-hidden mb-6">
        <thead>
            <tr class="bg-gray-100 text-gray-600 uppercase text-sm leading-normal">
                <th class="py-3 px-6 text-left">Name</th>
                <th class="py-3 px-6 text-left">Email</th>
                <th class="py-3 px-6 text-left">Role</th>
                <th class="py-3 px-6 text-left">Actions</th>
            </tr>
        </thead>
        <tbody class="text-gray-600 text-sm">
            @foreach ($users as $user)
                <tr class="border-b hover:bg-gray-50">
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
