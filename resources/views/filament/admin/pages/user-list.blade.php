<x-filament::page>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        {{-- ================= Managers Card ================= --}}
        <div class="p-6 rounded-xl shadow-md border
                    bg-white dark:bg-gray-800
                    border-gray-200 dark:border-gray-700">

            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4">
                Managers ({{ $managers->count() }})
            </h2>

            <div class="space-y-3 max-h-[70vh] overflow-y-auto pr-2">

                @foreach ($managers as $m)
                    <div class="p-4 rounded-lg bg-gray-100 dark:bg-gray-700
                                text-gray-900 dark:text-gray-100">

                        <p class="font-semibold">{{ $m->name }}</p>
                        <p class="text-sm">Email: {{ $m->email }}</p>

                    </div>
                @endforeach

            </div>
        </div>


        {{-- ================= Users Card ================= --}}
        <div class="p-6 rounded-xl shadow-md border
                    bg-white dark:bg-gray-800
                    border-gray-200 dark:border-gray-700">

            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4">
                Users ({{ $users->count() }})
            </h2>

            <div class="space-y-3 max-h-[70vh] overflow-y-auto pr-2">

                @foreach ($users as $u)
                    <div class="p-4 rounded-lg bg-gray-100 dark:bg-gray-700
                                text-gray-900 dark:text-gray-100">

                        <p class="font-semibold">{{ $u->name }}</p>
                        <p class="text-sm">Email: {{ $u->email }}</p>

                    </div>
                @endforeach

            </div>
        </div>

    </div>

</x-filament::page>
