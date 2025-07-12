<x-filament::page>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        @foreach ($companies as $company)
            <a href="{{ route('filament.admin.pages.user-permissions') }}?company={{ $company->id }}"
                class="cursor-pointer p-6 bg-white rounded-xl shadow hover:shadow-xl transform hover:-translate-y-1 transition-all duration-300 text-center">
                <h2 class="text-xl font-semibold">{{ $company->company_name }}</h2>
            </a>
        @endforeach
    </div>
</x-filament::page>
