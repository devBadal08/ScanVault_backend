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

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        
        <div class="p-6 rounded-xl shadow-md border
                    bg-white dark:bg-gray-800
                    border-gray-200 dark:border-gray-700">

            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4">
                Managers (<?php echo e($managers->count()); ?>)
            </h2>

            <div class="space-y-3 max-h-[70vh] overflow-y-auto pr-2">

                <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $managers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="p-4 rounded-lg bg-gray-100 dark:bg-gray-700
                                text-gray-900 dark:text-gray-100">

                        <p class="font-semibold"><?php echo e($m->name); ?></p>
                        <p class="text-sm">Email: <?php echo e($m->email); ?></p>

                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->

            </div>
        </div>


        
        <div class="p-6 rounded-xl shadow-md border
                    bg-white dark:bg-gray-800
                    border-gray-200 dark:border-gray-700">

            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4">
                Users (<?php echo e($users->count()); ?>)
            </h2>

            <div class="space-y-3 max-h-[70vh] overflow-y-auto pr-2">

                <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="p-4 rounded-lg bg-gray-100 dark:bg-gray-700
                                text-gray-900 dark:text-gray-100">

                        <p class="font-semibold"><?php echo e($u->name); ?></p>
                        <p class="text-sm">Email: <?php echo e($u->email); ?></p>

                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->

            </div>
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