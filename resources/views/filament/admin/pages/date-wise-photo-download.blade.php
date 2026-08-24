<x-filament-panels::page>

    @if(!$passwordVerified)

        {{-- Password Protection --}}

        <div class="max-w-md mx-auto mt-10">

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">

                <div class="text-center mb-6">

                    <div class="flex justify-center mb-4">
                        <div class="p-3 rounded-full bg-primary-100 dark:bg-primary-900">
                            <x-heroicon-o-lock-closed
                                class="w-8 h-8 text-primary-600 dark:text-primary-400"
                            />
                        </div>
                    </div>

                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                        Protected Page
                    </h2>

                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Enter your password to access Backup Photos.
                    </p>

                </div>

                <form wire:submit="verifyPassword">

                    <div>

                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Password
                        </label>

                        <div class="relative">

                            <input
                                id="backup-password"
                                type="password"
                                wire:model="backupPassword"
                                autocomplete="current-password"
                                class="w-full rounded-lg border-gray-300
                                    dark:border-gray-600
                                    dark:bg-gray-700
                                    dark:text-white
                                    pr-12"
                                placeholder="Enter your password"
                                autofocus
                            >

                            {{-- Eye Button --}}
                            <button
                                type="button"
                                onclick="toggleBackupPassword()"
                                tabindex="-1"
                                style="
                                    position: absolute;
                                    right: 12px;
                                    top: 50%;
                                    transform: translateY(-50%);
                                    width: 32px;
                                    height: 32px;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    z-index: 10;
                                    background: transparent;
                                    border: none;
                                    cursor: pointer;
                                "
                            >

                                <svg
                                    id="backup-password-eye"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="1.8"
                                    stroke="currentColor"
                                    class="w-5 h-5"
                                >

                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M2.036 12.322a1.012 1.012 0 010-.644
                                        C3.423 7.51 7.36 5 12 5c4.64 0
                                        8.577 2.51 9.964 6.678a1.012
                                        1.012 0 010 .644C20.577
                                        16.49 16.64 19 12 19c-4.64
                                        0-8.577-2.51-9.964-6.678z"
                                    />

                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                    />

                                </svg>

                            </button>

                        </div>

                    </div>


                    @if($passwordError)

                        <div class="mt-3 text-sm text-danger-600">
                            {{ $passwordError }}
                        </div>

                    @endif


                    {{-- Unlock Button --}}

                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        style="margin-top: 16px;"
                        class="w-full px-4 py-2.5
                            bg-primary-600 hover:bg-primary-700
                            text-white rounded-lg font-medium
                            disabled:opacity-50"
                    >

                        <span wire:loading.remove wire:target="verifyPassword">
                            Unlock Backup
                        </span>

                    </button>

                </form>

            </div>

        </div>

    @else

        {{-- ========================================================= --}}
        {{-- YOUR EXISTING BACKUP PAGE --}}
        {{-- ========================================================= --}}

        <div class="space-y-6">

            <div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                    Backup All Photos
                </h2>

                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Select a start date and end date to download photos.
                </p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <div>

                        <label class="block text-sm font-medium mb-2">
                            Start Date
                        </label>

                        <input
                            type="date"
                            id="start_date"
                            class="w-full rounded-lg border-gray-300
                                   dark:border-gray-600
                                   dark:bg-gray-700"
                        >

                    </div>

                    <div>

                        <label class="block text-sm font-medium mb-2">
                            End Date
                        </label>

                        <input
                            type="date"
                            id="end_date"
                            class="w-full rounded-lg border-gray-300
                                   dark:border-gray-600
                                   dark:bg-gray-700"
                        >

                    </div>

                </div>

                <div class="mt-6">

                    <button
                        type="button"
                        onclick="startDateWiseDownload()"
                        class="px-5 py-3 rounded-lg
                               bg-primary-600 hover:bg-primary-700
                               text-white font-medium"
                    >
                        Download Photos
                    </button>

                </div>

            </div>

        </div>

    @endif

    <script>
        function startDateWiseDownload() {

            const startDate = document.getElementById('start_date')?.value;
            const endDate = document.getElementById('end_date')?.value;

            if (!startDate) {
                alert('Please select start date.');
                return;
            }

            if (!endDate) {
                alert('Please select end date.');
                return;
            }

            if (startDate > endDate) {
                alert('End date cannot be before start date.');
                return;
            }

            const url =
                "{{ route('manager.date-wise-photo-download') }}" +
                "?start_date=" + encodeURIComponent(startDate) +
                "&end_date=" + encodeURIComponent(endDate);

            console.log('Starting download:', url);

            window.location.href = url;
        }

        function toggleBackupPassword() {

            const passwordInput = document.getElementById('backup-password');
            const eyeIcon = document.getElementById('backup-password-eye');

            if (passwordInput.type === 'password') {

                passwordInput.type = 'text';

                eyeIcon.innerHTML = `
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M3.98 8.223A10.477 10.477 0 001.934
                        12C3.226 16.338 7.244 19 12
                        19c.852 0 1.68-.107 2.467-.31M6.228
                        6.228A10.451 10.451 0 0112 5c4.756
                        0 8.773 2.662 10.065 7a10.523
                        10.523 0 01-4.293 5.157M6.228
                        6.228L3 3m3.228 3.228l3.65
                        3.65m5.894 5.894L21 21m-3.228
                        -3.228l-3.65-3.65m0 0a3 3 0
                        104.243-4.243m-4.243 4.243L9.88
                        9.88"
                    />
                `;

            } else {

                passwordInput.type = 'password';

                eyeIcon.innerHTML = `
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M2.036 12.322a1.012 1.012
                        0 010-.644C3.423 7.51 7.36
                        5 12 5c4.64 0 8.577 2.51
                        9.964 6.678a1.012 1.012
                        0 010 .644C20.577 16.49
                        16.64 19 12 19c-4.64
                        0-8.577-2.51-9.964-6.678z"
                    />

                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M15 12a3 3 0 11-6 0 3 3
                        0 016 0z"
                    />
                `;
            }
        }
    </script>

</x-filament-panels::page>