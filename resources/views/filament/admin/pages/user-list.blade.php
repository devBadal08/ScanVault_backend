<x-filament::page>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

        {{-- Total Managers Card --}}
        <div
            class="p-6 rounded-xl shadow-sm border
                   bg-white dark:bg-gray-800
                   border-gray-200 dark:border-gray-700
                   text-gray-900 dark:text-gray-100
            "
        >
            <h3 class="text-lg font-semibold">Total Managers</h3>
            <p class="text-3xl font-bold mt-2">{{ $totals['managers'] }}</p>
        </div>

        {{-- Total Users Card --}}
        <div
            class="p-6 rounded-xl shadow-sm border
                   bg-white dark:bg-gray-800
                   border-gray-200 dark:border-gray-700
                   text-gray-900 dark:text-gray-100
            "
        >
            <h3 class="text-lg font-semibold">Total Users</h3>
            <p class="text-3xl font-bold mt-2">{{ $totals['users'] }}</p>
        </div>

    </div>
</x-filament::page>
