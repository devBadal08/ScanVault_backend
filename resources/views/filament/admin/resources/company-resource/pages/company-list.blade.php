<x-filament-panels::page>

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">

        @foreach ($companies as $company)

            <div class="relative">

                {{-- Delete Button --}}
                <button 
                    wire:click="deleteCompany({{ $company->id }})"
                    onclick="return confirm('Are you sure you want to delete this company?')"
                    class="absolute right-3 top-3 z-20 bg-red-100 text-red-600 
                           p-2 rounded-full hover:bg-red-200 dark:bg-red-900/30 dark:hover:bg-red-900/50"
                >
                    <x-heroicon-o-trash class="w-5 h-5"/>
                </button>

                {{-- Card --}}
                <a href="{{ \App\Filament\Admin\Resources\CompanyResource::getUrl('dashboard', ['record' => $company->id]) }}"
                    class="block">

                    <x-filament::card 
    class="relative w-full h-72 bg-white dark:bg-gray-800 overflow-hidden rounded-2xl flex flex-col"
>

    {{-- FIXED SIZE LOGO BOX (square, same for all logos) --}}
    <div class="w-full flex justify-center mt-10">
        <div class="w-32 h-32 flex items-center justify-center">
            <img 
                src="{{ asset('storage/' . $company->company_logo) }}" 
                class="max-w-full max-h-full object-contain"
            />
        </div>
    </div>

    {{-- Divider --}}
    <div class="w-10/12 mx-auto h-px bg-gray-300 dark:bg-gray-700 mt-4"></div>

    {{-- Company Name --}}
    <div class="flex-1 flex items-center justify-center">
        <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100 text-center">
            {{ $company->company_name }}
        </h2>
    </div>

</x-filament::card>
                </a>

            </div>

        @endforeach

    </div>

</x-filament-panels::page>
