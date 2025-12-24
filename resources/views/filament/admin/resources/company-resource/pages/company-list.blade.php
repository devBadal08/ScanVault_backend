<x-filament-panels::page>

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">

        @foreach ($companies as $company)

            <div class="relative">

                {{-- Card --}}
                <a href="{{ \App\Filament\Admin\Resources\CompanyResource::getUrl('dashboard', ['record' => $company->id]) }}"
                    class="block">

                    <x-filament::card 
                        class="relative w-full h-72 bg-white dark:bg-gray-800 overflow-hidden rounded-2xl flex flex-col"
                    >

                        {{-- FIXED SIZE LOGO BOX (square, same for all logos) --}}
                        <div class="w-full flex justify-center mt-10">
                            <div class="w-32 h-32 rounded-xl bg-gray-50 dark:bg-gray-900
                                        flex items-center justify-center overflow-hidden">

                                <img
                                    src="{{ asset('storage/' . $company->company_logo) }}"
                                    alt="{{ $company->company_name }} logo"
                                    class="w-full h-full object-contain scale-90"
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
