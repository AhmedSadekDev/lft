<?php echo $__env->make("layouts.includes.header", \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<?php echo $__env->make("layouts.includes.sidebar", \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<?php echo $__env->make("layouts.includes.footer", \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

<!--begin::Page Scripts(used by this page)-->
<script src="<?php echo e(asset('assets/js/pages/widgets.js')); ?>"></script>
<script src="<?php echo e(asset('assets/js/pages/waypoints.min.js')); ?>"></script>
<script src="<?php echo e(asset('assets/js/pages/counterup.min.js')); ?>"></script>

<script>
    $('.counter').counterUp({
        delay: 10,
        time: 1500
    });
</script>
<?php /**PATH /home3/cloudtal/cloudymenue.cloudy-digital.com/resources/views/layouts/admin.blade.php ENDPATH**/ ?>