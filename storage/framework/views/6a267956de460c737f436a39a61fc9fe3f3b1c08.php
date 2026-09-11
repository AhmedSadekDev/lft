<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <?php if(isset($page) && !is_null($page)): ?>
            <li class="breadcrumb-item">
                <a href="<?php echo e(route('main')); ?>">
                    <?php echo e(__('main.home')); ?>

                </a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <?php echo e($page); ?>

            </li>
        <?php else: ?>
            <li class="breadcrumb-item active">
                <a href="<?php echo e(route('main')); ?>">
                    <?php echo e(__('main.home')); ?>

                </a>
            </li>
        <?php endif; ?>
    </ol>
</nav>


<?php /**PATH /home3/cloudtal/cloudymenue.cloudy-digital.com/resources/views/layouts/includes/breadcrumb.blade.php ENDPATH**/ ?>