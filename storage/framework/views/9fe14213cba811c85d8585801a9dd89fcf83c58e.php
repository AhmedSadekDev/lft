<div class="row px-4 py-4 mt-4 mb-4 containers-div container-item" id="container_<?php echo e($index); ?>">
    <div class="row align-items-center justify-content-between col-xl-12">
        <div class="col-xl-6">
            <div class="form-group">
                <?php echo Form::label("containers[$index][branch_id]", __('admin.branch')); ?>

                <?php echo Form::select(
                    "containers[$index][branch_id]",
                    ['to_be_disabled' => __('admin.select')] + $branches->toArray(),
                    old("containers.$index.branch_id"),
                    ['class' => 'form-control', 'required'],
                ); ?>

            </div>
        </div>

        <div class="col-xl-2 delete-div">
            <button type="button" class="btn btn-danger delete-container">X</button>
        </div>
    </div>

    <div class="row col-xl-12">
        <div class="col-xl-6">
            <div class="form-group">
                <?php echo Form::label("containers[$index][container_id]", __('admin.container')); ?>

                <?php echo Form::select(
                    "containers[$index][container_id]",
                    ['to_be_disabled' => __('admin.select')] + $containers_type->toArray(),
                    old("containers.$index.container_id"),
                    ['class' => 'form-control', 'required'],
                ); ?>

            </div>
        </div>
        <div class="col-xl-6">
            <div class="form-group">
                <?php echo Form::label("containers[$index][arrival_date]", __('admin.arrival_date')); ?>

                <?php echo Form::date(
                    "containers[$index][arrival_date]",
                    old("containers.$index.arrival_date"),
                    ['class' => 'form-control', 'required'],
                ); ?>

            </div>
        </div>
    </div>

    <div class="row col-xl-12 border-bottom mx-0 mb-4">
        <div class="col-xl-12">
            <div class="form-group">
                <?php echo Form::label("containers[$index][containers_count]", __('admin.containers_count')); ?>

                <?php echo Form::number(
                    "containers[$index][containers_count]",
                    old("containers.$index.containers_count", 1),
                    ['class' => 'form-control', 'required', 'min' => 1]
                ); ?>

            </div>
        </div>
    </div>
</div>
<?php /**PATH D:\laragon\www\leader\leader\resources\views\admin\bookings\container.blade.php ENDPATH**/ ?>