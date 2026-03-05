
<script>
(function() {
    'use strict';
    
    // Simple mobile detection for body class (CSS hooks only)
    function updateMobileClass() {
        if (window.innerWidth < 768) {
            document.body.classList.add('is-mobile-view');
        } else {
            document.body.classList.remove('is-mobile-view');
        }
    }
    
    // Run on load and resize
    document.addEventListener('DOMContentLoaded', updateMobileClass);
    window.addEventListener('resize', updateMobileClass);
    
    // Run immediately
    if (document.readyState !== 'loading') {
        updateMobileClass();
    }
})();
</script>
<?php /**PATH /var/www/resources/views/filament/hooks/responsive-navigation.blade.php ENDPATH**/ ?>