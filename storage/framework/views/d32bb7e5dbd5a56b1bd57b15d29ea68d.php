<?php if (isset($component)) { $__componentOriginalbe23554f7bded3778895289146189db7 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalbe23554f7bded3778895289146189db7 = $attributes; } ?>
<?php $component = Filament\View\LegacyComponents\Page::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::page'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Filament\View\LegacyComponents\Page::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>

    <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-gray-100">
        Users in <?php echo e($company->company_name); ?>

    </h2>

    
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 mb-8">
        <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <button wire:click="selectUser(<?php echo e($user->id); ?>)"
                class="p-4 w-full text-left rounded-lg border transition
                       text-gray-900 dark:text-gray-100
                       border-gray-300 dark:border-gray-700
                       <?php echo e($selectedUser && $selectedUser->id == $user->id
                            ? 'bg-green-100 dark:bg-green-800 border-green-500'
                            : 'bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700'); ?>">
                
                <strong><?php echo e($user->name); ?></strong>
                <span class="text-sm text-gray-500 dark:text-white">
                    (<?php echo e(ucfirst($user->role)); ?>)
                </span>
            </button>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
    </div>

    <!--[if BLOCK]><![endif]--><?php if($selectedUser): ?>

        
        <div class="mt-8">
            <h3 class="text-2xl font-bold mb-4 text-gray-900 dark:text-gray-100">
                Permissions for <?php echo e($selectedUser->name); ?>

            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                
                <?php
                    $items = [
                        'show_total_users' => 'Show Total Users',
                        'show_total_managers' => 'Show Total Managers',
                        'show_total_admins' => 'Show Total Admins',
                        'show_total_limit' => 'Show Total Limit',
                        'show_total_storage' => 'Show Total Storage',
                        'show_total_photos' => 'Show Total Photos',
                    ];
                ?>

                <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <label class="flex items-center p-4 rounded-lg shadow transition cursor-pointer
                                  bg-white dark:bg-gray-800
                                  hover:shadow-lg
                                  text-gray-900 dark:text-gray-100">
                        <input type="checkbox"
                               wire:model.defer="permissions.<?php echo e($key); ?>"
                               class="form-checkbox h-5 w-5 text-blue-600 mr-3
                                      bg-white dark:bg-gray-700
                                      border-gray-300 dark:border-gray-600">
                        <span class="text-base"><?php echo e($label); ?></span>
                    </label>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->

            </div>

            
            <?php if (isset($component)) { $__componentOriginal6330f08526bbb3ce2a0da37da512a11f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6330f08526bbb3ce2a0da37da512a11f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.button.index','data' => ['wire:click' => 'savePermissions','class' => 'mt-6 px-6 py-3 font-semibold rounded-lg transition
                       bg-blue-600 text-white hover:bg-blue-700
                       dark:bg-blue-700 dark:hover:bg-blue-800']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:click' => 'savePermissions','class' => 'mt-6 px-6 py-3 font-semibold rounded-lg transition
                       bg-blue-600 text-white hover:bg-blue-700
                       dark:bg-blue-700 dark:hover:bg-blue-800']); ?>
                Update Permissions
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6330f08526bbb3ce2a0da37da512a11f)): ?>
<?php $attributes = $__attributesOriginal6330f08526bbb3ce2a0da37da512a11f; ?>
<?php unset($__attributesOriginal6330f08526bbb3ce2a0da37da512a11f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6330f08526bbb3ce2a0da37da512a11f)): ?>
<?php $component = $__componentOriginal6330f08526bbb3ce2a0da37da512a11f; ?>
<?php unset($__componentOriginal6330f08526bbb3ce2a0da37da512a11f); ?>
<?php endif; ?>
        </div>
    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalbe23554f7bded3778895289146189db7)): ?>
<?php $attributes = $__attributesOriginalbe23554f7bded3778895289146189db7; ?>
<?php unset($__attributesOriginalbe23554f7bded3778895289146189db7); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalbe23554f7bded3778895289146189db7)): ?>
<?php $component = $__componentOriginalbe23554f7bded3778895289146189db7; ?>
<?php unset($__componentOriginalbe23554f7bded3778895289146189db7); ?>
<?php endif; ?>
<?php /**PATH C:\xampp\htdocs\ScanVault_backend-main\resources\views/filament/admin/pages/user-permissions.blade.php ENDPATH**/ ?>