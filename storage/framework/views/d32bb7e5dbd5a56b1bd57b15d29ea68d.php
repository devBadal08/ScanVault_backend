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
    <h2 class="text-2xl font-bold mb-6">Users in <?php echo e($company->company_name); ?></h2>

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 mb-8">
        <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <button wire:click="selectUser(<?php echo e($user->id); ?>)"
                class="p-4 w-full text-left rounded-lg border
                    <?php echo e($selectedUser && $selectedUser->id == $user->id ? 'bg-green-100 border-green-500' : 'hover:bg-gray-100'); ?>">
                <strong><?php echo e($user->name); ?></strong>
                <span class="text-sm text-gray-500">(<?php echo e(ucfirst($user->role)); ?>)</span>
            </button>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
    </div>

    <!--[if BLOCK]><![endif]--><?php if($selectedUser): ?>
        <div class="mt-8">
            <h3 class="text-2xl font-bold mb-4">Permissions for <?php echo e($selectedUser->name); ?></h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <label class="flex items-center p-4 bg-white rounded-lg shadow hover:shadow-lg transition-all cursor-pointer">
                    <input type="checkbox" wire:model.defer="permissions.show_total_users" class="form-checkbox h-5 w-5 text-blue-600 mr-3">
                    <span class="text-base">Show Total Users</span>
                </label>
                <label class="flex items-center p-4 bg-white rounded-lg shadow hover:shadow-lg transition-all cursor-pointer">
                    <input type="checkbox" wire:model.defer="permissions.show_total_managers" class="form-checkbox h-5 w-5 text-blue-600 mr-3">
                    <span class="text-base">Show Total Managers</span>
                </label>
                <label class="flex items-center p-4 bg-white rounded-lg shadow hover:shadow-lg transition-all cursor-pointer">
                    <input type="checkbox" wire:model.defer="permissions.show_total_admins" class="form-checkbox h-5 w-5 text-blue-600 mr-3">
                    <span class="text-base">Show Total Admins</span>
                </label>
                <label class="flex items-center p-4 bg-white rounded-lg shadow hover:shadow-lg transition-all cursor-pointer">
                    <input type="checkbox" wire:model.defer="permissions.show_total_limit"
                        class="form-checkbox h-5 w-5 text-blue-600 mr-3">
                    <span class="text-md">Show Total Limit</span>
                </label>
                <label class="flex items-center p-4 bg-white rounded-lg shadow hover:shadow-lg transition-all cursor-pointer">
                    <input type="checkbox" wire:model.defer="permissions.show_total_storage"
                        class="form-checkbox h-5 w-5 text-blue-600 mr-3">
                    <span class="text-md">Show Total Storage</span>
                </label>
            </div>

            <?php if (isset($component)) { $__componentOriginal6330f08526bbb3ce2a0da37da512a11f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6330f08526bbb3ce2a0da37da512a11f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.button.index','data' => ['wire:click' => 'savePermissions','class' => 'mt-6 px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:click' => 'savePermissions','class' => 'mt-6 px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition']); ?>
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