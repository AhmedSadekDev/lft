<?php $__env->startSection('content'); ?>
    <div class="card card-custom gutter-b">
        <div class="card-header">
            <div class="card-title">
                <?php echo e(__('admin.add_new_booking')); ?>

            </div>
            <div class="mt-3">
                <a href="<?php echo e(route('bookings.index')); ?>" class="btn btn-secondary float-right">
                    <?php echo e(__('main.back')); ?>

                </a>
            </div>
        </div>
        
        
        <?php echo $__env->make('admin.bookings.form', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('js'); ?>
<script>
    $(document).ready(function () {
        /**
         * Handle factory change event to dynamically fetch and update branches dropdown
         */
        $(document).on('change', '#factory_id', function () {
            let factoryId = $(this).val();
            let url = '<?php echo e(route("factory.branches", ":id")); ?>'.replace(':id', factoryId);
            
            $.ajax({
                url: url,
                type: 'GET',
                success: function (response) {
                    if (response.status) {
                        let options = '<option value=""><?php echo e(__('admin.select')); ?></option>';
                        $.each(response.data, function (id, name) {
                            options += `<option value="${id}">${name}</option>`;
                        });
                        $('#branch_id').html(options);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Error fetching branches:', error);
                }
            });
        });

        /**
         * Load employees dynamically based on selected company
         */
        var company_employees = <?php echo json_encode($company_employees); ?>;
        $('#company_id').on('change', function () {
            var companyId = $(this).val();
            $('#employee_id').empty().append('<option value=""><?php echo e(__('admin.select')); ?></option>');
            if (company_employees[companyId]) {
                $.each(company_employees[companyId], function (id, name) {
                    $('#employee_id').append(`<option value="${id}">${name}</option>`);
                });
            }
        });

    
        /**
         * Set the minimum date for date inputs (prevents selecting past dates)
         */
        let today = new Date().toISOString().split('T')[0];
        $('#permit_end_date_input, #discharge_date_input, .arrival_date_input').attr('min', today);
    });
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home3/cloudtal/cloudymenue.cloudy-digital.com/resources/views/admin/bookings/create.blade.php ENDPATH**/ ?>