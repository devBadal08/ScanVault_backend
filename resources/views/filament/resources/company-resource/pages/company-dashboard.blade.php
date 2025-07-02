<x-filament-panels::page>
    <h1 class="text-2xl font-bold mb-4">{{ $company->company_name }} Dashboard</h1>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-6">
        <div class="bg-white shadow rounded-lg p-6 text-center">
            <div class="text-3xl font-bold">{{ $totalAdmins }}</div>
            <div class="text-gray-600 mt-2">Admins</div>
        </div>
        <div class="bg-white shadow rounded-lg p-6 text-center">
            <div class="text-3xl font-bold">{{ $totalManagers }}</div>
            <div class="text-gray-600 mt-2">Managers</div>
        </div>
        <div class="bg-white shadow rounded-lg p-6 text-center">
            <div class="text-3xl font-bold">{{ $totalUsers }}</div>
            <div class="text-gray-600 mt-2">Users</div>
        </div>
    </div>

    @if (session()->has('success'))
        <div class="p-4 mb-4 text-green-700 bg-green-100 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white shadow rounded-lg p-6">
        <form wire:submit.prevent="createUser">
            {{ $form }}
            <x-filament::button type="submit" class="mt-4">
                Create User
            </x-filament::button>
        </form>
    </div>
</x-filament-panels::page>
