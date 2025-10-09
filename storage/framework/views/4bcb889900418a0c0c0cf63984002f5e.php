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
        
        <!--[if BLOCK]><![endif]--><?php if(!$selectedManager && !$selectedUser): ?>
            <h2 class="text-xl font-bold mb-4">Select Manager or Admin User </h2>
            <div class="grid gap-2 mb-6" style="grid-template-columns: repeat(auto-fill, minmax(8rem, 1fr));">
                <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $managers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $manager): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div onclick="window.location.href='?manager=<?php echo e($manager->id); ?>'"
                        class="flex flex-col items-center justify-center w-32 h-32 bg-white rounded-lg shadow hover:shadow-md transition border hover:bg-orange-100 cursor-pointer text-center overflow-hidden group">
                        <div class="text-3xl">👨‍💼</div>
                        <div class="mt-1 text-[10px] font-semibold text-gray-800 truncate w-full px-1">
                            <?php echo e($manager->name); ?>

                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->

                
                <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $adminUsers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <a href="?user=<?php echo e($user->id); ?>"
                        class="flex flex-col items-center justify-center text-center font-medium transition duration-150 ease-in-out hover:text-blue-700">
                        <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-s-user'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-20 h-16','style' => 'color:#1D4ED8;']); ?>
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
                        <span class="mt-1 text-sm text-black truncate w-24"><?php echo e($user->name); ?></span>
                    </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
            </div>

        
        <?php elseif($selectedManager && !$selectedUser): ?>
            <div class="mb-4">
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
            <h2 class="text-xl font-bold mb-4">Users under <?php echo e($selectedManager->name); ?></h2>
            <div class="grid gap-2" style="grid-template-columns: repeat(auto-fill, minmax(6rem, 1fr));">
                <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <a href="?manager=<?php echo e($selectedManager->id); ?>&user=<?php echo e($user->id); ?>"
                        class="flex flex-col items-center justify-center text-center font-medium transition duration-150 ease-in-out hover:text-blue-700">
                        <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-s-user'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-20 h-16','style' => 'color:#1D4ED8;']); ?>
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
                        <span class="mt-1 text-sm text-black truncate w-24"><?php echo e($user->name); ?></span>
                    </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
            </div>

        
        <?php elseif($selectedUser && !$selectedFolder): ?>
            <div class="mb-4">
                <?php if (isset($component)) { $__componentOriginal6330f08526bbb3ce2a0da37da512a11f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6330f08526bbb3ce2a0da37da512a11f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.button.index','data' => ['tag' => 'a','href' => '?'.e($selectedManager ? 'manager='.$selectedManager->id : '').'','color' => 'primary','icon' => 'heroicon-o-arrow-left']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['tag' => 'a','href' => '?'.e($selectedManager ? 'manager='.$selectedManager->id : '').'','color' => 'primary','icon' => 'heroicon-o-arrow-left']); ?>
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
            </div>

            <h2 class="text-xl font-bold mb-4">Folders of <?php echo e($selectedUser->name); ?></h2>

            <div class="mb-4 flex justify-end">
                <?php if (isset($component)) { $__componentOriginal6330f08526bbb3ce2a0da37da512a11f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6330f08526bbb3ce2a0da37da512a11f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.button.index','data' => ['tag' => 'a','href' => ''.e(route('download-today-folders')).'','color' => 'success','icon' => 'heroicon-o-arrow-down-tray']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['tag' => 'a','href' => ''.e(route('download-today-folders')).'','color' => 'success','icon' => 'heroicon-o-arrow-down-tray']); ?>
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
                    <button class="w-full text-left px-4 py-2 bg-gray-100 hover:bg-gray-200 flex justify-between items-center accordion-header">
                        <span class="text-sm font-semibold"><?php echo e($group); ?></span>
                        <span class="text-sm">▼</span>
                    </button>

                    <div class="accordion-content px-4 py-2">
                        <div class="grid gap-4" style="grid-template-columns: repeat(auto-fill, minmax(7rem, 1fr));">
                            <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $folder): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="flex flex-col items-center justify-center text-center">
                                    
                                    <a href="<?php echo e(route('download-folder', ['path' => $folder['path']])); ?>" class="self-end -mb-6 mr-6 z-10 p-1 rounded-full hover:bg-gray-200" title="Download Folder">
                                        <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-arrow-down-tray'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-5 h-5 text-gray-700']); ?>
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

                                    
                                    <!--[if BLOCK]><![endif]--><?php if(isset($folder['linked']) && $folder['linked']): ?>
                                        <span class="absolute top-0 left-0 bg-blue-500 text-white text-xs px-1 rounded-br">Linked</span>
                                    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                                    
                                    <a href="?<?php echo e($selectedManager ? 'manager='.$selectedManager->id.'&' : ''); ?>&user=<?php echo e($selectedUser->id); ?>&folder=<?php echo e(urlencode($folder['path'])); ?>" class="flex flex-col items-center hover:text-yellow-600 transition duration-150 ease-in-out">
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.button.index','data' => ['tag' => 'a','href' => '?'.e($selectedManager ? 'manager='.$selectedManager->id.'&' : '').'&user='.e($selectedUser->id).'','color' => 'primary','icon' => 'heroicon-o-arrow-left']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['tag' => 'a','href' => '?'.e($selectedManager ? 'manager='.$selectedManager->id.'&' : '').'&user='.e($selectedUser->id).'','color' => 'primary','icon' => 'heroicon-o-arrow-left']); ?>
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
                    <span class="text-sm">Select All</span> (<span id="selected-count">0</span>)
                </label>
                <button id="download-selected" class="inline-flex items-center justify-center px-4 py-2 bg-primary-600 text-white rounded">
                    Download
                </button>
            </div>

            
            <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $date => $groupItems): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="mb-4 border rounded">
                    <button class="w-full text-left px-4 py-2 bg-gray-100 flex justify-between items-center accordion-header">
                        <span class="text-sm font-semibold"><?php echo e($date); ?></span>
                        <span class="text-sm">▼</span>
                    </button>

                    <div class="accordion-content px-4 py-3">
                        <div class="grid gap-3" style="grid-template-columns: repeat(auto-fill, minmax(8rem, 1fr));">
                            <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $groupItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <!--[if BLOCK]><![endif]--><?php if($item['type'] === 'folder'): ?>
                                    
                                    <div class="relative w-40 h-32 bg-white rounded shadow border text-center text-xs font-medium">
                                        
                                        <input type="checkbox"
                                            class="folder-checkbox absolute top-2 left-2 z-50"
                                            style="transform: scale(1.2);"
                                            value="<?php echo e(route('download-folder', ['path' => $item['path']])); ?>">

                                        <a href="<?php echo e(route('download-folder', ['path' => $item['path']])); ?>" class="absolute top-2 right-2 bg-white p-1 shadow hover:bg-gray-200 z-20" title="Download Subfolder">
                                            <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-arrow-down-tray'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-5 h-5 text-gray-700']); ?>
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

                                        
                                        <!--[if BLOCK]><![endif]--><?php if(isset($item['linked']) && $item['linked']): ?>
                                            <span class="absolute top-0 left-0 bg-blue-500 text-white text-xs px-1 rounded-br">Linked</span>
                                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                                        <a href="?<?php echo e($selectedManager ? 'manager='.$selectedManager->id.'&' : ''); ?>&user=<?php echo e($selectedUser->id); ?>&folder=<?php echo e(urlencode($selectedFolder)); ?>&subfolder=<?php echo e(urlencode($item['path'])); ?>" class="absolute inset-0 flex flex-col items-center justify-center px-2">
                                            <div class="text-3xl">📁</div>
                                            <div class="mt-1 truncate px-1 w-full" title="<?php echo e($item['name']); ?>">
                                                <?php echo e(\Illuminate\Support\Str::limit($item['name'], 10)); ?>

                                            </div>
                                        </a>
                                    </div>
                                <?php else: ?>
                                    
                                    <div class="relative w-32 h-32 rounded shadow overflow-hidden group">
                                        
                                        <div class="absolute top-1 left-1 z-50">
                                            <input type="checkbox"
                                                class="<?php echo e(isset($selectedSubfolder) ? 'image-checkbox-subfolder' : 'image-checkbox'); ?>"
                                                value="<?php echo e(asset('storage/' . $item['path'])); ?>">
                                        </div>

                                        <!--[if BLOCK]><![endif]--><?php if($item['type'] === 'image'): ?>
                                            <a href="javascript:void(0)"
                                                onclick="openImageModal('<?php echo e($item['name']); ?>', '<?php echo e(asset('storage/' . $item['path'])); ?>', '<?php echo e($item['created_at'] ?? 'N/A'); ?>', 'image')"
                                                class="w-full h-full block">
                                                <img src="<?php echo e(asset('storage/' . $item['path'])); ?>" class="w-full h-full object-cover rounded" alt="<?php echo e($item['name']); ?>">
                                            </a>
                                        <?php elseif($item['type'] === 'video'): ?>
                                            <a href="javascript:void(0)"
                                                onclick="openImageModal('<?php echo e($item['name']); ?>', '<?php echo e(asset('storage/' . $item['path'])); ?>', '<?php echo e($item['created_at'] ?? 'N/A'); ?>', 'video')"
                                                class="w-full h-full block">
                                                <video class="w-full h-full object-cover rounded">
                                                    <source src="<?php echo e(asset('storage/' . $item['path'])); ?>" type="video/mp4">
                                                </video>
                                            </a>
                                        <?php elseif($item['type'] === 'pdf'): ?>
                                            <a href="<?php echo e(asset('storage/' . $item['path'])); ?>" target="_blank"
                                                class="w-full h-full flex flex-col items-center justify-center bg-gray-100 rounded text-center p-2 text-xs hover:bg-gray-200 transition"
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.button.index','data' => ['tag' => 'a','href' => '?'.e($selectedManager ? 'manager='.$selectedManager->id.'&' : '').'&user='.e($selectedUser->id).'&folder='.e(urlencode($parentPath)).'','color' => 'primary','icon' => 'heroicon-o-arrow-left']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['tag' => 'a','href' => '?'.e($selectedManager ? 'manager='.$selectedManager->id.'&' : '').'&user='.e($selectedUser->id).'&folder='.e(urlencode($parentPath)).'','color' => 'primary','icon' => 'heroicon-o-arrow-left']); ?>
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
                    <span class="text-sm">Select All</span> (<span id="selected-count-subfolder">0</span>)
                </label>
                <button id="download-selected-subfolder" class="inline-flex items-center justify-center px-4 py-2 bg-primary-600 text-white rounded">
                    Download
                </button>
            </div>

            
            <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $date => $groupItems): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="mb-4 border rounded">
                    <button class="w-full text-left px-4 py-2 bg-gray-100 flex justify-between items-center accordion-header">
                        <span class="text-sm font-semibold"><?php echo e($date); ?></span>
                        <span class="text-sm">▼</span>
                    </button>

                    <div class="accordion-content px-4 py-3">
                        <div class="grid gap-3" style="grid-template-columns: repeat(auto-fill, minmax(8rem, 1fr));">
                            <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $groupItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <!--[if BLOCK]><![endif]--><?php if($item['type'] === 'folder'): ?>
                                    <div class="relative w-32 h-32 bg-white rounded shadow border text-center text-xs font-medium">
                                        <a href="<?php echo e(route('download-folder')); ?>?path=<?php echo e(urlencode($item['path'])); ?>" class="absolute top-2 right-2 bg-white p-1 rounded-full shadow hover:bg-gray-200 z-20" title="Download Subfolder">
                                            <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-arrow-down-tray'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-5 h-5 text-gray-700']); ?>
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

                                        <a href="?<?php echo e($selectedManager ? 'manager='.$selectedManager->id.'&' : ''); ?>&user=<?php echo e($selectedUser->id); ?>&folder=<?php echo e(urlencode($selectedFolder)); ?>&subfolder=<?php echo e(urlencode($item['path'])); ?>" class="absolute inset-0 flex flex-col items-center justify-center px-2">
                                            <div class="text-3xl">📁</div>
                                            <div class="mt-1 truncate px-1 w-full" title="<?php echo e($item['name']); ?>"><?php echo e(\Illuminate\Support\Str::limit($item['name'], 10)); ?></div>
                                        </a>
                                    </div>
                                <?php else: ?>
                                    
                                    <div class="relative w-32 h-32 rounded shadow overflow-hidden group">
                                        
                                        <div class="absolute top-1 left-1 z-50">
                                            <input type="checkbox"
                                                class="<?php echo e(isset($selectedSubfolder) ? 'image-checkbox-subfolder' : 'image-checkbox'); ?>"
                                                value="<?php echo e(asset('storage/' . $item['path'])); ?>">
                                        </div>

                                        <!--[if BLOCK]><![endif]--><?php if($item['type'] === 'image'): ?>
                                            <a href="javascript:void(0)"
                                                onclick="openImageModal('<?php echo e($item['name']); ?>', '<?php echo e(asset('storage/' . $item['path'])); ?>', '<?php echo e($item['created_at'] ?? 'N/A'); ?>', 'image')"
                                                class="w-full h-full block">
                                                <img src="<?php echo e(asset('storage/' . $item['path'])); ?>" class="w-full h-full object-cover rounded" alt="<?php echo e($item['name']); ?>">
                                            </a>
                                        <?php elseif($item['type'] === 'video'): ?>
                                            <a href="javascript:void(0)"
                                                onclick="openImageModal('<?php echo e($item['name']); ?>', '<?php echo e(asset('storage/' . $item['path'])); ?>', '<?php echo e($item['created_at'] ?? 'N/A'); ?>', 'video')"
                                                class="w-full h-full block">
                                                <video class="w-full h-full object-cover rounded">
                                                    <source src="<?php echo e(asset('storage/' . $item['path'])); ?>" type="video/mp4">
                                                </video>
                                            </a>
                                        <?php elseif($item['type'] === 'pdf'): ?>
                                            <a href="<?php echo e(asset('storage/' . $item['path'])); ?>" target="_blank"
                                                class="w-full h-full flex flex-col items-center justify-center bg-gray-100 rounded text-center p-2 text-xs hover:bg-gray-200 transition"
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

    <!-- Modal -->
    <div id="imageModal" class="hidden fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50 p-4 transition-opacity duration-300">
        <div class="bg-white rounded-lg shadow-2xl max-w-md w-full overflow-hidden relative animate-scale-up">
            
            <button onclick="closeImageModal()"
                    class="absolute top-3 right-3 text-gray-600 hover:text-gray-900 text-2xl font-bold transition">
                &times;
            </button>

            
            <div class="w-full bg-gray-100 flex items-center justify-center p-4">
                <div class="max-w-full max-h-[30vh] flex items-center justify-center">
                    <img id="modalImage" src="" class="max-h-[40vh] max-w-full object-contain rounded-lg" alt="Image Preview">
                    <video id="modalVideo" controls class="max-h-[30vh] max-w-full object-contain rounded-lg hidden">
                        <source id="modalVideoSource" src="" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                </div>
            </div>

            
            <div class="px-6 py-4 bg-white border-t border-gray-200">
                <h3 class="text-lg font-semibold mb-2">Media Details</h3>
                <p class="text-sm text-gray-700"><strong>Name:</strong> <span id="modalName"></span></p>
                <p class="text-sm text-gray-700"><strong>Path:</strong> <span id="modalPath"></span></p>
                <p class="text-sm text-gray-700"><strong>Created At:</strong> <span id="modalCreated"></span></p>
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
    function openImageModal(name, path, created, type = 'image') {
        const modal = document.getElementById('imageModal');
        const img = document.getElementById('modalImage');
        const video = document.getElementById('modalVideo');
        const videoSource = document.getElementById('modalVideoSource');

        // Reset: hide both
        img.classList.add('hidden');
        video.classList.add('hidden');
        video.pause();

        // Show correct media
        if(type === 'image') {
            img.src = path;
            img.classList.remove('hidden');
        } else if(type === 'video') {
            videoSource.src = path;
            video.load();
            video.classList.remove('hidden');
        }

        // Update details
        document.getElementById('modalName').innerText = name;
        document.getElementById('modalPath').innerText = path;
        document.getElementById('modalCreated').innerText = created;

        modal.classList.remove('hidden');
    }

    function closeImageModal() {
        const modal = document.getElementById('imageModal');
        const video = document.getElementById('modalVideo');
        modal.classList.add('hidden');
        video.pause();
    }

