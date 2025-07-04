<x-filament::page>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="p-6 bg-white shadow rounded-xl">
            <h2 class="text-xl font-bold mb-2">Total Managers</h2>
            <p class="text-4xl text-blue-600">{{ $totals['managers'] }}</p>
        </div>
        <div class="p-6 bg-white shadow rounded-xl">
            <h2 class="text-xl font-bold mb-2">Total Users</h2>
            <p class="text-4xl text-green-600">{{ $totals['users'] }}</p>
        </div>
    </div>
</x-filament::page>
