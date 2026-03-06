<div class="flex items-center space-x-3">
    <button
        type="button"
        role="switch"
        aria-checked="<?php echo e($isOn ? 'true' : 'false'); ?>"
        wire:click="mountAction('<?php echo e($action->getName()); ?>')"
        wire:loading.attr="disabled"
        wire:target="mountAction('<?php echo e($action->getName()); ?>')"
        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-600 focus:ring-offset-2 <?php echo e($isOn ? 'bg-primary-600' : 'bg-gray-200 dark:bg-gray-700'); ?>"
    >
        <span
            aria-hidden="true"
            class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out <?php echo e($isOn ? 'translate-x-5' : 'translate-x-0'); ?>"
        ></span>
    </button>
    <span class="text-sm font-medium text-gray-900 dark:text-gray-100"><?php echo e($label); ?></span>
</div>
<?php /**PATH /var/www/resources/views/filament/actions/header-toggle.blade.php ENDPATH**/ ?>