
<?php $__env->startSection("content"); ?>
    <!--begin::Card-->
    <div class="card card-custom gutter-b">
        <div class="card-header">
            <div class="card-title">
                الملاحظات
            </div>
        </div>

        <div class="card-body">
            <div class="row">
                <?php $__currentLoopData = $notes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $paper): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="col-md-3">
                        <div class="card card-custom gutter-b">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12 position-relative">
                                        <!-- Images -->
                                        <div class="image-gallery mb-3">
                                            <?php $__currentLoopData = $paper->images; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $image): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <img src="<?php echo e($image->image); ?>" class="img-fluid mb-2" alt="image">
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </div>

                                        <!-- Note Content -->
                                        <div class="note-content mb-3">
                                            <h6><strong>الملاحظة:</strong></h6>
                                            <p><?php echo e($paper->notes); ?></p>
                                        </div>

                                        <!-- Note Owner -->
                                        <div class="note-owner mb-3">
                                            <h6><strong>صاحب الملاحظة:</strong></h6>
                                            <p><?php echo e($paper->attacher->name); ?></p>
                                        </div>

                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make("layouts.admin", \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home3/cloudtal/cloudymenue.cloudy-digital.com/resources/views/admin/bookings/notes.blade.php ENDPATH**/ ?>