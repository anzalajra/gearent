<div class="flex flex-col items-center justify-center py-4 text-xs text-gray-500">
    <div>
        <?php echo e(config('app.name')); ?> <?php echo e(\App\Helpers\SystemVersion::getName()); ?>

    </div>
    <div class="text-[10px] text-gray-400">
        <?php echo e(\App\Helpers\SystemVersion::getReleaseDate()); ?>

    </div>
</div>
<?php /**PATH /var/www/resources/views/filament/hooks/footer.blade.php ENDPATH**/ ?>