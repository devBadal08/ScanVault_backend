<?php if (isset($component)) { $__componentOriginal166a02a7c5ef5a9331faf66fa665c256 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal166a02a7c5ef5a9331faf66fa665c256 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament-panels::components.page.index','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament-panels::page'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>

    <!--[if BLOCK]><![endif]--><?php if(!$passwordVerified): ?>

        
        
        

        <div class="max-w-md mx-auto mt-10">

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">

                <div class="text-center mb-6">

                    <div class="flex justify-center mb-4">

                        <div class="p-3 rounded-full bg-danger-100 dark:bg-danger-900">

                            <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-lock-closed'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-8 h-8 text-danger-600 dark:text-danger-400']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $attributes = $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $component = $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>

                        </div>

                    </div>

                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                        Protected Delete Page
                    </h2>

                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Enter the delete password to continue.
                    </p>

                </div>

                <form wire:submit="verifyPassword">

                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Password
                    </label>

                    <div style="position: relative; width: 100%;">

                        <input
                            id="delete-password"
                            type="password"
                            wire:model="deletePassword"
                            autocomplete="off"
                            class="w-full rounded-lg border-gray-300
                                dark:border-gray-600
                                dark:bg-gray-700
                                dark:text-white
                                pr-12"
                            placeholder="Enter delete password"
                            autofocus
                        >

                        
                        <button
                            type="button"
                            onclick="toggleDeletePassword()"
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
                                id="delete-password-eye"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.8"
                                stroke="currentColor"
                                style="width: 20px; height: 20px;"
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


                    <!--[if BLOCK]><![endif]--><?php if($passwordError): ?>

                        <div class="mt-3 text-sm text-danger-600">
                            <?php echo e($passwordError); ?>

                        </div>

                    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->


                    

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
                            Unlock Delete
                        </span>

                    </button>

                </form>

            </div>

        </div>

    <?php else: ?>

        
        
        

        <div class="max-w-3xl mx-auto space-y-6">

            <div>

                <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                    Delete Photos by Date
                </h2>

                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Select the date range of photos you want to permanently delete.
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
                            wire:model="startDate"
                            class="w-full rounded-lg border-gray-300
                                   dark:border-gray-600
                                   dark:bg-gray-700
                                   dark:text-white"
                        >

                    </div>


                    

                    <div>

                        <label class="block text-sm font-medium mb-2">
                            End Date
                        </label>

                        <input
                            type="date"
                            wire:model="endDate"
                            class="w-full rounded-lg border-gray-300
                                   dark:border-gray-600
                                   dark:bg-gray-700
                                   dark:text-white"
                        >

                    </div>

                </div>


                

                <div class="mt-6 flex justify-end gap-3">

                    

                    <button
                        type="button"
                        wire:click="$set('startDate', null); $set('endDate', null)"
                        class="px-5 py-2.5 rounded-lg
                               bg-gray-200 hover:bg-gray-300
                               dark:bg-gray-700 dark:hover:bg-gray-600
                               text-gray-800 dark:text-white
                               font-medium"
                    >
                        Cancel
                    </button>


                    

                    <button
                        type="button"
                        wire:click="deletePermanently"
                        wire:loading.attr="disabled"
                        wire:confirm="Are you absolutely sure you want to permanently delete all photos within the selected date range? This action cannot be undone."
                        style="
                            background-color: #dc2626;
                            color: #ffffff;
                            border: none;
                        "
                        class="px-5 py-2.5 rounded-lg
                            font-medium
                            hover:opacity-90
                            disabled:opacity-50"
                    >
                        <span wire:loading.remove wire:target="deletePermanently">
                            Delete Permanently
                        </span>

                        <span wire:loading wire:target="deletePermanently">
                            Deleting...
                        </span>
                    </button>

                </div>

            </div>

        </div>

    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

    <script>
        function toggleDeletePassword() {

            const passwordInput =
                document.getElementById('delete-password');

            const eyeIcon =
                document.getElementById('delete-password-eye');

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

 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal166a02a7c5ef5a9331faf66fa665c256)): ?>
<?php $attributes = $__attributesOriginal166a02a7c5ef5a9331faf66fa665c256; ?>
<?php unset($__attributesOriginal166a02a7c5ef5a9331faf66fa665c256); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal166a02a7c5ef5a9331faf66fa665c256)): ?>
<?php $component = $__componentOriginal166a02a7c5ef5a9331faf66fa665c256; ?>
<?php unset($__componentOriginal166a02a7c5ef5a9331faf66fa665c256); ?>
<?php endif; ?><?php /**PATH D:\Vidhi\All Projects\ScanVault_backend-main\resources\views/filament/admin/pages/date-wise-photo-delete.blade.php ENDPATH**/ ?>