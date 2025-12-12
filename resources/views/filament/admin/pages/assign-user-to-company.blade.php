<x-filament::page>

    {{-- Select User --}}
    <div class="mb-6">
        <label class="font-semibold">Select User:</label>
        <select wire:model="selectedUser" wire:change="$refresh" class="border rounded p-2 w-1/3">
            <option value="">-- Choose User --</option>

            @foreach ($this->getUsers() as $user)
                <option value="{{ $user->id }}">
                    {{ $user->name }} ({{ $user->email }})
                </option>
            @endforeach
        </select>
    </div>

    @if($selectedUser)

        {{-- Companies List --}}
        <h3 class="text-xl font-semibold mb-3">Select Companies to Assign</h3>

        <div class="space-y-3">
            @foreach ($this->getCompanies() as $company)
                <label class="flex items-center gap-3">
                    <input type="checkbox" 
                        wire:model="selectedCompanies" 
                        value="{{ $company->id }}">
                    <span>{{ $company->company_name }}</span>
                </label>
            @endforeach
        </div>

        {{-- Save Button --}}
        <div class="mt-6">
            <x-filament::button wire:click="save" color="primary">
                Save Access
            </x-filament::button>
        </div>

        {{-- Already Assigned --}}
        @if($this->getAssignedCompanies()->isNotEmpty())
            <div class="mt-10">
                <h3 class="text-lg font-semibold mb-2">Already Assigned Access:</h3>

                <ul class="list-disc ml-6 space-y-2">
                    @foreach ($this->getAssignedCompanies() as $company)
                        <li class="flex justify-between items-center w-1/3">
                            <span>{{ $company->company_name }}</span>

                            <x-filament::button 
                                wire:click="removeAccess({{ $company->id }})"
                                color="danger"
                                size="xs"
                            >
                                Remove
                            </x-filament::button>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

    @endif

</x-filament::page>
