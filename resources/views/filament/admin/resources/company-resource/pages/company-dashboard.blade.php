<x-filament-panels::page>
    <h1 class="text-2xl font-bold mb-4">{{ $company->company_name }} Dashboard</h1>

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
    </div>

    {{-- CREATE USER BUTTON --}}
    <div class="mb-4">
        <x-filament::button tag="a" href="{{ route('filament.admin.resources.users.create') }}">
            + Create User
        </x-filament::button>
    </div>

    {{-- FORM --}}
    @if($showForm)
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <form wire:submit.prevent="createOrUpdateUser">
                {{ $form }}

                <div class="mt-4">
                    <x-filament::button type="submit">
                        {{ $editingUserId ? 'Update User' : 'Create User' }}
                    </x-filament::button>
                    <x-filament::button color="secondary" wire:click="$set('showForm', false)">
                        Cancel
                    </x-filament::button>
                </div>
            </form>
        </div>
    @endif

    {{-- USERS TABLE --}}
    <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-xl font-bold mb-4">Users</h2>
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b">
                    <th class="py-2 px-4 text-left">Name</th>
                    <th class="py-2 px-4 text-left">Email</th>
                    <th class="py-2 px-4 text-left">Role</th>
                    <th class="py-2 px-4 text-left">Max Limit</th>
                    <th class="py-2 px-4 text-left">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($company->users as $user)
                    <tr class="border-b">
                        <td class="py-2 px-4">{{ $user->name }}</td>
                        <td class="py-2 px-4">{{ $user->email }}</td>
                        <td class="py-2 px-4">{{ ucfirst($user->role) }}</td>
                        <td class="py-2 px-4">{{ $user->max_limit }}</td>
                        <td class="py-2 px-4">
                            <x-filament::button size="sm" color="info" wire:click="editUser({{ $user->id }})">
                                Edit
                            </x-filament::button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-filament-panels::page>
