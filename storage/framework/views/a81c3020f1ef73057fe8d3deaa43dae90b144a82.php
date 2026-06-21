
<?php $__env->startSection("content"); ?>
    <!--begin::Card-->
    <div class="card card-custom gutter-b">
        <div class="card-header">
            <div class="card-title">
                <?php echo e(__('admin.add_financial_custody_agent')); ?>

            </div>
        </div>
        <?php echo $__env->make('admin.financial_custody_agents.form', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make("layouts.admin", \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home3/cloudtal/cloudymenue.cloudy-digital.com/resources/views/admin/financial_custody_agents/create.blade.php ENDPATH**/ ?>