document.addEventListener('DOMContentLoaded', function () {
    // Accordion logic
    document.querySelectorAll('.accordion-header').forEach(header => {
        header.addEventListener('click', function () {
            const content = this.nextElementSibling;
            content.classList.toggle('hidden');
            this.querySelector('span:last-child').classList.toggle('rotate-180');
        });
    });

    const updateCount = (selector, countId) => {
        document.getElementById(countId).textContent = `${document.querySelectorAll(selector+':checked').length} selected`;
    };

    // Folder level
    const folderCheckboxes = document.querySelectorAll('.image-checkbox, .folder-checkbox');
    document.getElementById('select-all')?.addEventListener('change', function () {
        folderCheckboxes.forEach(cb => cb.checked = this.checked);
        updateCount('.image-checkbox, .folder-checkbox', 'selected-count');
    });
    folderCheckboxes.forEach(cb => cb.addEventListener('change', () => updateCount('.image-checkbox, .folder-checkbox', 'selected-count')));

    // Subfolder level
    const subfolderCheckboxes = document.querySelectorAll('.image-checkbox-subfolder, .folder-checkbox');
    document.getElementById('select-all-subfolder')?.addEventListener('change', function () {
        subfolderCheckboxes.forEach(cb => cb.checked = this.checked);
        updateCount('.image-checkbox-subfolder, .folder-checkbox', 'selected-count-subfolder');
    });
    subfolderCheckboxes.forEach(cb => cb.addEventListener('change', () => updateCount('.image-checkbox-subfolder, .folder-checkbox', 'selected-count-subfolder')));

    // Download logic (folder & subfolder)
    const download = (selector) => {
        const selected = [...document.querySelectorAll(selector+':checked')].map(cb => cb.value);
        if (!selected.length) return alert('Please select at least one image to download.');

        selected.forEach((url, i) => {
            setTimeout(() => {
                const a = document.createElement('a');
                a.href = url;
                a.download = '';
                a.style.display = 'none';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
            }, i * 300); // 300ms delay between downloads
        });
    };
    document.getElementById('download-selected')?.addEventListener('click', () => download('.image-checkbox, .folder-checkbox'));
    document.getElementById('download-selected-subfolder')?.addEventListener('click', () => download('.image-checkbox-subfolder, .folder-checkbox'));
});
</script>
<?php /**PATH C:\xampp\htdocs\ScanVault_backend-main\resources\views/filament/admin/pages/admin-users-page.blade.php ENDPATH**/ ?>