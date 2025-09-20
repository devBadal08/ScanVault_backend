<div class="grid grid-cols-5 gap-4">
    
    <div class="col-span-1 bg-gray-50 p-4 rounded shadow">
        <h3 class="font-bold mb-2">Folders</h3>
        <!--[if BLOCK]><![endif]--><?php $__empty_1 = true; $__currentLoopData = $folders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $folder): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <button 
                wire:click="selectFolder(<?php echo e($folder['id']); ?>)"
                class="block w-full text-left p-2 mb-1 rounded shadow hover:bg-gray-100
                       <?php echo e($selectedFolderId === $folder['id'] ? 'bg-blue-200 font-bold' : 'bg-white'); ?>">
                <?php echo e($folder['name']); ?>

            </button>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <p>No folders available.</p>
        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
    </div>

    
    <div class="col-span-4">
        <h2 class="text-xl font-bold mb-4">Folder Photos</h2>

        <!--[if BLOCK]><![endif]--><?php if($selectedFolderId && count($photos)): ?>
            <div class="grid grid-cols-4 gap-4">
                <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $photos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $photo): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="rounded overflow-hidden shadow">
                        <img src="<?php echo e(asset('storage/' . $photo['path'])); ?>" 
                             alt="Photo"
                             class="w-full h-40 object-cover">
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
            </div>
        <?php elseif($selectedFolderId): ?>
            <p>No photos found in this folder.</p>
        <?php else: ?>
            <p>Please select a folder.</p>
        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
    </div>
</div>
<?php /**PATH C:\xampp\htdocs\scanner-app\resources\views/filament/admin/pages/folder-photos-page.blade.php ENDPATH**/ ?>