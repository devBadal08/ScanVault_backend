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
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

        
        <div
            class="p-6 rounded-xl shadow-sm border
                   bg-white dark:bg-gray-800
                   border-gray-200 dark:border-gray-700
                   text-gray-900 dark:text-gray-100
            "
        >
            <h3 class="text-lg font-semibold">Total Managers</h3>
            <p class="text-3xl font-bold mt-2"><?php echo e($totals['managers']); ?></p>
        </div>

        
        <div
            class="p-6 rounded-xl shadow-sm border
                   bg-white dark:bg-gray-800
                   border-gray-200 dark:border-gray-700
                   text-gray-900 dark:text-gray-100
            "
        >
            <h3 class="text-lg font-semibold">Total Users</h3>
            <p class="text-3xl font-bold mt-2"><?php echo e($totals['users']); ?></p>
        </div>

    </div>
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
<?php /**PATH C:\xampp\htdocs\ScanVault_backend-main\resources\views/filament/admin/pages/user-list.blade.php ENDPATH**/ ?>