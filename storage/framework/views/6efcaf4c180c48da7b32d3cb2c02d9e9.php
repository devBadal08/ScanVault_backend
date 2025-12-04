<?php use Illuminate\Support\Facades\Auth; ?>
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
    <div>
        <div class="mb-6">
            <input
                type="text"
                wire:model.live="globalSearch"
                wire:ignore.self
                placeholder="🔍 Global Search (folders, images, files...)"
                class="w-full px-4 py-3 rounded-xl border
                    border-gray-300 dark:border-gray-700
                    bg-white dark:bg-gray-900
                    text-gray-900 dark:text-white
                    focus:ring-2 focus:ring-orange-500 shadow"
            >
        </div>

        <!--[if BLOCK]><![endif]--><?php if(!empty($globalResults)): ?>
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 mb-6 shadow border">
                <h3 class="text-lg font-bold mb-2">Search Results</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $globalResults; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <a
                            href="?user=<?php echo e($item['user_id']); ?>&folder=<?php echo e(urlencode($item['path'])); ?>"
                            class="p-3 rounded-lg border hover:bg-orange-100 dark:hover:bg-orange-900/30 transition"
                        >
                            <div class="text-sm font-semibold">
                                <?php echo e($item['name']); ?>

                            </div>

                            <div class="text-xs text-gray-500">
                                <?php echo e(strtoupper($item['type'])); ?> in <?php echo e($item['user']); ?>

                            </div>
                        </a>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
                </div>
            </div>
        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
        <br>

        
        <!--[if BLOCK]><![endif]--><?php if(!$selectedUser): ?>
            <h2 class="text-xl font-bold mb-4">Select User</h2>
            <div class="grid gap-4 mb-6"
                style="grid-template-columns: repeat(auto-fill, minmax(8rem, 1fr));">

                <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $managerUsers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div onclick="window.location.href='?user=<?php echo e($user->id); ?>'"
                        class="flex flex-col items-center justify-center w-32 h-32 rounded-lg shadow border
                            bg-white dark:bg-gray-800
                            border-gray-200 dark:border-gray-700
                            hover:bg-orange-100 dark:hover:bg-orange-900/40
                            text-center overflow-hidden cursor-pointer transition group">

                        <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-s-user'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-11 h-11 text-blue-600 dark:text-blue-400','style' => 'color:#1D4ED8;']); ?>
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

                        <div class="mt-1 text-[10px] font-semibold 
                                    text-gray-800 dark:text-gray-200
                                    truncate w-full px-1">
                            <?php echo e($user->name); ?>

                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->

            </div>

        
        <?php elseif($selectedUser && !$selectedFolder): ?>
            <h2 class="text-xl font-bold mb-4">Folders of <?php echo e($selectedUser->name); ?></h2>

            <div class="mb-4 flex justify-between items-center">
                
                <?php if (isset($component)) { $__componentOriginal6330f08526bbb3ce2a0da37da512a11f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6330f08526bbb3ce2a0da37da512a11f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.button.index','data' => ['tag' => 'a','href' => ''.e(url()->current()).'','color' => 'primary','icon' => 'heroicon-o-arrow-left']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['tag' => 'a','href' => ''.e(url()->current()).'','color' => 'primary','icon' => 'heroicon-o-arrow-left']); ?>
                    Back to Users
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

                
                <?php if (isset($component)) { $__componentOriginal6330f08526bbb3ce2a0da37da512a11f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6330f08526bbb3ce2a0da37da512a11f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.button.index','data' => ['tag' => 'a','href' => ''.e(route('download-today-folders', ['user' => $selectedUser->id])).'','color' => 'success','icon' => 'heroicon-o-arrow-down-tray']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['tag' => 'a','href' => ''.e(route('download-today-folders', ['user' => $selectedUser->id])).'','color' => 'success','icon' => 'heroicon-o-arrow-down-tray']); ?>
                    Download Today’s Folders
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
            
            <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $folders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $group => $items): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="mb-2 border rounded">
                    <button class="w-full text-left px-4 py-2 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-900 dark:text-white flex justify-between items-center accordion-header">
                        <span class="text-sm font-semibold"><?php echo e($group); ?></span>
                        <span class="text-sm">▼</span>
                    </button>
                    <div class="accordion-content px-4 py-2">
                        <div class="grid gap-4" style="grid-template-columns: repeat(auto-fill, minmax(7rem, 1fr));">
                            <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $folder): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="flex flex-col items-center justify-center text-center">
                                    
                                    <a href="<?php echo e(route('download-folder', ['path' => $folder['path']])); ?>"
                                        class="self-end -mb-6 mr-6 z-10 p-1 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700"
                                        title="Download Folder">
                                        <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-arrow-down-tray'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-5 h-5 text-gray-700 dark:text-white']); ?>
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
                                    </a>
                                    
                                    <a href="?user=<?php echo e($selectedUser->id); ?>&folder=<?php echo e(urlencode($folder['path'])); ?>"
                                        class="flex flex-col items-center hover:text-yellow-600 transition duration-150 ease-in-out">
                                        <div class="w-24 h-24 flex items-center justify-center">
                                            <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-s-folder'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-16 h-16 text-yellow-500','style' => 'color: #facc15;']); ?>
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
                                        <span class="mt-1 text-xs text-black truncate w-24" title="<?php echo e($folder['name']); ?>">
                                            <?php echo e(\Illuminate\Support\Str::limit($folder['name'], 10)); ?>

                                        </span>
                                    </a>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->

            
            <!--[if BLOCK]><![endif]--><?php if($total > $perPage): ?>
                <div class="mt-4 flex justify-center space-x-2">
                    <!--[if BLOCK]><![endif]--><?php if($page > 1): ?>
                        <a href="<?php echo e(request()->fullUrlWithQuery(['page' => $page - 1])); ?>" class="px-3 py-1 bg-gray-200 rounded">Previous</a>
                    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                    <!--[if BLOCK]><![endif]--><?php for($i = 1; $i <= ceil($total / $perPage); $i++): ?>
                        <a href="<?php echo e(request()->fullUrlWithQuery(['page' => $i])); ?>" class="px-3 py-1 rounded <?php echo e($i == $page ? 'bg-blue-500 text-white' : 'bg-gray-200 hover:bg-gray-300'); ?>"><?php echo e($i); ?></a>
                    <?php endfor; ?><!--[if ENDBLOCK]><![endif]-->

                    <!--[if BLOCK]><![endif]--><?php if($page < ceil($total / $perPage)): ?>
                        <a href="<?php echo e(request()->fullUrlWithQuery(['page' => $page + 1])); ?>" class="px-3 py-1 bg-gray-200 rounded">Next</a>
                    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                </div>
            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

        
        <?php elseif($selectedFolder && !$selectedSubfolder): ?>
            <h2 class="text-xl font-bold mb-4">Content in <?php echo e(basename($selectedFolder)); ?></h2>

            <div class="mb-4">
                <?php if (isset($component)) { $__componentOriginal6330f08526bbb3ce2a0da37da512a11f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6330f08526bbb3ce2a0da37da512a11f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.button.index','data' => ['tag' => 'a','href' => '?user='.e($selectedUser->id).'','color' => 'primary','icon' => 'heroicon-o-arrow-left']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['tag' => 'a','href' => '?user='.e($selectedUser->id).'','color' => 'primary','icon' => 'heroicon-o-arrow-left']); ?>
                    Back
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

            
            <div class="flex items-center justify-between mb-2">
                <label class="flex items-center space-x-2">
                    <input type="checkbox" id="select-all" class="form-checkbox">
                    <span class="text-sm">Select All</span>
                    (<span id="selected-count">0</span>)
                </label>
                <button id="download-selected" class="inline-flex items-center justify-center px-4 py-2 bg-primary-600 text-white rounded">
                    Download
                </button>
            </div>

            
            <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $date => $groupItems): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="mb-4 border rounded">
                    <button class="w-full text-left px-4 py-2 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-900 dark:text-white flex justify-between items-center accordion-header">
                        <span class="text-sm font-semibold"><?php echo e($date); ?></span>
                        <span class="text-sm">▼</span>
                    </button>

                    <div class="accordion-content px-4 py-3">
                        <div class="grid gap-3" style="grid-template-columns: repeat(auto-fill, minmax(8rem, 1fr));">
                            <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $groupItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <!--[if BLOCK]><![endif]--><?php if($item['type'] === 'folder'): ?>
                                    
                                    <div class="relative w-32 h-36 bg-white dark:bg-gray-800 rounded shadow border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white text-xs font-medium overflow-hidden flex flex-col">
                                        
                                        <div class="flex justify-between items-start p-1">
                                            <input type="checkbox"
                                                class="folder-checkbox"
                                                style="transform: scale(1.2);"
                                                value="<?php echo e(route('download-folder', ['path' => $item['path']])); ?>">

                                            <a href="<?php echo e(route('download-folder')); ?>?path=<?php echo e(urlencode($item['path'])); ?>"
                                            class="p-1 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700"
                                            title="Download Subfolder">
                                                <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-arrow-down-tray'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-5 h-5 text-gray-700 dark:text-white']); ?>
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
                                            </a>
                                        </div>

                                        
                                        <a href="?user=<?php echo e($selectedUser->id); ?>&folder=<?php echo e(urlencode($selectedFolder)); ?>&subfolder=<?php echo e(urlencode($item['path'])); ?>"
                                        class="flex flex-col items-center justify-center flex-1 px-2">
                                            <div class="text-3xl">📁</div>
                                            <div class="mt-1 truncate w-full text-center" title="<?php echo e($item['name']); ?>">
                                                <?php echo e(\Illuminate\Support\Str::limit($item['name'], 10)); ?>

                                            </div>
                                        </a>
                                    </div>

                                <?php else: ?>
                                    
                                    <div class="relative w-32 h-32 rounded shadow overflow-hidden group">
                                        
                                        <div class="flex justify-between items-start p-1">
                                            <div class="flex items-center space-x-1">
                                                <input type="checkbox"
                                                    class="<?php echo e(isset($selectedSubfolder) ? 'image-checkbox-subfolder' : 'image-checkbox'); ?>"
                                                    value="<?php echo e(asset('storage/' . $item['path'])); ?>">
                                                <!--[if BLOCK]><![endif]--><?php if(isset($item['linked']) && $item['linked']): ?>
                                                    <span class="bg-blue-500 text-white text-[10px] px-1 rounded">Linked</span>
                                                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                                            </div>

                                            
                                            <button 
                                                onclick="openPropertiesModal('<?php echo e($item['name']); ?>', '<?php echo e($item['type']); ?>', '<?php echo e($item['created_at'] ?? 'N/A'); ?>', '<?php echo e(asset('storage/' . $item['path'])); ?>')"
                                                class="p-1 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700 transition"
                                                title="More options">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-700 dark:text-white" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M10 3a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3z" />
                                                </svg>
                                            </button>
                                        </div>

                                        <!--[if BLOCK]><![endif]--><?php if($item['type'] === 'image'): ?>
                                            <a href="<?php echo e(asset('storage/' . $item['path'])); ?>" target="_blank" class="w-full h-full block">
                                                <img src="<?php echo e(asset('storage/' . $item['path'])); ?>" 
                                                    class="w-full h-full object-cover rounded" 
                                                    alt="<?php echo e($item['name']); ?>">
                                            </a>
                                        <?php elseif($item['type'] === 'video'): ?>
                                            <a href="<?php echo e(asset('storage/' . $item['path'])); ?>" target="_blank" class="w-full h-full block">
                                                <video class="w-full h-full object-cover rounded" controls>
                                                    <source src="<?php echo e(asset('storage/' . $item['path'])); ?>" type="video/mp4">
                                                </video>
                                            </a>
                                        <?php elseif($item['type'] === 'pdf'): ?>
                                            <a href="<?php echo e(asset('storage/' . $item['path'])); ?>" target="_blank"
                                                class="w-full h-full flex flex-col items-center justify-center bg-gray-100 dark:bg-gray-800 rounded text-gray-900 dark:text-white rounded text-center p-2 text-xs hover:bg-gray-200 dark:hover:bg-gray-700 transition"
                                                title="<?php echo e($item['name']); ?>">
                                                <div class="text-3xl">📄</div>
                                                <div class="mt-1 truncate w-full"><?php echo e(\Illuminate\Support\Str::limit($item['name'], 10)); ?></div>
                                            </a>
                                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                                    </div>
                                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->

            
            <!--[if BLOCK]><![endif]--><?php if($total > $perPage): ?>
                <div class="mt-4 flex justify-center space-x-2">
                    <!--[if BLOCK]><![endif]--><?php if($page > 1): ?>
                        <a href="<?php echo e(request()->fullUrlWithQuery(['page' => $page - 1])); ?>" class="px-3 py-1 bg-gray-200 rounded">Previous</a>
                    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                    <!--[if BLOCK]><![endif]--><?php for($i = 1; $i <= ceil($total / $perPage); $i++): ?>
                        <a href="<?php echo e(request()->fullUrlWithQuery(['page' => $i])); ?>" class="px-3 py-1 rounded <?php echo e($i == $page ? 'bg-blue-500 text-white' : 'bg-gray-200 hover:bg-gray-300'); ?>"><?php echo e($i); ?></a>
                    <?php endfor; ?><!--[if ENDBLOCK]><![endif]-->

                    <!--[if BLOCK]><![endif]--><?php if($page < ceil($total / $perPage)): ?>
                        <a href="<?php echo e(request()->fullUrlWithQuery(['page' => $page + 1])); ?>" class="px-3 py-1 bg-gray-200 rounded">Next</a>
                    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                </div>
            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

        
        <?php elseif($selectedSubfolder): ?>
            <h2 class="text-xl font-bold mb-4">Content in <?php echo e(basename($selectedSubfolder)); ?></h2>

            <div class="mb-4">
                <?php $parentPath = dirname($selectedSubfolder); ?>

                <?php if (isset($component)) { $__componentOriginal6330f08526bbb3ce2a0da37da512a11f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6330f08526bbb3ce2a0da37da512a11f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.button.index','data' => ['tag' => 'a','href' => '?user='.e($selectedUser->id).'&folder='.e(urlencode($parentPath)).'','color' => 'primary','icon' => 'heroicon-o-arrow-left']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['tag' => 'a','href' => '?user='.e($selectedUser->id).'&folder='.e(urlencode($parentPath)).'','color' => 'primary','icon' => 'heroicon-o-arrow-left']); ?>
                    Back to <?php echo e(basename($parentPath)); ?>

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

            
            <div class="flex items-center justify-between mb-2">
                <label class="flex items-center space-x-2">
                    <input type="checkbox" id="select-all-subfolder" class="form-checkbox">
                    <span class="text-sm">Select All</span>
                    (<span id="selected-count-subfolder">0</span>)
                </label>
                <button id="download-selected-subfolder" class="inline-flex items-center justify-center px-4 py-2 bg-primary-600 text-white rounded">
                    Download
                </button>
            </div>

            
            <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $date => $groupItems): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="mb-4 border rounded">
                    <button class="w-full text-left px-4 py-2 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-900 dark:text-white flex justify-between items-center accordion-header">
                        <span class="text-sm font-semibold"><?php echo e($date); ?></span>
                        <span class="text-sm">▼</span>
                    </button>

                    <div class="accordion-content px-4 py-3">
                        <div class="grid gap-3" style="grid-template-columns: repeat(auto-fill, minmax(8rem, 1fr));">
                            <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $groupItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <!--[if BLOCK]><![endif]--><?php if($item['type'] === 'folder'): ?>
                                    <div class="relative w-32 h-36 bg-white dark:bg-gray-800 rounded shadow border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white text-xs font-medium overflow-hidden flex flex-col">
                                        
                                        <div class="flex justify-between items-start p-1">
                                            <input type="checkbox"
                                                class="folder-checkbox"
                                                style="transform: scale(1.2);"
                                                value="<?php echo e(route('download-folder', ['path' => $item['path']])); ?>">

                                            <a href="<?php echo e(route('download-folder')); ?>?path=<?php echo e(urlencode($item['path'])); ?>"
                                            class="p-1 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700"
                                            title="Download Subfolder">
                                                <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-arrow-down-tray'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-5 h-5 text-gray-700 dark:text-white']); ?>
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
                                            </a>
                                        </div>

                                        
                                        <a href="?user=<?php echo e($selectedUser->id); ?>&folder=<?php echo e(urlencode($selectedFolder)); ?>&subfolder=<?php echo e(urlencode($item['path'])); ?>"
                                        class="flex flex-col items-center justify-center flex-1 px-2">
                                            <div class="text-3xl">📁</div>
                                            <div class="mt-1 truncate w-full text-center" title="<?php echo e($item['name']); ?>">
                                                <?php echo e(\Illuminate\Support\Str::limit($item['name'], 10)); ?>

                                            </div>
                                        </a>
                                    </div>
                                <?php else: ?>
                                    
                                    <div class="relative w-32 h-32 rounded shadow overflow-hidden group">
                                        
                                        <div class="flex justify-between items-start p-1">
                                            <div class="flex items-center space-x-1">
                                                <input type="checkbox"
                                                    class="<?php echo e(isset($selectedSubfolder) ? 'image-checkbox-subfolder' : 'image-checkbox'); ?>"
                                                    value="<?php echo e(asset('storage/' . $item['path'])); ?>">
                                                <!--[if BLOCK]><![endif]--><?php if(isset($item['linked']) && $item['linked']): ?>
                                                    <span class="bg-blue-500 text-white text-[10px] px-1 rounded">Linked</span>
                                                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                                            </div>

                                            
                                            <button 
                                                onclick="openPropertiesModal('<?php echo e($item['name']); ?>', '<?php echo e($item['type']); ?>', '<?php echo e($item['created_at'] ?? 'N/A'); ?>', '<?php echo e(asset('storage/' . $item['path'])); ?>')"
                                                class="p-1 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700 transition"
                                                title="More options">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-700 dark:text-white" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M10 3a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3z" />
                                                </svg>
                                            </button>
                                        </div>

                                        <!--[if BLOCK]><![endif]--><?php if($item['type'] === 'image'): ?>
                                            <a href="<?php echo e(asset('storage/' . $item['path'])); ?>" target="_blank" class="w-full h-full block">
                                                <img src="<?php echo e(asset('storage/' . $item['path'])); ?>" 
                                                    class="w-full h-full object-cover rounded" 
                                                    alt="<?php echo e($item['name']); ?>">
                                            </a>
                                        <?php elseif($item['type'] === 'video'): ?>
                                            <a href="<?php echo e(asset('storage/' . $item['path'])); ?>" target="_blank" class="w-full h-full block">
                                                <video class="w-full h-full object-cover rounded" controls>
                                                    <source src="<?php echo e(asset('storage/' . $item['path'])); ?>" type="video/mp4">
                                                </video>
                                            </a>
                                        <?php elseif($item['type'] === 'pdf'): ?>
                                            <a href="<?php echo e(asset('storage/' . $item['path'])); ?>" target="_blank"
                                                class="w-full h-full flex flex-col items-center justify-center bg-gray-100 dark:bg-gray-800 rounded text-gray-900 dark:text-white rounded text-center p-2 text-xs hover:bg-gray-200 dark:hover:bg-gray-700 transition"
                                                title="<?php echo e($item['name']); ?>">
                                                <div class="text-3xl">📄</div>
                                                <div class="mt-1 truncate w-full"><?php echo e(\Illuminate\Support\Str::limit($item['name'], 10)); ?></div>
                                            </a>
                                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                                    </div>
                                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->

            
            <!--[if BLOCK]><![endif]--><?php if($total > $perPage): ?>
                <div class="mt-4 flex justify-center space-x-2">
                    <!--[if BLOCK]><![endif]--><?php if($page > 1): ?>
                        <a href="<?php echo e(request()->fullUrlWithQuery(['page' => $page - 1])); ?>" class="px-3 py-1 bg-gray-200 rounded">Previous</a>
                    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                    <!--[if BLOCK]><![endif]--><?php for($i = 1; $i <= ceil($total / $perPage); $i++): ?>
                        <a href="<?php echo e(request()->fullUrlWithQuery(['page' => $i])); ?>" class="px-3 py-1 rounded <?php echo e($i == $page ? 'bg-blue-500 text-white' : 'bg-gray-200 hover:bg-gray-300'); ?>"><?php echo e($i); ?></a>
                    <?php endfor; ?><!--[if ENDBLOCK]><![endif]-->

                    <!--[if BLOCK]><![endif]--><?php if($page < ceil($total / $perPage)): ?>
                        <a href="<?php echo e(request()->fullUrlWithQuery(['page' => $page + 1])); ?>" class="px-3 py-1 bg-gray-200 rounded">Next</a>
                    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                </div>
            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
    </div>

    <!-- Properties Modal -->
    <div id="propertiesModal"
        class="hidden fixed inset-0 bg-black/50 dark:bg-black/60 
            flex items-center justify-center z-50 p-4 transition">

        <div class="rounded-lg shadow-2xl w-full max-w-sm relative p-6
                    bg-white dark:bg-gray-800
                    border border-gray-200 dark:border-gray-700
                    text-gray-900 dark:text-white">

            
            <button onclick="closePropertiesModal()"
                class="absolute top-3 right-3 text-gray-600 dark:text-white 
                    hover:text-gray-800 dark:hover:text-white 
                    text-2xl leading-none focus:outline-none transition">
                &times;
            </button>

            <h3 class="text-lg font-bold mb-4 text-center">
                File Properties
            </h3>

            <div class="space-y-3 text-sm break-words overflow-hidden">

                
                <p class="truncate">
                    <strong>Name:</strong>
                    <span id="prop-name"
                        class="break-words block text-gray-700 dark:text-white">
                    </span>
                </p>

                
                <p>
                    <strong>Created At:</strong>
                    <span id="prop-date" class="text-gray-700 dark:text-white"></span>
                </p>

                
                <p>
                    <strong>Path:</strong>
                    <span id="prop-path"
                        class="break-all block max-h-24 overflow-y-auto p-2 rounded
                            bg-blue-50 dark:bg-blue-900/30
                            text-blue-600 dark:text-blue-300 border 
                            border-blue-200 dark:border-blue-800">
                    </span>
                </p>
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

