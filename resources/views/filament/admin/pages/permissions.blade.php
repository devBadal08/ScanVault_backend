<x-filament::page>

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">

        @foreach ($companies as $company)
            <a href="{{ route('filament.admin.pages.user-permissions') }}?company={{ $company->id }}"
                class="group cursor-pointer p-6 rounded-xl border
                       bg-white dark:bg-gray-800
                       border-gray-200 dark:border-gray-700
                       text-gray-900 dark:text-gray-100
                       shadow-sm hover:shadow-lg
                       transition-all duration-300 transform hover:-translate-y-1">

                {{-- Icon --}}
                <div class="flex justify-center mb-4">
                    <div class="p-3 rounded-full 
                                bg-blue-100 dark:bg-blue-900/40
                                text-blue-600 dark:text-blue-300
                                transition duration-300 group-hover:scale-110">
                        <x-heroicon-o-building-office class="w-8 h-8" />
                    </div>
                </div>

                {{-- Company Name --}}
                <h2 class="text-xl font-semibold text-center group-hover:text-blue-600 dark:group-hover:text-blue-400 transition">
                    {{ $company->company_name }}
                </h2>

            </a>
        @endforeach

    </div>

</x-filament::page>
