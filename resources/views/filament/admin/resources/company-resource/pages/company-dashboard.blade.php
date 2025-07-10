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

    @if (session()->has('success'))
        <div class="p-4 mb-4 text-green-700 bg-green-100 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="p-4 mb-4 text-red-700 bg-red-100 rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white shadow rounded-lg p-6 mt-6">
        <form wire:submit.prevent="createUser">
            {{ $form }}
            <div class="mt-4">
                <x-filament::button type="submit">
                    Create User
                </x-filament::button>
            </div>
        </form>
    </div>
</x-filament-panels::page>
