
<?php $__env->startSection("content"); ?>
    <!--begin::Card-->
    <div class="card card-custom gutter-b">
        <div class="card-header">
            <div class="card-title">
                <?php echo e(__('admin.edit_container_information')); ?>

            </div>
        </div>
        
        
            <?php echo Form::model($container, ['url' => route('booking_containers_agents.update', $container->id), 'method' => 'POST', 'enctype' => 'multipart/form-data', 'files' => true]); ?>

                <input type="hidden" name="type_id" value="<?php echo e($stageType); ?>">
                <div class="card-body">
                    <div class="mb-4">
                        <?php $__currentLoopData = [0 => 'التخصيص', 1 => 'التحميل', 2 => 'التعتيق']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <a class="btn <?php echo e($stageType === $type ? 'btn-primary' : 'btn-light'); ?>" href="<?php echo e(request()->url()); ?>?type_id=<?php echo e($type); ?>"><?php echo e($label); ?></a>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                    <div class="row">
                        <!-- For loop this div -->
                        <div class="col-sm-12">
                            <div class="form-group">
                               <lable>#</lable>
                               <input readonly value="<?php echo e($container->id); ?>" class="form-control">
                            </div>
                        </div>
                        <!-- For loop this div -->
                        
                        
                        <?php $__currentLoopData = $agents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $agent): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="col-sm-4 mb-3">
                                <div class="form-group">
                                    <label for="agent<?php echo e($agent->id); ?>"><?php echo e($agent->name); ?></label>
                                    <input id="agent<?php echo e($agent->id); ?>" type="checkbox" name="agents[]" value="<?php echo e($agent->id); ?>" <?php if(in_array($agent->id, $container->agents->pluck('id')->toArray())): ?> checked <?php endif; ?>>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

        
                    </div>
                </div>
        
                <div class="card-footer">
                    
                    <?php echo Form::submit(__('admin.update'), ["class"=>"btn btn-primary"]); ?>

                </div>
        
        </form>
        <?php echo Form::close(); ?>

<!-- /.card-body -->


        
        
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make("layouts.admin", \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\laragon\www\leader\leader\resources\views\admin\bookingsagents\edit.blade.php ENDPATH**/ ?>