<script>
    function openPropertiesModal(name, type, date, path) {
        document.getElementById('prop-name').innerText = name;
        document.getElementById('prop-date').innerText = date;
        document.getElementById('prop-path').innerText = path;
        document.getElementById('propertiesModal').classList.remove('hidden');
    }

    function closePropertiesModal() {
        document.getElementById('propertiesModal').classList.add('hidden');
    }

    document.addEventListener('DOMContentLoaded', function () {
        // Accordion toggle
        document.querySelectorAll('.accordion-header').forEach(header => {
            header.addEventListener('click', function () {
                const content = this.nextElementSibling;
                content.classList.toggle('hidden');
                this.querySelector('span:last-child').classList.toggle('rotate-180');
            });
        });

        // Function to update count text
        const updateCount = (selector, countId) => {
            const count = document.querySelectorAll(selector).length;
            document.getElementById(countId).textContent = `${count} selected`;
        };

        // -------- Folder Level --------
        const folderCheckboxes = document.querySelectorAll('.image-checkbox, .folder-checkbox');
        const selectAll = document.getElementById('select-all');

        if (selectAll) {
            selectAll.addEventListener('change', function () {
                folderCheckboxes.forEach(cb => cb.checked = this.checked);
                updateCount('.image-checkbox:checked, .folder-checkbox:checked', 'selected-count');
                if (!this.checked) document.getElementById('selected-count').textContent = '0 selected';
            });
        }

        folderCheckboxes.forEach(cb => {
            cb.addEventListener('change', () => {
                updateCount('.image-checkbox:checked, .folder-checkbox:checked', 'selected-count');
            });
        });

        // -------- Subfolder Level (images + folders) --------
        const subfolderCheckboxes = document.querySelectorAll('.image-checkbox-subfolder, .folder-checkbox, .folder-checkbox-subfolder');
        const selectAllSubfolder = document.getElementById('select-all-subfolder');

        if (selectAllSubfolder) {
            selectAllSubfolder.addEventListener('change', function () {
                subfolderCheckboxes.forEach(cb => cb.checked = this.checked);
                updateCount('.image-checkbox-subfolder:checked, .folder-checkbox:checked, .folder-checkbox-subfolder:checked', 'selected-count-subfolder');
                if (!this.checked) document.getElementById('selected-count-subfolder').textContent = '0 selected';
            });
        }

        subfolderCheckboxes.forEach(cb => {
            cb.addEventListener('change', () => {
                updateCount('.image-checkbox-subfolder:checked, .folder-checkbox:checked, .folder-checkbox-subfolder:checked', 'selected-count-subfolder');
            });
        });

        // -------- Download Selected --------
        const download = (selector) => {
            const selected = [...document.querySelectorAll(selector.replace(/([^,]+)/g, '$1:checked'))].map(cb => cb.value);
            if (!selected.length) return alert('Please select at least one item to download.');

            selected.forEach((url, i) => {
                setTimeout(() => {
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = '';
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                }, i * 300);
            });
        };

        document.getElementById('download-selected')?.addEventListener('click', () =>
            download('.image-checkbox, .folder-checkbox')
        );
        document.getElementById('download-selected-subfolder')?.addEventListener('click', () =>
            download('.image-checkbox-subfolder, .folder-checkbox, .folder-checkbox-subfolder')
        );
    });
</script>
<?php /**PATH C:\xampp\htdocs\ScanVault_backend-main\resources\views/filament/admin/pages/manager-users-page.blade.php ENDPATH**/ ?>