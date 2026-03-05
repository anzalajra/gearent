<?php $__env->startSection('title', 'Home'); ?>

<?php $__env->startSection('content'); ?>
<!-- Hero Section -->
<section class="bg-gradient-to-r from-primary-600 to-primary-800 text-white py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h1 class="text-4xl md:text-5xl font-bold mb-6">Rent Professional Equipment</h1>
            <p class="text-xl mb-8 text-primary-100">High quality cameras, lenses, and accessories for your project</p>
            <a href="<?php echo e(route('catalog.index')); ?>" class="bg-white text-primary-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition">
                Browse Catalog
            </a>
        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-bold mb-8 text-center">Browse by Category</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e(route('catalog.index', ['category' => $category->id])); ?>" class="bg-white rounded-lg shadow p-6 text-center hover:shadow-lg transition">
                    <div class="text-4xl mb-2">📷</div>
                    <h3 class="font-semibold"><?php echo e($category->name); ?></h3>
                    <p class="text-sm text-gray-500"><?php echo e($category->products_count); ?> products</p>
                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>
</section>

<!-- Featured Products -->
<section class="py-16 bg-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-bold mb-8 text-center">Featured Products</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $featuredProducts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="bg-white rounded-lg shadow overflow-hidden hover:shadow-lg transition">
                    <div class="h-48 bg-gray-200 flex items-center justify-center">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($product->image): ?>
                            <img src="<?php echo e(Storage::url($product->image)); ?>" alt="<?php echo e($product->name); ?>" class="h-full w-full object-cover">
                        <?php else: ?>
                            <span class="text-6xl">📷</span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                    <div class="p-4">
                        <p class="text-xs text-primary-600 mb-1"><?php echo e($product->category->name); ?></p>
                        <h3 class="font-semibold mb-2"><?php echo e($product->name); ?></h3>
                        <p class="text-primary-600 font-bold">Rp <?php echo e(number_format($product->daily_rate, 0, ',', '.')); ?>/day</p>
                        <a href="<?php echo e(route('catalog.show', $product)); ?>" class="mt-3 block text-center bg-primary-600 text-white py-2 rounded hover:bg-primary-700 transition">
                            View Details
                        </a>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
        <div class="text-center mt-8">
            <a href="<?php echo e(route('catalog.index')); ?>" class="text-primary-600 font-semibold hover:underline">View All Products →</a>
        </div>
    </div>
</section>

<!-- How It Works -->
<section class="py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-bold mb-12 text-center">How It Works</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div class="text-center">
                <div class="w-16 h-16 bg-primary-100 text-primary-600 rounded-full flex items-center justify-center text-2xl mx-auto mb-4">1</div>
                <h3 class="font-semibold mb-2">Browse & Select</h3>
                <p class="text-gray-600 text-sm">Choose from our wide range of equipment</p>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 bg-primary-100 text-primary-600 rounded-full flex items-center justify-center text-2xl mx-auto mb-4">2</div>
                <h3 class="font-semibold mb-2">Book Online</h3>
                <p class="text-gray-600 text-sm">Select your dates and complete booking</p>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 bg-primary-100 text-primary-600 rounded-full flex items-center justify-center text-2xl mx-auto mb-4">3</div>
                <h3 class="font-semibold mb-2">Pick Up</h3>
                <p class="text-gray-600 text-sm">Collect your equipment at our location</p>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 bg-primary-100 text-primary-600 rounded-full flex items-center justify-center text-2xl mx-auto mb-4">4</div>
                <h3 class="font-semibold mb-2">Return</h3>
                <p class="text-gray-600 text-sm">Return equipment when you're done</p>
            </div>
        </div>
    </div>
</section>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.frontend', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/frontend/home.blade.php ENDPATH**/ ?>