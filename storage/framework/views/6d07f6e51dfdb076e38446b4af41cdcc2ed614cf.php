
<?php $__env->startSection('content'); ?>
    <style>
        #transportationsTable {
            direction: rtl;
        }
        #transportationsTable thead th {
            background-color: #f7f8fa;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
        }
        #transportationsTable tbody tr:hover {
            background-color: #f8f9fa;
            transition: background-color 0.2s;
        }
        .empty-state {
            padding: 40px 20px;
        }
        .empty-state i {
            opacity: 0.5;
        }
    </style>
    <div class="container">
        <?php echo $__env->make('layouts.includes.breadcrumb', ['page' => __('main.transportations')], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        <!--begin::Card-->
        <div class="card card-custom">
            <div class="card-header flex-wrap py-5">
                <div class="card-toolbar">
                    <!--begin::Button-->
                    <?php if(auth()->user()->hasPermissionTo('transportations.create')): ?>
                        <a href="<?php echo e($route_create); ?>" class="btn btn-primary font-weight-bolder">
                            <span class="svg-icon svg-icon-md">
                                <!--begin::Svg Icon | path:assets/media/svg/icons/Design/Flatten.svg-->
                                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                                    width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                                    <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                        <rect x="0" y="0" width="24" height="24" />
                                        <circle fill="#000000" cx="9" cy="15" r="6" />
                                        <path
                                            d="M8.8012943,7.00241953 C9.83837775,5.20768121 11.7781543,4 14,4 C17.3137085,4 20,6.6862915 20,10 C20,12.2218457 18.7923188,14.1616223 16.9975805,15.1987057 C16.9991904,15.1326658 17,15.0664274 17,15 C17,10.581722 13.418278,7 9,7 C8.93357256,7 8.86733422,7.00080962 8.8012943,7.00241953 Z"
                                            fill="#000000" opacity="0.3" />
                                    </g>
                                </svg>
                                <!--end::Svg Icon-->
                            </span><?php echo e(__('admin.add')); ?>

                        </a>
                    <?php endif; ?>

                    <div class="float-left ml-2">
                        <button type="button" class="btn btn-success " id="imports" data-toggle="modal"
                            data-target="#import_excels">
                            <i class="icon-share-alternitive"></i>
                            <?php echo e(__('admin.import')); ?>

                        </button>
                    </div>
                    <div class="float-left ml-2">
                        <a href="<?php echo e(route('companyTransportations.export', request()->all())); ?>" class="btn btn-info">
                            <i class="fas fa-file-excel"></i>
                            <?php echo e(__('admin.export')); ?>

                        </a>
                    </div>
                    <!--end::Button-->
                </div>
                <div class="mt-3">
                    <a href="<?php echo e(route('companies.index')); ?>" class="btn btn-secondary float-right">
                        <?php echo e(__('main.back')); ?>

                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped" id="transportationsTable">
                        <thead class="thead-light">
                            <tr>
                                <th scope="col" style="width: 50px;">#</th>
                                <th scope="col"><?php echo e(__('main.company')); ?></th>
                                <th scope="col"><?php echo e(__('main.container')); ?></th>
                                <th scope="col"><?php echo e(__('admin.departure_location')); ?></th>
                                <th scope="col"><?php echo e(__('admin.loading_location')); ?></th>
                                <th scope="col"><?php echo e(__('admin.aging_location')); ?></th>
                                <th scope="col" style="text-align: left;"><?php echo e(__('admin.price')); ?></th>
                                <th scope="col" style="width: 120px; text-align: center;"><?php echo e(__('admin.actions')); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $transportations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $transportation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <th scope="row" class="font-weight-bold">
                                        <?php echo e($transportation->id); ?>

                                    </th>
                                    <td>
                                        <span class="font-weight-bold text-primary">
                                            <?php echo e($transportation->company_name ?? __('main.not_found')); ?>

                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-info badge-pill">
                                            <?php echo e($transportation->container_type ?? __('main.not_found')); ?>

                                        </span>
                                    </td>
                                    <td>
                                        <i class="fas fa-map-marker-alt text-danger mr-1"></i>
                                        <?php echo e($transportation->Departure->title ?? __('main.not_found')); ?>

                                    </td>
                                    <td>
                                        <i class="fas fa-map-marker-alt text-success mr-1"></i>
                                        <?php echo e($transportation->Loading->title ?? __('main.not_found')); ?>

                                    </td>
                                    <td>
                                        <i class="fas fa-map-marker-alt text-warning mr-1"></i>
                                        <?php echo e($transportation->Aging->title ?? __('main.not_found')); ?>

                                    </td>
                                    <td>
                                        <span class="font-weight-bold text-success" style="font-size: 1.1rem;">
                                            <?php echo e(number_format($transportation->price ?? 0, 2)); ?> <?php echo e(__('main.currency')); ?>

                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-center align-items-center">
                                            <?php if(auth()->user()->hasPermissionTo('transportations.update')): ?>
                                                <a href="<?php echo e(route('companyTransportations.edit', ['companyTransportation' => $transportation->id, 'company_id' => isset(request()->company_id) && !is_null(request()->company_id) ? request()->company_id : null])); ?>"
                                                    class="btn btn-icon btn-light btn-hover-primary btn-sm mr-2"
                                                    title="<?php echo e(__('admin.edit')); ?>">
                                                    <i class="fas fa-edit text-primary"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if(auth()->user()->hasPermissionTo('transportations.delete')): ?>
                                                <button class="btn btn-icon btn-light btn-hover-danger btn-sm delete"
                                                    onclick="Delete('<?php echo e($transportation->id); ?>')"
                                                    title="<?php echo e(__('admin.delete')); ?>">
                                                    <i class="fas fa-trash text-danger"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5">
                                        <div class="empty-state">
                                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                            <h5 class="text-muted"><?php echo e(__('main.no_data_available')); ?></h5>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!--end::Card-->
    </div>
    <?php echo $__env->make('admin.transportations.modals.import', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<?php $__env->stopSection(); ?>
<?php $__env->startPush('js'); ?>
    <script>
        $(document).ready(function() {
            if ($('#transportationsTable').length) {
                // Destroy existing DataTable if it exists
                if ($.fn.DataTable.isDataTable('#transportationsTable')) {
                    $('#transportationsTable').DataTable().destroy();
                }

                // Initialize DataTable
                $('#transportationsTable').DataTable({
                    "language": {
                        "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Arabic.json"
                    },
                    "order": [[0, "desc"]],
                    "pageLength": 10,
                    "responsive": true,
                    "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                    "columnDefs": [
                        { "orderable": false, "targets": [7] } // Disable sorting on actions column
                    ]
                });
            }
        });

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
                    var url = '<?php echo e(route('companyTransportations.destroy', ':id')); ?>';
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
                        type: 'DELETE',
                        success: function(response) {
                            location.reload();
                            Swal.fire({
                                title: <?php echo e(__('alerts.done')); ?>,
                                icon: 'success',
                                showConfirmButton: false,
                                timer: 3000,
                                timerProgressBar: true,
                            });
                        }
                    });
                }
            });
        }
    </script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home3/cloudtal/cloudymenue.cloudy-digital.com/resources/views/admin/transportations/index.blade.php ENDPATH**/ ?>