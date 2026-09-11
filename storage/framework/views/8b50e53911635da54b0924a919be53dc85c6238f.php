
<?php $__env->startSection('content'); ?>
    <div class="container">
        <?php echo $__env->make('layouts.includes.breadcrumb', ['page' => __('main.permissions')], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        <!--begin::Card-->
        <div class="card card-custom">
            <div class="card-header flex-wrap py-5">
                <div class="card-toolbar">
                    <!--begin::Button-->

                    <a href="<?php echo e(route('permissions.index')); ?>" class="btn btn-primary font-weight-bolder">
                        <span class="svg-icon svg-icon-md">
                            <!--begin::Svg Icon | path:assets/media/svg/icons/Design/Flatten.svg-->
                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px"
                                height="24px" viewBox="0 0 24 24" version="1.1">
                                <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                    <rect x="0" y="0" width="24" height="24" />
                                    <circle fill="#000000" cx="9" cy="15" r="6" />
                                    <path
                                        d="M8.8012943,7.00241953 C9.83837775,5.20768121 11.7781543,4 14,4 C17.3137085,4 20,6.6862915 20,10 C20,12.2218457 18.7923188,14.1616223 16.9975805,15.1987057 C16.9991904,15.1326658 17,15.0664274 17,15 C17,10.581722 13.418278,7 9,7 C8.93357256,7 8.86733422,7.00080962 8.8012943,7.00241953 Z"
                                        fill="#000000" opacity="0.3" />
                                </g>
                            </svg>
                            <!--end::Svg Icon-->
                        </span><?php echo e(__('main.back')); ?>

                    </a>

                    <!--end::Button-->
                </div>
                <div class="mt-3">
                    <a href="<?php echo e(route('permissions.index')); ?>" class="btn btn-secondary float-right">
                        <?php echo e(__('main.back')); ?>

                    </a>
                </div>
            </div>
            <div class="card-body">
                <form action="<?php echo e(route('permissions.update', $role->id)); ?>" method="post">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PUT'); ?>
                    <div class="form-group">
                        <input class="form-control" type="text" name="name" placeholder="<?php echo e(__('admin.name')); ?>"
                            value="<?php echo e(old('name') ?? $role->name); ?>" required>
                        <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <div class="text-danger"><?php echo e($message); ?></div>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <lable class="mb-2"><?php echo e(__('main.permissions')); ?>:</lable>
                    <div class="row">

                        <?php $__currentLoopData = $permissions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $permission): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="form-check col-3 mb-3">
                                <input id="permission<?php echo e($permission->id); ?>" class="form-check-input" type="checkbox"
                                    name="permissions[]" value="<?php echo e($permission->id); ?>"
                                    <?php if($role->permissions->contains('id', $permission->id)): ?> checked <?php endif; ?>>
                                <label for="permission<?php echo e($permission->id); ?>"
                                    class="form-check-label"><?php echo e(__('admin.' . $permission->name)); ?></label>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>


                    <div class="d-flex justify-content-end my-3">
                        <button type="submit" class="btn btn-primary"><?php echo e(__('admin.save')); ?></button>
                    </div>
                </form>
            </div>
        </div>
        <!--end::Card-->
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home3/cloudtal/cloudymenue.cloudy-digital.com/resources/views/admin/permissions/edit.blade.php ENDPATH**/ ?>