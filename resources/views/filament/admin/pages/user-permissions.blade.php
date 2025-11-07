<x-filament::page>
    <h2 class="text-2xl font-bold mb-6">Users in {{ $company->company_name }}</h2>

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 mb-8">
        @foreach ($users as $user)
            <button wire:click="selectUser({{ $user->id }})"
                class="p-4 w-full text-left rounded-lg border
                    {{ $selectedUser && $selectedUser->id == $user->id ? 'bg-green-100 border-green-500' : 'hover:bg-gray-100' }}">
                <strong>{{ $user->name }}</strong>
                <span class="text-sm text-gray-500">({{ ucfirst($user->role) }})</span>
            </button>
        @endforeach
    </div>

    @if($selectedUser)
        <div class="mt-8">
            <h3 class="text-2xl font-bold mb-4">Permissions for {{ $selectedUser->name }}</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <label class="flex items-center p-4 bg-white rounded-lg shadow hover:shadow-lg transition-all cursor-pointer">
                    <input type="checkbox" wire:model.defer="permissions.show_total_users" class="form-checkbox h-5 w-5 text-blue-600 mr-3">
                    <span class="text-base">Show Total Users</span>
                </label>
                <label class="flex items-center p-4 bg-white rounded-lg shadow hover:shadow-lg transition-all cursor-pointer">
                    <input type="checkbox" wire:model.defer="permissions.show_total_managers" class="form-checkbox h-5 w-5 text-blue-600 mr-3">
                    <span class="text-base">Show Total Managers</span>
                </label>
                <label class="flex items-center p-4 bg-white rounded-lg shadow hover:shadow-lg transition-all cursor-pointer">
                    <input type="checkbox" wire:model.defer="permissions.show_total_admins" class="form-checkbox h-5 w-5 text-blue-600 mr-3">
                    <span class="text-base">Show Total Admins</span>
                </label>
                <label class="flex items-center p-4 bg-white rounded-lg shadow hover:shadow-lg transition-all cursor-pointer">
                    <input type="checkbox" wire:model.defer="permissions.show_total_limit"
                        class="form-checkbox h-5 w-5 text-blue-600 mr-3">
                    <span class="text-md">Show Total Limit</span>
                </label>
                <label class="flex items-center p-4 bg-white rounded-lg shadow hover:shadow-lg transition-all cursor-pointer">
                    <input type="checkbox" wire:model.defer="permissions.show_total_storage"
                        class="form-checkbox h-5 w-5 text-blue-600 mr-3">
                    <span class="text-md">Show Total Storage</span>
                </label>
                <label class="flex items-center p-4 bg-white rounded-lg shadow hover:shadow-lg transition-all cursor-pointer">
                    <input type="checkbox" wire:model.defer="permissions.show_total_photos"
                        class="form-checkbox h-5 w-5 text-blue-600 mr-3">
                    <span class="text-md">Show Total Photos</span>
                </label>
            </div>

            <x-filament::button wire:click="savePermissions"
                class="mt-6 px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition">
                Update Permissions
            </x-filament::button>
        </div>
    @endif
</x-filament::page>
