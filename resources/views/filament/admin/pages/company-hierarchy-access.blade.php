<x-filament::page>

    {{-- SHOW MAIN COMPANIES AS CARDS --}}
    @if(!$selectedCompany)

        <div class="grid gap-6 grid-cols-1 md:grid-cols-2 lg:grid-cols-3">

            @foreach ($this->getAllCompanies() as $company)

                {{-- MAIN COMPANY (clickable) --}}
                @if($company->parent_id === null)
                    <div
                        wire:click="selectCompany({{ $company->id }})"
                        class="p-6 rounded-xl border cursor-pointer transition
                               bg-white border-gray-200 shadow-sm hover:shadow-md
                               dark:bg-gray-800 dark:border-gray-700 dark:hover:shadow-lg"
                    >
                        <div class="flex flex-col items-center">
                            <x-heroicon-o-building-office
                                class="w-10 h-10 mb-3 text-gray-700 dark:text-gray-300" />
                            <span class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                                {{ $company->company_name }}
                            </span>
                        </div>
                    </div>

                {{-- SUB-COMPANY (NOT clickable) --}}
                @else
                    <div
                        class="p-6 rounded-xl border opacity-60 cursor-not-allowed
                               bg-gray-200 border-gray-300
                               dark:bg-gray-700 dark:border-gray-600"
                    >
                        <div class="flex flex-col items-center">
                            <x-heroicon-o-building-office
                                class="w-10 h-10 mb-3 text-gray-500 dark:text-gray-400" />
                            <span class="text-xl font-semibold text-gray-600 dark:text-gray-300">
                                {{ $company->company_name }}
                            </span>
                            <span class="text-xs mt-1 text-gray-700 dark:text-gray-400">
                                (Sub Company)
                            </span>
                        </div>
                    </div>
                @endif

            @endforeach
        </div>

    @else

        {{-- BACK BUTTON --}}
        <div class="mb-4">
            <x-filament::button wire:click="goBack" color="primary">
                Back
            </x-filament::button>
        </div>

        {{-- HEADING --}}
        <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-gray-100">
            Select Sub Companies for {{ $selectedCompany->company_name }}
        </h2>

        {{-- SUB COMPANIES --}}
        <div class="space-y-3">
            @foreach ($this->getSubCompanies() as $sub)
                <label class="flex items-center gap-3 text-gray-800 dark:text-gray-200">
                    <input
                        type="checkbox"
                        wire:model="selectedSubCompanies"
                        value="{{ $sub->id }}"
                        class="rounded border-gray-300 dark:border-gray-600
                               text-primary-600 focus:ring-primary-500"
                    >
                    <span>{{ $sub->company_name }}</span>
                </label>
            @endforeach
        </div>

        <div class="mt-6 flex gap-3">
            <x-filament::button wire:click="saveAccess" color="primary">
                Submit
            </x-filament::button>
        </div>
    @endif

    {{-- ASSIGNED SUB COMPANIES --}}
    @if($this->getAssignedSubCompanies()->isNotEmpty())
        <div class="mt-8">
            <h3 class="text-lg font-semibold mb-2 text-gray-900 dark:text-gray-100">
                Already Assigned Sub Companies:
            </h3>

            <ul class="list-disc ml-6 space-y-1">
                @foreach ($this->getAssignedSubCompanies() as $assigned)
                    <li class="text-gray-700 dark:text-white">
                        {{ $assigned->company_name }}
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

</x-filament::page>
