<?php
use App\Models\BookingContainer;
?>


<?php $__env->startSection('css'); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="container">
        <?php echo $__env->make('layouts.includes.breadcrumb', ['page' => __('main.car_shipments')], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        <!--begin::Card-->
        <div class="card card-custom">
            <div class="card-header flex-wrap align-items-center py-5">
                <div class="card-toolbar">
                    <div class="">
                        <!--begin::Button-->
                        
                    </div>
                </div>

                <div class="">
                    <!-- Button trigger modal -->
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#exampleModal">
                        <?php echo e(__('admin.filter')); ?>

                    </button>
                    <button type="button" class="btn btn-secondary" data-toggle="modal" data-target="#filterModalExport">
                        <?php echo e(__('admin.export_shipments')); ?>

                    </button>
                    <a href="<?php echo e(route('accounts.car.payment', $car->id)); ?>" class="btn btn-success">
                        <i class="fas fa-money-bill-wave"></i> سداد
                    </a>

                    <!-- Modal -->
                    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                        aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="exampleModalLabel"><?php echo e(__('admin.filter')); ?></h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        X
                                    </button>
                                </div>
                                <form action="<?php echo e(route('shipments.index', $car->id)); ?>" method="get">
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="dateFrom"><?php echo e(__('admin.from')); ?></label>
                                                    <input id="dateFrom" class="form-control" type="date"
                                                        name="date_from">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="dateTo"><?php echo e(__('admin.to')); ?></label>
                                                    <input id="dateTo" class="form-control" type="date"
                                                        name="date_to">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-primary"><?php echo e(__('admin.filter')); ?></button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="modal fade" id="filterModalExport" tabindex="-1" aria-labelledby="exampleModalLabel"
                        aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="exampleModalLabel"><?php echo e(__('admin.export_shipments')); ?></h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        X
                                    </button>
                                </div>
                                <form action="<?php echo e(route('shipments.export')); ?>" method="get">
                                    <input type="hidden" name="car_id" value="<?php echo e($car->id); ?>" />
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="dateFrom"><?php echo e(__('admin.from')); ?></label>
                                                    <input id="dateFrom" class="form-control" type="date"
                                                        name="from">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="dateTo"><?php echo e(__('admin.to')); ?></label>
                                                    <input id="dateTo" class="form-control" type="date"
                                                        name="to">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-primary"><?php echo e(__('admin.export')); ?></button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Edit Payment -->
                    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel"
                        aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="editModalLabel"><?php echo e(__('Pay')); ?></h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        X
                                    </button>
                                </div>
                                <form id="editForm" method="POST" enctype="multipart/form-data">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('PUT'); ?>
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="cost"><?php echo e(__('admin.cost')); ?></label>
                                                    <input id="cost" class="form-control" type="number"
                                                        name="cost">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary"
                                            data-dismiss="modal"><?php echo e(__('admin.close')); ?></button>
                                        <button type="submit" class="btn btn-primary"><?php echo e(__('admin.save')); ?></button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <div class="card-body">
                <!-- عرض رقم السيارة فوق الجدول -->
                <div class="mb-4 text-center">
                    <h4><?php echo e(__('admin.car_number')); ?>: <span class="text-primary"><?php echo e($car->car_number); ?></span></h4>
                </div>
                <table class="table table-responsive-xl" id="table">
                    <thead class="thead-light">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col"><?php echo e(__('admin.container_no')); ?></th>
                            <th scope="col">تاريخ النقلة</th>
                            <th scope="col"><?php echo e(__('admin.costing')); ?></th>
                            <th scope="col"><?php echo e(__('admin.financial_custody')); ?></th>
                            <th scope="col"><?php echo e(__('main.extra_expense')); ?></th>
                            <th scope="col"><?php echo e(__('the_rest')); ?></th>
                            <th scope="col"><?php echo e(__('admin.departure')); ?></th>
                            <th scope="col"><?php echo e(__('admin.loading')); ?></th>
                            <th scope="col"><?php echo e(__('admin.aging')); ?></th>
                            <th scope="col"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $shipments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $shipment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                $bookingContainerIds = $shipment->booking_containers
                                    ->pluck('id')
                                    ->sort()
                                    ->values()
                                    ->toJson();
                                $containerNumbers = $shipment->booking_containers
                                    ? implode(', ', $shipment->booking_containers->pluck('container_no')->toArray() ?? [])
                                    : '';
                                $cost = $shipment->cost ?? 0;
                                $financialCustody = $shipment->money_transfer->value ?? 0;
                                $extraExpenses = $shipment->extraExpenses->sum('value') ?? 0;
                                $payments = $shipment->payingCars->sum('value') ?? 0;

                                // حساب المتبقي:
                                // إذا كان هناك cost: المتبقي = cost - العهدة + المصروفات الإضافية - المدفوعات
                                // إذا لم يكن هناك cost: المتبقي = المصروفات الإضافية + المدفوعات - العهدة (لأن العهدة دين على السيارة)
                                $remain = $cost
                                    ? $cost - $financialCustody + $extraExpenses - $payments
                                    : $extraExpenses + $payments - $financialCustody;
                                $booking_id = $containerNumbers ? BookingContainer::where('container_no', $containerNumbers)->first() ? BookingContainer::where('container_no', $containerNumbers)->first()->booking_id : "" : "";
                            ?>

                            <tr>
                                <th scope="row"><?php echo e($shipment->id); ?></th>
                                <td><a href="<?php echo e(route('bookings.show', $booking_id ?? 1)); ?>"><?php echo e($containerNumbers); ?></a></td>
                                <td><?php echo e($shipment->date ?? ''); ?></td>
                                <td><?php echo e($cost); ?></td>
                                <td>
                                    <?php if($shipment->booking_containers->first() && $shipment->booking_containers->first()->id): ?>
                                        <a target="_blank"
                                           href="<?php echo e(route('bookings.booking_container_policies', $shipment->booking_containers->first()->id)); ?>">
                                            <?php echo e($financialCustody); ?>

                                        </a>
                                    <?php else: ?>
                                        0
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($shipment->booking_containers->first() && $shipment->booking_containers->first()->id): ?>
                                        <a target="_blank"
                                           href="<?php echo e(route('booking_contrainer_extra_costs', $shipment->booking_containers->first()->id)); ?>">
                                            <?php echo e($extraExpenses); ?>

                                        </a>
                                    <?php else: ?>
                                        0
                                    <?php endif; ?>
                                </td>
                                <td><?php echo e($remain); ?></td>
                                <td><?php echo e($shipment->booking_containers->first()->departure->title ?? ''); ?></td>
                                <td><?php echo e($shipment->booking_containers->first()->loading->title ?? ''); ?></td>
                                <td><?php echo e($shipment->booking_containers->first()->aging->title ?? ''); ?></td>
                                <td>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <button data-toggle="modal" data-target="#editModal"
                                                    data-url="<?php echo e(route('shipments.update', $shipment->id)); ?>"
                                                    data-cost="<?php echo e($shipment->cost); ?>" data-id="<?php echo e($shipment->id); ?>"
                                                    class="btn btn-icon btn-light btn-hover-secondary btn-sm mx-3 edit-btn">
                                                <?php echo e(__('admin.edit')); ?>

                                            </button>
                                        </div>
                                        <div class="col-md-3">
                                            <button class="btn btn-icon btn-light btn-hover-danger btn-sm mx-3 delete"
                                                    onclick="Delete('<?php echo e($shipment->id); ?>')">
                                                <i class="fas fa-trash text-danger"></i>
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        </div>
        <!--end::Card-->
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('js'); ?>
    <script>
        $(document).on('click', '.edit-btn', function() {
            $('#cost').val($(this).data('cost'));
            $('#editForm').attr('action', $(this).data('url'));
        });
    </script>

    <script>
        function Delete(id) {
            Swal.fire({
                title: "<?php echo e(__('alerts.are_you_sure')); ?>",
                text: "<?php echo e(__('alerts.not_revert_information')); ?>",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: "<?php echo e(__('alerts.confirm')); ?>",
                cancelButtonText: "<?php echo e(__('alerts.cancel')); ?>",
            }).then((result) => {
                if (result.isConfirmed) {
                    var url = '<?php echo e(route('shipments.destroy', ':id')); ?>';
                    url = url.replace(':id', id);
                    var token = '<?php echo e(csrf_token()); ?>';
                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': token,
                            'X-Requested-With': 'XMLHttpRequest',
                        }
                    });
                    $.ajax({
                        url: url,
                        type: 'delete',
                        success: function(response, textStatus, xhr) {
                            if (xhr.status == 200) {
                                Swal.fire({
                                    title: "<?php echo e(__('alerts.done')); ?>",
                                    icon: 'success',
                                    showConfirmButton: false,
                                    timer: 3000,
                                    timerProgressBar: true,
                                });
                                location.reload();
                            }
                        }
                    });
                }
            });
        }
    </script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home3/cloudtal/cloudymenue.cloudy-digital.com/resources/views/admin/shipments/index.blade.php ENDPATH**/ ?>