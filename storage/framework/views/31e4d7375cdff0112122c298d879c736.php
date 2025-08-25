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

        
        <?php elseif($selectedUser && !$selectedFolder): ?>
            <div class="mb-4">
                <?php if (isset($component)) { $__componentOriginal6330f08526bbb3ce2a0da37da512a11f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6330f08526bbb3ce2a0da37da512a11f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.button.index','data' => ['tag' => 'a','href' => ''.e(url()->current()).'?'.e($selectedManager ? 'manager='.$selectedManager->id : '').'','color' => 'primary','icon' => 'heroicon-o-arrow-left']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['tag' => 'a','href' => ''.e(url()->current()).'?'.e($selectedManager ? 'manager='.$selectedManager->id : '').'','color' => 'primary','icon' => 'heroicon-o-arrow-left']); ?>
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
            <h2 class="text-xl font-bold mb-4">Folders of <?php echo e($selectedUser->name); ?></h2>
            <div class="grid gap-4" style="grid-template-columns: repeat(auto-fill, minmax(7rem, 1fr));">
                <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $folders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $folder): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="flex flex-col items-center justify-center text-center">
                        
                        <a href="<?php echo e(route('download-folder', ['path' => $folder])); ?>"
                            class="self-end -mb-6 mr-6 z-10 p-1 rounded-full hover:bg-gray-200"
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

                        
                        <a href="?<?php echo e($selectedManager ? 'manager='.$selectedManager->id.'&' : ''); ?>user=<?php echo e($selectedUser->id); ?>&folder=<?php echo e($folder); ?>"
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
<?php $component->withAttributes(['class' => 'w-20 h-20','style' => 'color: #facc15;']); ?>
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
                            <span class="mt-1 text-sm text-black truncate w-24" title="<?php echo e(basename($folder)); ?>">
                                <?php echo e(Str::limit(basename($folder), 10)); ?>

                            </span>
                        </a>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
            </div>

        
        <?php elseif($selectedFolder && !$selectedSubfolder): ?>
            <h2 class="text-xl font-bold mb-4">Content in <?php echo e(basename($selectedFolder)); ?></h2>

            
            <div class="flex items-center justify-between mb-2 ">
                <label class="flex items-center space-x-8">
                    <input type="checkbox" id="select-all" class="form-checkbox">
                    <span>Select All</span>
                    (<span id="selected-count">0</span> )
                </label>
                <button id="download-selected"
                    class="inline-flex items-center justify-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-white hover:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition">
                    Download
                </button>
            </div>

            <div class="grid gap-2" style="grid-template-columns: repeat(auto-fill, minmax(8rem, 1fr));">
                
                <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $subfolders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $subfolder): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="relative w-32 h-32 bg-white rounded shadow border hover:bg-orange-100 text-center text-xs font-medium">
                        
                        
                        <a href="<?php echo e(route('download-folder', ['path' => $subfolder])); ?>"
                        class="absolute top-2 right-2 bg-white p-1 shadow hover:bg-gray-200 z-20"
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

                        <a href="?user=<?php echo e($selectedUser->id); ?>&folder=<?php echo e($selectedFolder); ?>&subfolder=<?php echo e($subfolder); ?>"
                        class="absolute inset-0 flex flex-col items-center justify-center px-2">
                            📁
                            <div class="mt-1 truncate px-1 w-full" title="<?php echo e(basename($subfolder)); ?>">
                                <?php echo e(Str::limit(basename($subfolder), 10)); ?>

                            </div>
                        </a>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->

                
                <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $images; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $image): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="relative w-32 h-32 rounded shadow overflow-hidden group">
                        <input type="checkbox" class="absolute top-1 left-1 z-50 image-checkbox" value="<?php echo e(asset('storage/' . $image)); ?>">
                        <a href="<?php echo e(asset('storage/' . $image)); ?>" target="_blank"
                        class="relative w-32 h-32 rounded shadow overflow-hidden group">
                            <img src="<?php echo e(asset('storage/' . $image)); ?>"
                                class="w-full h-full object-cover" alt="Image">
                        </a>

                        <a href="<?php echo e(asset('storage/' . $image)); ?>" download
                        class="absolute bottom-2 right-2 z-50 bg-white p-1 rounded-full shadow hover:bg-gray-100 transition"
                        title="Download Image">
                            <?php echo e(svg('heroicon-o-arrow-down-tray', 'w-5 h-5 text-gray-700')); ?>
                        </a>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->

                <!--[if BLOCK]><![endif]--><?php if($total > $perPage): ?>
                    <div class="mt-4 flex justify-center space-x-2">
                        
                        <!--[if BLOCK]><![endif]--><?php if($page > 1): ?>
                            <a href="<?php echo e(request()->fullUrlWithQuery(['page' => $page - 1])); ?>"
                            class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">Previous</a>
                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                        
                        <!--[if BLOCK]><![endif]--><?php for($i = 1; $i <= ceil($total / $perPage); $i++): ?>
                            <a href="<?php echo e(request()->fullUrlWithQuery(['page' => $i])); ?>"
                            class="px-3 py-1 rounded <?php echo e($i == $page ? 'bg-blue-500 text-white' : 'bg-gray-200 hover:bg-gray-300'); ?>">
                                <?php echo e($i); ?>

                            </a>
                        <?php endfor; ?><!--[if ENDBLOCK]><![endif]-->

                        
                        <!--[if BLOCK]><![endif]--><?php if($page < ceil($total / $perPage)): ?>
                            <a href="<?php echo e(request()->fullUrlWithQuery(['page' => $page + 1])); ?>"
                            class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">Next</a>
                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                    </div>
                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
            </div>

        
        <?php elseif($selectedSubfolder): ?>
            <h2 class="text-xl font-bold mb-4">Content in <?php echo e(basename($selectedSubfolder)); ?></h2>

            
            <div class="flex items-center justify-between mb-2">
                <label class="flex items-center space-x-2">
                    <input type="checkbox" id="select-all-subfolder" class="form-checkbox">
                    <span class="text-sm font-medium text-gray-700">Select All</span>
                    (<span id="selected-count-subfolder">0</span> )
                </label>
                <button id="download-selected-subfolder"
                    class="inline-flex items-center justify-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-white hover:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition">
                    Download
                </button>
            </div>

            
            <div class="grid gap-2" style="grid-template-columns: repeat(auto-fill, minmax(8rem, 1fr));">
                
                <!--[if BLOCK]><![endif]--><?php if(!empty($subfolders)): ?>
                    <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $subfolders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sf): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="relative w-32 h-32 bg-white rounded shadow border hover:bg-orange-100 text-center text-xs font-medium">
                            
                            <a href="<?php echo e(route('download-folder')); ?>?path=<?php echo e(urlencode($sf)); ?>"
                                class="absolute top-2 right-2 bg-white p-1 rounded-full shadow hover:bg-gray-200 z-20"
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

                            <a href="?user=<?php echo e($selectedUser->id); ?>&folder=<?php echo e($selectedFolder); ?>&subfolder=<?php echo e($sf); ?>"
                                class="absolute inset-0 flex flex-col items-center justify-center px-2">
                                📁
                                <div class="mt-1 truncate px-1 w-full" title="<?php echo e(basename($sf)); ?>">
                                    <?php echo e(Str::limit(basename($sf), 10)); ?>

                                </div>
                            </a>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                
                <!--[if BLOCK]><![endif]--><?php $__empty_1 = true; $__currentLoopData = $images; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $image): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <div class="relative w-32 h-32 rounded shadow overflow-hidden group">
                        <input type="checkbox" class="absolute top-1 left-1 z-10 image-checkbox-subfolder" value="<?php echo e(asset('storage/' . $image)); ?>">
                        <a href="<?php echo e(asset('storage/' . $image)); ?>" target="_blank"
                            class="relative w-32 h-32 rounded shadow overflow-hidden group">
                            <img src="<?php echo e(asset('storage/' . $image)); ?>" class="w-full h-full object-cover" alt="Image">
                        </a>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                <!--[if BLOCK]><![endif]--><?php if($total > $perPage): ?>
                    <div class="mt-4 flex justify-center space-x-2">
                        
                        <!--[if BLOCK]><![endif]--><?php if($page > 1): ?>
                            <a href="<?php echo e(request()->fullUrlWithQuery(['page' => $page - 1])); ?>"
                            class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">Previous</a>
                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                        
                        <!--[if BLOCK]><![endif]--><?php for($i = 1; $i <= ceil($total / $perPage); $i++): ?>
                            <a href="<?php echo e(request()->fullUrlWithQuery(['page' => $i])); ?>"
                            class="px-3 py-1 rounded <?php echo e($i == $page ? 'bg-blue-500 text-white' : 'bg-gray-200 hover:bg-gray-300'); ?>">
                                <?php echo e($i); ?>

                            </a>
                        <?php endfor; ?><!--[if ENDBLOCK]><![endif]-->

                        
                        <!--[if BLOCK]><![endif]--><?php if($page < ceil($total / $perPage)): ?>
                            <a href="<?php echo e(request()->fullUrlWithQuery(['page' => $page + 1])); ?>"
                            class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">Next</a>
                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                    </div>
                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
            </div>
        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
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
document.addEventListener('DOMContentLoaded', function () {
    const updateCount = (checkboxSelector, countElementId) => {
        const count = document.querySelectorAll(`${checkboxSelector}:checked`).length;
        document.getElementById(countElementId).textContent = `${count} selected`;
    };

    // Folder
    const folderCheckboxes = document.querySelectorAll('.image-checkbox');
    document.getElementById('select-all')?.addEventListener('change', function () {
        folderCheckboxes.forEach(cb => cb.checked = this.checked);
        updateCount('.image-checkbox', 'selected-count');
    });
    folderCheckboxes.forEach(cb => {
        cb.addEventListener('change', () => updateCount('.image-checkbox', 'selected-count'));
    });

    // Subfolder
    const subfolderCheckboxes = document.querySelectorAll('.image-checkbox-subfolder');
    document.getElementById('select-all-subfolder')?.addEventListener('change', function () {
        subfolderCheckboxes.forEach(cb => cb.checked = this.checked);
        updateCount('.image-checkbox-subfolder', 'selected-count-subfolder');
    });
    subfolderCheckboxes.forEach(cb => {
        cb.addEventListener('change', () => updateCount('.image-checkbox-subfolder', 'selected-count-subfolder'));
    });

    // Download logic (unchanged)
    document.getElementById('download-selected')?.addEventListener('click', function () {
        const selected = [...document.querySelectorAll('.image-checkbox:checked')].map(cb => cb.value);
        if (selected.length === 0) return alert('Please select at least one image to download.');
        selected.forEach(url => {
            const a = document.createElement('a');
            a.href = url;
            a.download = '';
            a.style.display = 'none';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        });
    });

    document.getElementById('download-selected-subfolder')?.addEventListener('click', function () {
        const selected = [...document.querySelectorAll('.image-checkbox-subfolder:checked')].map(cb => cb.value);
        if (selected.length === 0) return alert('Please select at least one image to download.');
        selected.forEach(url => {
            const a = document.createElement('a');
            a.href = url;
            a.download = '';
            a.style.display = 'none';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        });
    });
});
</script><?php /**PATH C:\xampp\htdocs\scanner-app\resources\views/filament/admin/pages/admin-users-page.blade.php ENDPATH**/ ?>