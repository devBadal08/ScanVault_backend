<x-filament::page>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @foreach ($companies as $company)
            <a href="{{ route('filament.admin.resources.photo-delete-histories.company', $company->id) }}">
                <div class="p-6 bg-white rounded-xl shadow hover:shadow-lg transition cursor-pointer">
                    <h2 class="text-lg font-bold">
                        {{ $company->company_name }}
                    </h2>

                    <p class="text-sm text-gray-500 mt-2">
                        View deleted photos
                    </p>
                </div>
            </a>
        @endforeach
    </div>
</x-filament::page>