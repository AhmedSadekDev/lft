
<?php $__env->startSection('content'); ?>
    <style>
        .bookings-table {
            font-size: 14px;
        }
        .bookings-table thead th {
            background-color: #f8f9fa;
            color: #495057;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
            padding: 12px;
            white-space: nowrap;
        }
        .bookings-table tbody td {
            padding: 12px;
            vertical-align: middle;
        }
        .bookings-table tbody tr:hover {
            background-color: #f8f9fa;
        }
        .filter-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .badge-custom {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
        }
        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        .action-buttons .btn {
            padding: 5px 10px;
            font-size: 12px;
        }
        .search-input-group {
            position: relative;
        }
        .search-input-group .form-control {
            padding-right: 40px;
        }
        .search-input-group .search-icon {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        .filter-badge {
            display: inline-block;
            margin: 2px;
            padding: 4px 8px;
            background: #e9ecef;
            border-radius: 4px;
            font-size: 12px;
        }
        .pagination-wrapper {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            border-top: 1px solid #e9ecef;
            margin-top: 20px;
        }
        .pagination-info {
            color: #6c757d;
            font-size: 14px;
        }
        .pagination {
            margin: 0;
        }
        .pagination .page-link {
            color: #495057;
            border: 1px solid #dee2e6;
            padding: 8px 12px;
            margin: 0 2px;
            border-radius: 4px;
            transition: all 0.3s;
        }
        .pagination .page-link:hover {
            background-color: #e9ecef;
            border-color: #adb5bd;
            color: #495057;
        }
        .pagination .page-item.active .page-link {
            background-color: #007bff;
            border-color: #007bff;
            color: #fff;
            font-weight: 600;
        }
        .pagination .page-item.disabled .page-link {
            color: #6c757d;
            pointer-events: none;
            background-color: #fff;
            border-color: #dee2e6;
            opacity: 0.5;
        }
        .custom-pagination {
            display: flex;
            align-items: center;
        }
        .pagination-list {
            display: flex;
            list-style: none;
            padding: 0;
            margin: 0;
            gap: 5px;
            align-items: center;
        }
        .pagination-item {
            margin: 0;
        }
        .pagination-link {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 8px 14px;
            min-width: 40px;
            height: 40px;
            color: #495057;
            background-color: #fff;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .pagination-link:hover:not(.disabled) {
            background-color: #e9ecef;
            border-color: #adb5bd;
            color: #495057;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .pagination-item.active .pagination-link {
            background-color: #007bff;
            border-color: #007bff;
            color: #fff;
            font-weight: 600;
            box-shadow: 0 2px 6px rgba(0,123,255,0.3);
        }
        .pagination-item.active .pagination-link:hover {
            background-color: #0056b3;
            border-color: #0056b3;
            transform: translateY(-1px);
        }
        .pagination-link.disabled,
        .pagination-item.disabled .pagination-link {
            color: #6c757d;
            background-color: #f8f9fa;
            border-color: #e9ecef;
            cursor: not-allowed;
            opacity: 0.6;
        }
        .pagination-prev,
        .pagination-next {
            gap: 6px;
            font-weight: 500;
        }
        .pagination-prev i,
        .pagination-next i {
            font-size: 12px;
        }
        .pagination-item.disabled span {
            cursor: not-allowed;
        }
        .table-container {
            min-height: 400px;
        }
        @media (max-width: 768px) {
            .pagination-wrapper {
                flex-direction: column;
                gap: 15px;
            }
            .pagination-info {
                text-align: center;
            }
            .pagination-list {
                flex-wrap: wrap;
                justify-content: center;
            }
            .pagination-link {
                padding: 6px 10px;
                min-width: 36px;
                height: 36px;
                font-size: 13px;
            }
            .pagination-prev span,
            .pagination-next span {
                display: none;
            }
        }
    </style>

    <div class="container-fluid">
        <?php echo $__env->make('layouts.includes.breadcrumb', ['page' => __('main.bookings')], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

        <!-- Filters Card -->
        <div class="card filter-card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-filter"></i> <?php echo e(__('admin.search')); ?> و <?php echo e(__('admin.filter')); ?>

                </h5>
            </div>
            <div class="card-body">
                <form method="GET" action="<?php echo e(route('bookings.index')); ?>" id="filterForm">
                    <?php if(request('per_page')): ?>
                        <input type="hidden" name="per_page" value="<?php echo e(request('per_page')); ?>">
                    <?php endif; ?>
                    <div class="row">
                        <!-- Search Input -->
                        <div class="col-md-3 mb-3">
                            <label class="form-label"><?php echo e(__('admin.search')); ?></label>
                            <div class="search-input-group">
                                <input type="text" name="search" class="form-control"
                                        value="<?php echo e(request('search')); ?>"
                                        placeholder="<?php echo e(__('admin.search')); ?>...">
                                <i class="fas fa-search search-icon"></i>
                            </div>
                        </div>

                        <!-- Date From Filter -->
                        <div class="col-md-2 mb-3">
                            <label class="form-label"><?php echo e(__('admin.date_from') ?? 'تاريخ من'); ?></label>
                            <input type="date" name="date_from" class="form-control"
                                    value="<?php echo e(request('date_from')); ?>">
                        </div>

                        <!-- Date To Filter -->
                        <div class="col-md-2 mb-3">
                            <label class="form-label"><?php echo e(__('admin.date_to') ?? 'تاريخ إلى'); ?></label>
                            <input type="date" name="date_to" class="form-control"
                                    value="<?php echo e(request('date_to')); ?>">
                        </div>

                        <!-- Company Filter -->
                        <div class="col-md-2 mb-3">
                            <label class="form-label"><?php echo e(__('main.company')); ?></label>
                            <select name="company" class="form-control">
                                <option value=""><?php echo e(__('admin.all')); ?></option>
                                <?php $__currentLoopData = $companies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $company): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($company->id); ?>"
                                            <?php echo e(request('company') == $company->id ? 'selected' : ''); ?>>
                                        <?php echo e($company->name); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>

                        <!-- Tax Status Filter -->
                        <div class="col-md-2 mb-3">
                            <label class="form-label"><?php echo e(__('admin.taxed_status')); ?></label>
                            <select name="tax_status" class="form-control">
                                <option value=""><?php echo e(__('admin.all')); ?></option>
                                <option value="1" <?php echo e(request('tax_status') == '1' ? 'selected' : ''); ?>>
                                    <?php echo e(__('admin.taxed')); ?>

                                </option>
                                <option value="0" <?php echo e(request('tax_status') == '0' ? 'selected' : ''); ?>>
                                    <?php echo e(__('admin.not_taxed')); ?>

                                </option>
                            </select>
                        </div>

                        <!-- Invoice Status Filter -->
                        <div class="col-md-2 mb-3">
                            <label class="form-label">حالة الفاتورة</label>
                            <select name="invoice_status" class="form-control">
                                <option value=""><?php echo e(__('admin.all')); ?></option>
                                <option value="1" <?php echo e(request('invoice_status') == '1' ? 'selected' : ''); ?>>
                                    تم إنشاء فاتورة
                                </option>
                                <option value="0" <?php echo e(request('invoice_status') == '0' ? 'selected' : ''); ?>>
                                    لم يتم إنشاء فاتورة
                                </option>
                            </select>
                        </div>

                        <!-- Action Buttons -->
                        <div class="col-md-1 mb-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search"></i> <?php echo e(__('admin.search')); ?>

                            </button>
                        </div>
                    </div>

                    <!-- Active Filters -->
                    <?php if(request()->hasAny(['search', 'arrival_date', 'company', 'tax_status', 'invoice_status'])): ?>
                        <div class="row mt-2">
                            <div class="col-12">
                                <small class="text-muted">الفلاتر النشطة:</small>
                                <?php if(request('search')): ?>
                                    <span class="filter-badge">
                                        بحث: <?php echo e(request('search')); ?>

                                        <a href="<?php echo e(route('bookings.index', request()->except('search'))); ?>" class="ml-2 text-danger">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    </span>
                                <?php endif; ?>
                                <?php if(request('arrival_date')): ?>
                                    <span class="filter-badge">
                                        تاريخ: <?php echo e(request('arrival_date')); ?>

                                        <a href="<?php echo e(route('bookings.index', request()->except('arrival_date'))); ?>" class="ml-2 text-danger">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    </span>
                                <?php endif; ?>
                                <?php if(request('company')): ?>
                                    <span class="filter-badge">
                                        شركة: <?php echo e($companies->find(request('company'))->name ?? ''); ?>

                                        <a href="<?php echo e(route('bookings.index', request()->except('company'))); ?>" class="ml-2 text-danger">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    </span>
                                <?php endif; ?>
                                <?php if(request('tax_status') !== null && request('tax_status') !== ''): ?>
                                    <span class="filter-badge">
                                        ضريبة: <?php echo e(request('tax_status') == '1' ? 'معفى' : 'غير معفى'); ?>

                                        <a href="<?php echo e(route('bookings.index', request()->except('tax_status'))); ?>" class="ml-2 text-danger">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    </span>
                                <?php endif; ?>
                                <?php if(request('invoice_status') !== null && request('invoice_status') !== ''): ?>
                                    <span class="filter-badge">
                                        فاتورة: <?php echo e(request('invoice_status') == '1' ? 'تم الإنشاء' : 'لم يتم الإنشاء'); ?>

                                        <a href="<?php echo e(route('bookings.index', request()->except('invoice_status'))); ?>" class="ml-2 text-danger">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    </span>
                                <?php endif; ?>
                                <a href="<?php echo e(route('bookings.index')); ?>" class="btn btn-sm btn-outline-danger ml-2">
                                    <i class="fas fa-times"></i> إزالة جميع الفلاتر
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Bookings Table Card -->
        <div class="card card-custom">
            <div class="card-header flex-wrap py-4">
                <div class="card-title">
                    <h3 class="card-label">
                        <?php echo e(__('main.bookings')); ?>

                        <span class="text-muted font-size-sm ml-2">
                            (<?php echo e($bookings->total()); ?> <?php echo e(__('admin.result')); ?>)
                        </span>
                    </h3>
                </div>
                <div class="card-toolbar">
                    <?php if(auth()->user()->hasPermissionTo('bookings.create')): ?>
                        <a href="<?php echo e(route('bookings.create')); ?>" class="btn btn-primary font-weight-bolder">
                            <i class="fas fa-plus"></i> <?php echo e(__('admin.add_new_booking')); ?>

                        </a>
                    <?php endif; ?>
                    <?php if($bookings->count() > 0): ?>
                        <a class="btn btn-success ml-2"
                            href="<?php echo e(route('booking_container.export', request()->only(['search', 'date_from', 'date_to', 'company', 'tax_status', 'invoice_status']))); ?>"
                            title="<?php echo e(__('admin.export')); ?>">
                            <i class="fas fa-download"></i> <?php echo e(__('admin.export')); ?>

                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card-body">
                <?php if($bookings->count() > 0): ?>
                    <div class="table-container">
                        <div class="table-responsive">
                            <table class="table table-hover bookings-table no-datatable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th><?php echo e(__('admin.company_name')); ?></th>
                                    <th><?php echo e(__('admin.responsible_employee')); ?></th>
                                    <th><?php echo e(__('main.factory')); ?></th>
                                    <th><?php echo e(__('admin.booking_number')); ?></th>
                                    <th><?php echo e(__('admin.taxed_status')); ?></th>
                                    <th>حالة الفاتورة</th>
                                    <th><?php echo e(__('admin.created_at')); ?></th>
                                    <th>الفاتورة</th>
                                    <th>الملاحظات</th>
                                    <th><?php echo e(__('admin.actions')); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__currentLoopData = $bookings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $booking): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr>
                                        <td><strong><?php echo e($booking->id); ?></strong></td>
                                        <td><?php echo e($booking->company->name ?? '__'); ?></td>
                                        <td><?php echo e($booking->employee_name ?? '__'); ?></td>
                                        <td><?php echo e($booking?->factory?->name ?? '__'); ?></td>
                                        <td>
                                            <span class="badge badge-info badge-custom">
                                                <?php echo e($booking->booking_number); ?>

                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php echo e($booking->company && $booking->company->taxed == 0 ? 'danger' : 'success'); ?> badge-custom">
                                                <i class="fa fa-<?php echo e($booking->company && $booking->company->taxed == 0 ? 'xmark' : 'check'); ?>"></i>
                                                <?php echo e($booking->taxed_invoice); ?>

                                            </span>
                                        </td>
                                        <td>
                                            <?php if(!is_null($booking->invoice?->invoice_number)): ?>
                                                <div class="d-flex flex-column">
                                                    <span class="badge badge-success badge-custom mb-1">
                                                        <i class="fas fa-check"></i> تم إنشاء فاتورة
                                                    </span>
                                                    <span class="badge badge-info badge-custom" style="font-size: 0.75rem;">
                                                        <i class="fas fa-file-invoice"></i> <?php echo e($booking->invoice->invoice_number); ?>

                                                    </span>
                                                </div>
                                            <?php else: ?>
                                                <span class="badge badge-warning badge-custom">
                                                    <i class="fas fa-clock"></i> لم يتم إنشاء فاتورة
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo e($booking->created_at ? \Carbon\Carbon::parse($booking->created_at)->format('Y-m-d') : '__'); ?></td>
                                        <td>
                                            <?php if(is_null($booking->invoice?->invoice_number)): ?>
                                                <?php if($booking->type_of_action != 2): ?>
                                                    <a class="btn btn-sm btn-primary" href="<?php echo e(route('booking-invoices.create', $booking->id)); ?>">
                                                        <i class="fas fa-plus"></i> إنشاء فاتورة
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <?php if($booking->type_of_action != 2): ?>
                                                    <div class="d-flex flex-column gap-1">
                                                        <a href="<?php echo e(route('booking-invoices.edit', $booking->invoice->id)); ?>" class="btn btn-sm btn-warning">
                                                            <i class="fas fa-edit"></i> تعديل
                                                        </a>
                                                        <a href="<?php echo e(route('booking-invoices.show', ['booking_invoice' => $booking->invoice->id])); ?>" class="btn btn-sm btn-info">
                                                            <i class="fas fa-eye"></i> عرض
                                                        </a>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="<?php echo e(route('bookings.booking_notes', ['booking' => $booking->id])); ?>" class="btn btn-sm btn-secondary">
                                                <i class="fas fa-sticky-note"></i> الملاحظات
                                            </a>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <?php if(auth()->user()->hasPermissionTo('bookings.index')): ?>
                                                    <a href="<?php echo e(route('bookings.show', $booking->id)); ?>"
                                                        class="btn btn-sm btn-light btn-hover-success"
                                                        title="<?php echo e(__('admin.show')); ?>">
                                                        <i class="fas fa-eye text-success"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if(auth()->user()->hasPermissionTo('bookings.update')): ?>
                                                    <a href="<?php echo e(route('bookings.edit', $booking->id)); ?>"
                                                        class="btn btn-sm btn-light btn-hover-primary"
                                                        title="<?php echo e(__('admin.edit')); ?>">
                                                        <i class="fas fa-edit text-primary"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if(auth()->user()->hasPermissionTo('bookings.delete')): ?>
                                                    <button class="btn btn-sm btn-light btn-hover-danger delete"
                                                            onclick="Delete('<?php echo e($booking->id); ?>')"
                                                            title="<?php echo e(__('admin.delete')); ?>">
                                                        <i class="fas fa-trash text-danger"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <a href="<?php echo e(route('bookings.booking_papers', ['booking' => $booking->id])); ?>"
                                                    class="btn btn-sm btn-light btn-hover-info"
                                                    title="<?php echo e(__('admin.papers')); ?>">
                                                    <i class="fas fa-file text-info"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                        </div>

                        <!-- Custom Pagination -->
                        <div class="pagination-wrapper">
                            <div class="d-flex align-items-center gap-3">
                                <div class="pagination-info">
                                    <i class="fas fa-info-circle"></i>
                                    عرض <?php echo e($bookings->firstItem() ?? 0); ?> إلى <?php echo e($bookings->lastItem() ?? 0); ?> من <?php echo e($bookings->total()); ?> نتيجة
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <label for="perPageSelect" class="mb-0 text-muted font-weight-bold" style="font-size: 0.9rem;">عرض:</label>
                                    <select id="perPageSelect" class="form-control form-control-sm" style="width: auto; min-width: 80px;" onchange="changePerPage(this.value)">
                                        <?php for($i = 15; $i <= 100; $i+=15): ?>
                                            <option value="<?php echo e($i); ?>" <?php echo e((request('per_page', 15) == $i) ? 'selected' : ''); ?>><?php echo e($i); ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="custom-pagination">
                                <?php
                                    $currentPage = $bookings->currentPage();
                                    $lastPage = $bookings->lastPage();
                                    $queryParams = request()->query();
                                ?>

                                <ul class="pagination-list">
                                    
                                    <li class="pagination-item <?php echo e($currentPage == 1 ? 'disabled' : ''); ?>">
                                        <?php if($currentPage > 1): ?>
                                            <a href="<?php echo e($bookings->appends($queryParams)->url($currentPage - 1)); ?>" class="pagination-link pagination-prev">
                                                <i class="fas fa-chevron-right"></i>
                                                <span>السابق</span>
                                            </a>
                                        <?php else: ?>
                                            <span class="pagination-link pagination-prev disabled">
                                                <i class="fas fa-chevron-right"></i>
                                                <span>السابق</span>
                                            </span>
                                        <?php endif; ?>
                                    </li>

                                    
                                    <?php if($lastPage <= 7): ?>
                                        
                                        <?php for($i = 1; $i <= $lastPage; $i++): ?>
                                            <li class="pagination-item <?php echo e($i == $currentPage ? 'active' : ''); ?>">
                                                <a href="<?php echo e($bookings->appends($queryParams)->url($i)); ?>" class="pagination-link">
                                                    <?php echo e($i); ?>

                                                </a>
                                            </li>
                                        <?php endfor; ?>
                                    <?php else: ?>
                                        
                                        <?php if($currentPage > 3): ?>
                                            <li class="pagination-item">
                                                <a href="<?php echo e($bookings->appends($queryParams)->url(1)); ?>" class="pagination-link">1</a>
                                            </li>
                                            <?php if($currentPage > 4): ?>
                                                <li class="pagination-item disabled">
                                                    <span class="pagination-link">...</span>
                                                </li>
                                            <?php endif; ?>
                                        <?php endif; ?>

                                        
                                        <?php for($i = max(1, $currentPage - 2); $i <= min($lastPage, $currentPage + 2); $i++): ?>
                                            <li class="pagination-item <?php echo e($i == $currentPage ? 'active' : ''); ?>">
                                                <a href="<?php echo e($bookings->appends($queryParams)->url($i)); ?>" class="pagination-link">
                                                    <?php echo e($i); ?>

                                                </a>
                                            </li>
                                        <?php endfor; ?>

                                        
                                        <?php if($currentPage < $lastPage - 2): ?>
                                            <?php if($currentPage < $lastPage - 3): ?>
                                                <li class="pagination-item disabled">
                                                    <span class="pagination-link">...</span>
                                                </li>
                                            <?php endif; ?>
                                            <li class="pagination-item">
                                                <a href="<?php echo e($bookings->appends($queryParams)->url($lastPage)); ?>" class="pagination-link"><?php echo e($lastPage); ?></a>
                                            </li>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    
                                    <li class="pagination-item <?php echo e($currentPage == $lastPage ? 'disabled' : ''); ?>">
                                        <?php if($currentPage < $lastPage): ?>
                                            <a href="<?php echo e($bookings->appends($queryParams)->url($currentPage + 1)); ?>" class="pagination-link pagination-next">
                                                <span>التالي</span>
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="pagination-link pagination-next disabled">
                                                <span>التالي</span>
                                                <i class="fas fa-chevron-left"></i>
                                            </span>
                                        <?php endif; ?>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted"><?php echo e(__('admin.no_data')); ?></p>
                        <?php if(auth()->user()->hasPermissionTo('bookings.create')): ?>
                            <a href="<?php echo e(route('bookings.create')); ?>" class="btn btn-primary">
                                <i class="fas fa-plus"></i> <?php echo e(__('admin.add_new_booking')); ?>

                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('js'); ?>
    <script>
        (function($) {
            "use strict";

            // Auto submit form on filter change (optional)
            // $('select[name="company"], select[name="tax_status"], select[name="invoice_status"]').on('change', function() {
            //     $('#filterForm').submit();
            // });

            // Clear search on X click
            $('.search-input-group .form-control').on('input', function() {
                if ($(this).val() === '') {
                    // Optionally auto-submit when cleared
                }
            });
        })(jQuery);

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
                    var url = '<?php echo e(route('bookings.destroy', ':id')); ?>';
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
                            Swal.fire({
                                title: "<?php echo e(__('alerts.done')); ?>",
                                icon: 'success',
                                showConfirmButton: false,
                                timer: 2000,
                                timerProgressBar: true,
                            }).then(() => {
                                location.reload();
                            });
                        },
                        error: function() {
                            Swal.fire({
                                title: "<?php echo e(__('alerts.error')); ?>",
                                text: "حدث خطأ أثناء الحذف",
                                icon: 'error',
                            });
                        }
                    });
                }
            });
        }

        function changePerPage(value) {
            const url = new URL(window.location.href);
            url.searchParams.set('per_page', value);
            url.searchParams.set('page', '1'); // العودة للصفحة الأولى عند تغيير عدد العناصر
            window.location.href = url.toString();
        }
    </script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home3/cloudtal/cloudymenue.cloudy-digital.com/resources/views/admin/bookings/index.blade.php ENDPATH**/ ?>