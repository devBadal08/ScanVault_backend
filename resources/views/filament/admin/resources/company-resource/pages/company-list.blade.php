<x-filament-panels::page>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
        @foreach($companies as $company)
            <a href="{{ \App\Filament\Admin\Resources\CompanyResource::getUrl('dashboard', ['record' => $company->id]) }}" class="block">
                <x-filament::card class="cursor-pointer hover:bg-gray-50 transition duration-200 ease-in-out">
                    <h2 class="text-xl font-bold mb-2 text-gray-900">{{ $company->company_name }}</h2>
                    <!-- Optionally add company description or stats here -->
                </x-filament::card>
            </a>
        @endforeach
    </div>
</x-filament-panels::page>