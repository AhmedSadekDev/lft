
<?php $__env->startSection('content'); ?>
    <div class="container-fluid invoice-create-page">
        <?php echo $__env->make('layouts.includes.breadcrumb', ['page' => __('admin.add_new_invoice')], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        <?php echo $__env->make('admin.components.booking-invoices.listings', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        <?php echo $__env->make('admin.components.booking-invoices.totals-form', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>
<style>
    .invoice-create-page {
        direction: rtl;
        background: #f5f7fa;
        padding-bottom: 2rem;
    }
    .invoice-create-page .card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    }
    .invoice-create-page .card-header {
        border-bottom: 1px solid #e4e6ef;
        background: #fff;
        border-radius: 12px 12px 0 0;
        padding: 1rem 1.5rem;
    }
    .invoice-create-page .card-body {
        padding: 1.5rem;
    }
</style>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home3/cloudtal/cloudymenue.cloudy-digital.com/resources/views/admin/bookings/booking-invoices/create.blade.php ENDPATH**/ ?>