<x-filament::page>

    {{-- SHOW MAIN COMPANIES AS CARDS --}}
    @if(!$selectedCompany)

        <div class="grid gap-6 grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($this->getAllCompanies() as $company)

                {{-- MAIN COMPANY (clickable) --}}
                @if($company->parent_id === null)
                    <div 
                        wire:click="selectCompany({{ $company->id }})"
                        class="p-6 bg-white rounded-xl border shadow-sm hover:shadow-md cursor-pointer transition"
                    >
                        <div class="flex flex-col items-center">
                            <x-heroicon-o-building-office class="w-10 h-10 mb-3 text-gray-700" />
                            <span class="text-xl font-semibold">{{ $company->company_name }}</span>
                        </div>
                    </div>

                {{-- SUB-COMPANY (NOT clickable) --}}
                @else
                    <div 
                        class="p-6 bg-gray-200 rounded-xl border shadow-sm opacity-50 cursor-not-allowed"
                    >
                        <div class="flex flex-col items-center">
                            <x-heroicon-o-building-office class="w-10 h-10 mb-3 text-gray-500" />
                            <span class="text-xl font-semibold text-gray-600">{{ $company->company_name }}</span>
                            <span class="text-xs text-gray-700 mt-1">(Sub Company)</span>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

    @else

        {{-- BACK BUTTON ABOVE HEADING --}}
        <div class="mb-4">
            <x-filament::button wire:click="goBack" color="primary">
                Back
            </x-filament::button>
        </div>

        {{-- SHOW SUB-COMPANIES --}}
        <h2 class="text-2xl font-bold mb-4">
            Select Sub Companies for {{ $selectedCompany->company_name }}
        </h2>

        <div class="space-y-3">

            @foreach ($this->getSubCompanies() as $sub)
                <label class="flex items-center gap-3">
                    <input type="checkbox" wire:model="selectedSubCompanies" value="{{ $sub->id }}">
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

    @if($this->getAssignedSubCompanies()->isNotEmpty())
        <div class="mt-8">
            <h3 class="text-lg font-semibold mb-2">Already Assigned Sub Companies:</h3>

            <ul class="list-disc ml-6 space-y-1">
                @foreach ($this->getAssignedSubCompanies() as $assigned)
                    <li class="text-gray-700 text-md">{{ $assigned->company_name }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</x-filament::page>
