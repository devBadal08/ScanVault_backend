<x-filament-panels::page>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
        @foreach($companies as $company)
            <a href="{{ \App\Filament\Admin\Resources\CompanyResource::getUrl('dashboard', ['record' => $company->id]) }}">
                <div class="bg-white shadow rounded-lg p-6 hover:bg-gray-100 transition">
                    <h2 class="text-xl font-bold mb-2">{{ $company->company_name }}</h2>
                </div>
            </a>
        @endforeach
    </div>
</x-filament-panels::page>
