<x-filament-panels::page>

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">

        @foreach($companies as $company)

            <div class="relative group transform transition-all duration-300 hover:-translate-y-1">

                {{-- Delete Button (Right Center + Dark Mode) --}}
                <button 
                    wire:click="deleteCompany({{ $company->id }})"
                    onclick="return confirm('Are you sure you want to delete this company?')"
                    class="absolute right-3 top-1/2 -translate-y-1/2
                           bg-red-100 text-red-600 hover:bg-red-200
                           dark:bg-red-900/30 dark:text-red-400 dark:hover:bg-red-900/50
                           p-2 rounded-full shadow-sm transition"
                    title="Delete Company"
                >
                    <x-heroicon-o-trash class="w-5 h-5"/>
                </button>

                {{-- Company Card --}}
                <a href="{{ \App\Filament\Admin\Resources\CompanyResource::getUrl('dashboard', ['record' => $company->id]) }}"
                   class="block"
                >
                    <div 
                        class="rounded-2xl shadow-sm 
                               bg-white p-6 border border-gray-100
                               hover:shadow-md transition duration-300

                               dark:bg-gray-800 dark:border-gray-700 dark:hover:shadow-lg"
                    >
                        <div class="flex items-center justify-center">
                            <x-heroicon-o-building-office 
                                class="w-10 h-10 text-primary-600 dark:text-orange-400"
                            />
                        </div>

                        <h2 class="text-xl font-bold text-center mt-4 
                                   text-gray-900 dark:text-gray-100">
                            {{ $company->company_name }}
                        </h2>
                    </div>
                </a>

            </div>

        @endforeach

    </div>

</x-filament-panels::page>
