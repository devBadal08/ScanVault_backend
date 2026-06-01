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

    <!--[if BLOCK]><![endif]--><?php if($showFormPage): ?>
        
        <div class="relative mb-6">
            <?php if (isset($component)) { $__componentOriginal6330f08526bbb3ce2a0da37da512a11f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6330f08526bbb3ce2a0da37da512a11f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.button.index','data' => ['wire:click' => 'back','class' => 'absolute left-0 bg-blue-600 hover:bg-blue-700 text-white 
                       dark:bg-blue-700 dark:hover:bg-blue-800
                       inline-flex items-center px-4 py-2 rounded transition']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:click' => 'back','class' => 'absolute left-0 bg-blue-600 hover:bg-blue-700 text-white 
                       dark:bg-blue-700 dark:hover:bg-blue-800
                       inline-flex items-center px-4 py-2 rounded transition']); ?>
                ← Back
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

            <h2 class="text-xl font-bold text-center 
                       text-gray-900 dark:text-gray-100">
                <?php echo e($editingUserId ? 'Edit User' : 'Create New User'); ?>

            </h2>
        </div>

        
        <div class="shadow rounded-lg p-6
                    bg-white dark:bg-gray-800
                    text-gray-900 dark:text-gray-100">
            <form wire:submit.prevent="saveUser">
                <?php echo e($form); ?>

                <div class="mt-4">
                    <?php if (isset($component)) { $__componentOriginal6330f08526bbb3ce2a0da37da512a11f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6330f08526bbb3ce2a0da37da512a11f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.button.index','data' => ['type' => 'submit']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit']); ?>
                        <?php echo e($editingUserId ? 'Update User' : 'Create User'); ?>

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
            </form>
        </div>

    <?php else: ?>

    
    <div class="relative mb-6">
        <?php if (isset($component)) { $__componentOriginal6330f08526bbb3ce2a0da37da512a11f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6330f08526bbb3ce2a0da37da512a11f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.button.index','data' => ['wire:click' => 'goBack','class' => 'absolute left-0 bg-blue-600 hover:bg-blue-700 text-white 
                   dark:bg-blue-700 dark:hover:bg-blue-800
                   inline-flex items-center px-4 py-2 rounded transition']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:click' => 'goBack','class' => 'absolute left-0 bg-blue-600 hover:bg-blue-700 text-white 
                   dark:bg-blue-700 dark:hover:bg-blue-800
                   inline-flex items-center px-4 py-2 rounded transition']); ?>
            ← Back
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

        <h1 class="text-2xl font-bold text-center 
                   text-gray-900 dark:text-gray-100">
            <?php echo e($company->company_name); ?> Dashboard
        </h1>
    </div>

    
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-6">
        <div class="rounded-lg p-6 text-center shadow 
                    bg-white dark:bg-gray-800
                    text-gray-900 dark:text-gray-100
                    border border-gray-200 dark:border-gray-700">
            <div class="text-3xl font-bold"><?php echo e($totalAdmins); ?></div>
            <div class="text-gray-600 dark:text-white mt-2">Total Admins</div>
        </div>

        <div class="rounded-lg p-6 text-center shadow 
                    bg-white dark:bg-gray-800
                    text-gray-900 dark:text-gray-100
                    border border-gray-200 dark:border-gray-700">
            <div class="text-3xl font-bold"><?php echo e($totalManagers); ?></div>
            <div class="text-gray-600 dark:text-white mt-2">Total Managers</div>
        </div>

        <div class="rounded-lg p-6 text-center shadow 
                    bg-white dark:bg-gray-800
                    text-gray-900 dark:text-gray-100
                    border border-gray-200 dark:border-gray-700">
            <div class="text-3xl font-bold"><?php echo e($totalUsers); ?></div>
            <div class="text-gray-600 dark:text-white mt-2">Total Users</div>
        </div>

        <div class="rounded-lg p-6 text-center shadow 
                    bg-white dark:bg-gray-800
                    text-gray-900 dark:text-gray-100
                    border border-gray-200 dark:border-gray-700">
            <div class="text-xl font-bold text-green-600 dark:text-green-400">
                <?php echo e($totalStorageUsed); ?>

            </div>
            <div class="text-gray-600 dark:text-white mt-2">Total Storage Used</div>
        </div>

        <div class="rounded-lg p-6 text-center shadow 
                    bg-white dark:bg-gray-800
                    text-gray-900 dark:text-gray-100
                    border border-gray-200 dark:border-gray-700">
            <div class="text-3xl font-bold"><?php echo e($totalPhotos); ?></div>
            <div class="text-gray-600 dark:text-white mt-2">Total Photos</div>
        </div>
    </div>

    
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Users</h2>

        <?php if (isset($component)) { $__componentOriginal6330f08526bbb3ce2a0da37da512a11f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6330f08526bbb3ce2a0da37da512a11f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.button.index','data' => ['wire:click' => 'createNewUserPage','color' => 'primary']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:click' => 'createNewUserPage','color' => 'primary']); ?>
            + New User
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

    
    <table class="min-w-full rounded-lg shadow overflow-hidden mb-6
                  bg-white dark:bg-gray-800
                  text-gray-900 dark:text-gray-100
                  border border-gray-200 dark:border-gray-700">

        <thead>
            <tr class="bg-gray-100 dark:bg-gray-700 
                       text-gray-600 dark:text-gray-200 
                       uppercase text-sm leading-normal">
                <th class="py-3 px-6 text-left">Name</th>
                <th class="py-3 px-6 text-left">Email</th>
                <th class="py-3 px-6 text-left">Role</th>
                <th class="py-3 px-6 text-left">Actions</th>
            </tr>
        </thead>

        <tbody class="text-gray-600 dark:text-white text-sm">
            <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr class="border-b border-gray-200 dark:border-gray-700 
                           hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="py-3 px-6"><?php echo e($user->name); ?></td>
                    <td class="py-3 px-6"><?php echo e($user->email); ?></td>
                    <td class="py-3 px-6 capitalize"><?php echo e($user->role); ?></td>
                    <td class="py-3 px-6">
                        <?php if (isset($component)) { $__componentOriginal6330f08526bbb3ce2a0da37da512a11f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6330f08526bbb3ce2a0da37da512a11f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.button.index','data' => ['color' => 'primary','size' => 'sm','wire:click' => 'editUserPage('.e($user->id).')']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['color' => 'primary','size' => 'sm','wire:click' => 'editUserPage('.e($user->id).')']); ?>
                            Edit
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
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
        </tbody>

    </table>

    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal166a02a7c5ef5a9331faf66fa665c256)): ?>
<?php $attributes = $__attributesOriginal166a02a7c5ef5a9331faf66fa665c256; ?>
<?php unset($__attributesOriginal166a02a7c5ef5a9331faf66fa665c256); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal166a02a7c5ef5a9331faf66fa665c256)): ?>
<?php $component = $__componentOriginal166a02a7c5ef5a9331faf66fa665c256; ?>
<?php unset($__componentOriginal166a02a7c5ef5a9331faf66fa665c256); ?>
<?php endif; ?>
<?php /**PATH D:\Vidhi\My Projects\ScanVault_backend-main\resources\views/filament/admin/resources/company-resource/pages/company-dashboard.blade.php ENDPATH**/ ?>