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
    <div class="space-y-6">

        
        <div class="sticky top-0 z-50 backdrop-blur-md bg-white/70 dark:bg-gray-900/60 
                    border-b border-gray-200 dark:border-gray-700 px-4 py-4 rounded-b-xl shadow-sm">

            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">

                <div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                        Google Excel Sheet
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Live editable spreadsheet synced directly with your dashboard.
                    </p>
                </div>

                
                <div class="flex flex-wrap gap-3">

                    <a href="https://docs.google.com/spreadsheets/d/1MCDA9I-jeJtzITvvNcbE4Aj_BK8wAs9HCFr5s5s_Z_U/export?format=xlsx"
                       target="_blank"
                       class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium 
                              rounded-lg shadow-lg transition-all active:scale-95">
                        Download Excel
                    </a>

                    <a href="https://docs.google.com/spreadsheets/d/1MCDA9I-jeJtzITvvNcbE4Aj_BK8wAs9HCFr5s5s_Z_U/export?format=pdf"
                       target="_blank"
                       class="px-4 py-2 bg-primary-600 hover:bg-gray-700 text-white font-medium 
                              rounded-lg shadow-lg transition-all active:scale-95">
                        PDF
                    </a>
                </div>

            </div>
        </div>

        
        <div class="rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 
                    bg-white dark:bg-gray-900 overflow-hidden">

            
            <div class="rounded-lg overflow-hidden">
                <iframe 
                    src="https://docs.google.com/spreadsheets/d/1MCDA9I-jeJtzITvvNcbE4Aj_BK8wAs9HCFr5s5s_Z_U/edit?usp=sharing"
                    width="100%"
                    height="900"
                    class="rounded-lg"
                    style="border:0;">
                </iframe>
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
<?php /**PATH C:\xampp\htdocs\ScanVault_backend-main\resources\views/filament/admin/pages/google-sheet-viewer.blade.php ENDPATH**/ ?>