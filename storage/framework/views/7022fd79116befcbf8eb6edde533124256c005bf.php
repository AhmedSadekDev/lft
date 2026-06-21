<?php $__env->startSection("content"); ?>
<div class="container-fluid">
    <?php echo $__env->make("layouts.includes.breadcrumb", [ 'page' => 'المصروفات العامة' ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <!--begin::Card-->
    <div class="card card-custom shadow-sm">
        <div class="card-header border-0 py-4">
            <div class="card-title">
                <h3 class="card-label font-weight-bolder text-dark">
                    <i class="fas fa-chart-line text-primary mr-2"></i>
                    تقرير المصروفات العامة
                </h3>
            </div>
            <div class="card-toolbar">
                <div class="d-flex gap-2 flex-wrap">
                    <!-- زر الفلتر -->
                    <button type="button" class="btn btn-primary font-weight-bold shadow-sm" data-toggle="modal" data-target="#filterModal">
                        <i class="fas fa-filter"></i> فلتر
                    </button>
                    <!-- زر تصدير Excel -->
                    <a href="<?php echo e(route('reports.general_expenses.export', array_filter(['from' => request('from'), 'to' => request('to'), 'search' => request('search')]))); ?>"
                       class="btn btn-success font-weight-bold shadow-sm">
                        <i class="fas fa-file-excel"></i> تصدير Excel
                    </a>
                </div>
            </div>
        </div>

        <!-- Modal الفلتر -->
        <div class="modal fade" id="filterModal" tabindex="-1" role="dialog" aria-labelledby="filterModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title font-weight-bold" id="filterModalLabel">
                            <i class="fas fa-calendar-alt mr-2"></i>فلترة حسب التاريخ
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form action="<?php echo e(route('reports.general_expenses')); ?>" method="get">
                        <div class="modal-body">
                            <?php if(request('search')): ?>
                                <input type="hidden" name="search" value="<?php echo e(request('search')); ?>">
                            <?php endif; ?>
                            <?php if(request('per_page')): ?>
                                <input type="hidden" name="per_page" value="<?php echo e(request('per_page')); ?>">
                            <?php endif; ?>
                            <div class="form-group">
                                <label for="fromInput" class="font-weight-bold">من تاريخ</label>
                                <input type="date" name="from" value="<?php echo e(request('from')); ?>" id="fromInput" class="form-control form-control-solid">
                            </div>
                            <div class="form-group">
                                <label for="toInput" class="font-weight-bold">إلى تاريخ</label>
                                <input type="date" name="to" value="<?php echo e(request('to')); ?>" id="toInput" class="form-control form-control-solid">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">إلغاء</button>
                            <button class="btn btn-primary font-weight-bold" type="submit">
                                <i class="fas fa-filter mr-1"></i> تطبيق الفلتر
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- شريط البحث -->
        <div class="card-body border-top">
            <form action="<?php echo e(route('reports.general_expenses')); ?>" method="get" class="mb-4">
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <label class="font-weight-bold text-dark mb-2">البحث</label>
                        <div class="input-group input-group-solid">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-light">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                            </div>
                            <input type="text" name="search" class="form-control form-control-solid"
                                   placeholder="ابحث عن المندوب، رقم الشحنة، أو الخدمة..."
                                   value="<?php echo e(request('search')); ?>">
                        </div>
                    </div>
                    <?php if(request('from') || request('to')): ?>
                        <input type="hidden" name="from" value="<?php echo e(request('from')); ?>">
                        <input type="hidden" name="to" value="<?php echo e(request('to')); ?>">
                    <?php endif; ?>
                    <?php if(request('per_page')): ?>
                        <input type="hidden" name="per_page" value="<?php echo e(request('per_page')); ?>">
                    <?php endif; ?>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary font-weight-bold w-100">
                            <i class="fas fa-search mr-1"></i> بحث
                        </button>
                    </div>
                    <?php if(request('search')): ?>
                        <div class="col-md-2">
                            <a href="<?php echo e(route('reports.general_expenses', array_filter(['from' => request('from'), 'to' => request('to'), 'per_page' => request('per_page')]))); ?>"
                               class="btn btn-secondary font-weight-bold w-100">
                                <i class="fas fa-times mr-1"></i> إلغاء البحث
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </form>

            <!-- معلومات النتائج -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="text-muted">
                        <i class="fas fa-info-circle mr-1"></i>
                        عرض <?php echo e($expenses->firstItem() ?? 0); ?> - <?php echo e($expenses->lastItem() ?? 0); ?> من أصل <?php echo e($expenses->total()); ?> سجل
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <label for="perPageSelect" class="mb-0 text-muted font-weight-bold" style="font-size: 0.9rem;">عرض:</label>
                        <select id="perPageSelect" class="form-control form-control-sm" style="width: auto; min-width: 80px;" onchange="changePerPage(this.value)">
                            <?php for($i = 15; $i <= 100; $i+=15): ?>
                                <option value="<?php echo e($i); ?>" <?php echo e(($perPage ?? 15) == $i ? 'selected' : ''); ?>><?php echo e($i); ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <?php if(request('from') || request('to')): ?>
                    <div class="badge badge-primary badge-pill">
                        <i class="fas fa-calendar mr-1"></i>
                        <?php if(request('from')): ?> من: <?php echo e(request('from')); ?> <?php endif; ?>
                        <?php if(request('to')): ?> إلى: <?php echo e(request('to')); ?> <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- الجدول -->
            <div class="table-responsive">
                <table class="table table-hover table-bordered table-head-custom table-vertical-center no-datatable" id="expensesTable">
                    <thead class="thead-light">
                        <tr class="text-center">
                            <th scope="col" style="width: 80px;">الصورة</th>
                            <th scope="col">الفئة</th>
                            <th scope="col">الخدمة</th>
                            <th scope="col" class="text-danger">القيمة منصرف</th>
                            <th scope="col" class="text-success">القيمة وارد</th>
                            <th scope="col">رقم الشحنة</th>
                            <th scope="col">التاريخ</th>
                            <th scope="col">المندوب</th>
                            <th scope="col">الملاحظات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $expenses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php
                            // التحقق من نوع السجل (مصروف أو معاملة مالية أو سجل نشاط)
                            $isMoneyTransfer = $item instanceof \App\Models\MoneyTransfer;
                            $isExpense = $item instanceof \App\Models\AgentExpense;
                            $isLogActivity = $item instanceof \App\Models\LogActivity;
                            $isPayingcar = $item instanceof \App\Models\Payingcar;

                            // حساب المصروفات والوارد
                            $expenseValue = 0;
                            $incomeValue = 0;
                            $category = '';
                            $service = '';
                            $agentName = '';
                            $bookingNumber = '';
                            $bookingId = null;
                            $imageUrl = '';
                            $date = '';
                            $notes = '';

                            if ($isLogActivity) {
                                // سجل نشاط (LogActivity)
                                $logType = $item->log_type;
                                $log = $item->log;
                                $value = $log?->value ?? 0;

                                $agentName = $item->attacher?->name ?? '-';
                                $date = $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('Y-m-d H:i') : '';

                                if ($logType == \App\Models\AgentExpense::class) {
                                    // مصروف مندوب (منصرف)
                                    $expenseValue = $value;
                                    $category = 'سجل نشاط - مصروف';
                                    $service = $item->action ?? 'مصروف مندوب';

                                    // محاولة الحصول على الصورة من المصروف
                                    if ($log && $log->image_agent_expenses) {
                                        $imgValue = $log->image_agent_expenses;
                                        // معالجة الصورة حسب نوعها
                                        if (strpos($imgValue, 'http') === 0 || strpos($imgValue, 'https') === 0) {
                                            $imageUrl = $imgValue; // رابط كامل
                                        } elseif (strpos($imgValue, 'data:image') === 0) {
                                            $imageUrl = $imgValue; // base64
                                        } elseif (strpos($imgValue, '/') === 0 || strpos($imgValue, 'storage/') !== false) {
                                            $imageUrl = asset($imgValue); // مسار storage
                                        } else {
                                            // اسم ملف فقط - جرب مسارات مختلفة
                                            if (file_exists(public_path('Admin/images/expenses/' . $imgValue))) {
                                                $imageUrl = asset('Admin/images/expenses/' . $imgValue);
                                            } elseif (file_exists(storage_path('app/public/agent_expenses/' . $imgValue))) {
                                                $imageUrl = asset('storage/agent_expenses/' . $imgValue);
                                            } else {
                                                $imageUrl = getImg($imgValue, 'agent_expenses');
                                            }
                                        }
                                    }

                                    // محاولة الحصول على رقم الشحنة
                                    if ($log && $log->bookingContainer) {
                                        $bookingNumber = $log->bookingContainer->booking?->booking_number ?? '';
                                        $bookingId = $log->bookingContainer->booking_id;
                                    }

                                    // الحصول على الملاحظات
                                    if ($log && isset($log->notes)) {
                                        $notes = $log->notes ?? '';
                                    }
                                } elseif ($logType == \App\Models\MoneyTransfer::class && $log) {
                                    $type = $log->type;

                                    if ($type == \App\Models\MoneyTransfer::deliveryPolicy) {
                                        // عهدة السيارة (منصرف)
                                        $expenseValue = $value;
                                        $category = 'سجل نشاط - عهدة السيارة';
                                        $service = $item->action ?? 'تحويل عهدة';

                                        // الحصول على الصورة من البوليصة
                                        if ($log->delivery_policy_id && $log->delivery_policy && $log->delivery_policy->image) {
                                            $imgObj = $log->delivery_policy->image;
                                            // Image model لديه accessor يعيد المسار الكامل
                                            $imageUrl = $imgObj->image ?? '';
                                        }

                                        // محاولة الحصول على رقم الشحنة
                                        if ($log->delivery_policy_id && $log->delivery_policy) {
                                            $firstContainer = $log->delivery_policy->booking_containers->first();
                                            if ($firstContainer) {
                                                $bookingNumber = $firstContainer->booking?->booking_number ?? '';
                                                $bookingId = $firstContainer->booking_id;
                                            }
                                        }
                                    } elseif ($type == \App\Models\MoneyTransfer::officeCommission) {
                                        // دخان المكتب (وارد)
                                        $incomeValue = $value;
                                        $category = 'سجل نشاط - دخان المكتب';
                                        $service = $item->action ?? 'دخان المكتب';

                                        // الحصول على الصورة من البوليصة
                                        if ($log->delivery_policy_id && $log->delivery_policy && $log->delivery_policy->image) {
                                            $imgObj = $log->delivery_policy->image;
                                            // Image model لديه accessor يعيد المسار الكامل
                                            $imageUrl = $imgObj->image ?? '';
                                        }

                                        // محاولة الحصول على رقم الشحنة
                                        if ($log->delivery_policy_id && $log->delivery_policy) {
                                            $firstContainer = $log->delivery_policy->booking_containers->first();
                                            if ($firstContainer) {
                                                $bookingNumber = $firstContainer->booking?->booking_number ?? '';
                                                $bookingId = $firstContainer->booking_id;
                                            }
                                        }
                                    } elseif ($type == \App\Models\MoneyTransfer::fromDashboard) {
                                        // إيداع من لوحة التحكم (وارد)
                                        $incomeValue = $value;
                                        $category = 'سجل نشاط - إيداع';
                                        $service = $item->action ?? 'إيداع من لوحة التحكم';
                                    } elseif ($type == \App\Models\MoneyTransfer::transferAgent) {
                                        // تحويل لمندوب آخر (منصرف)
                                        $expenseValue = $value;
                                        $category = 'سجل نشاط - تحويل';
                                        $service = $item->action ?? 'تحويل لمندوب آخر';
                                    } elseif ($type == \App\Models\MoneyTransfer::settle) {
                                        // تسوية (منصرف)
                                        $expenseValue = $value;
                                        $category = 'سجل نشاط - تسوية';
                                        $service = $item->action ?? 'تسوية البوليصة';
                                    }

                                    // الحصول على الملاحظات من MoneyTransfer
                                    if ($log && isset($log->notes)) {
                                        $notes = $log->notes ?? '';
                                    }
                                }
                            } elseif ($isMoneyTransfer && $item->type == 3) {
                                // عهدة السيارة (منصرف)
                                $expenseValue = $item->value ?? 0;
                                $category = 'عهدة السيارة';
                                $service = 'تحويل عهدة للسائق ' . ($item->transfered?->name ?? '');
                                $agentName = $item->transferer?->name ?? '-';
                                $date = $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('Y-m-d H:i') : '';

                                // الحصول على الصورة من البوليصة
                                if ($item->delivery_policy_id && $item->delivery_policy && $item->delivery_policy->image) {
                                    $imgObj = $item->delivery_policy->image;
                                    // Image model لديه accessor يعيد المسار الكامل
                                    $imageUrl = $imgObj->image ?? '';
                                }

                                // محاولة الحصول على رقم الشحنة من البوليصة
                                if ($item->delivery_policy_id && $item->delivery_policy) {
                                    $firstContainer = $item->delivery_policy->booking_containers->first();
                                    if ($firstContainer) {
                                        $bookingNumber = $firstContainer->booking?->booking_number ?? '';
                                        $bookingId = $firstContainer->booking_id;
                                    }
                                }

                                // الحصول على الملاحظات
                                $notes = $item->notes ?? '';
                            } elseif ($isMoneyTransfer && $item->type == 5) {
                                // دخان المكتب (وارد)
                                $incomeValue = $item->value ?? 0;
                                $category = 'دخان المكتب';
                                $service = 'عهدة السيارة';
                                $agentName = $item->transfered?->name ?? '-';
                                $date = $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('Y-m-d H:i') : '';

                                // الحصول على الصورة من البوليصة
                                if ($item->delivery_policy_id && $item->delivery_policy && $item->delivery_policy->image) {
                                    $imgObj = $item->delivery_policy->image;
                                    // Image model لديه accessor يعيد المسار الكامل
                                    $imageUrl = $imgObj->image ?? '';
                                }

                                // محاولة الحصول على رقم الشحنة من البوليصة
                                if ($item->delivery_policy_id && $item->delivery_policy) {
                                    $firstContainer = $item->delivery_policy->booking_containers->first();
                                    if ($firstContainer) {
                                        $bookingNumber = $firstContainer->booking?->booking_number ?? '';
                                        $bookingId = $firstContainer->booking_id;
                                    }
                                }

                                // الحصول على الملاحظات
                                $notes = $item->notes ?? '';
                            } elseif ($isExpense) {
                                // مصروف عادي (منصرف)
                                $expenseValue = $item->value ?? 0;

                                if ($item->delivery_policy_id) {
                                    $category = 'عهدة السيارة';
                                } elseif ($item->type == 2) {
                                    $category = 'عهدة السيارة';
                                } else {
                                    $category = 'من إدخال المندوب';
                                }

                                if ($item->service) {
                                    $service = ($item->service->serviceCategory?->title ?? '') . ' - ' . ($item->service->name ?? '');
                                } else {
                                    $service = '-';
                                }

                                $agentName = $item->agent?->name ?? '-';
                                $bookingNumber = $item->bookingContainer?->booking?->booking_number ?? '';
                                $bookingId = $item->bookingContainer?->booking_id;

                                // الحصول على الملاحظات
                                $notes = $item->notes ?? '';

                                // معالجة الصورة من AgentExpense
                                if ($item->image_agent_expenses) {
                                    $imgValue = $item->image_agent_expenses;
                                    // معالجة الصورة حسب نوعها
                                    if (strpos($imgValue, 'http') === 0 || strpos($imgValue, 'https') === 0) {
                                        $imageUrl = $imgValue; // رابط كامل
                                    } elseif (strpos($imgValue, 'data:image') === 0) {
                                        $imageUrl = $imgValue; // base64
                                    } elseif (strpos($imgValue, '/') === 0 || strpos($imgValue, 'storage/') !== false) {
                                        $imageUrl = asset($imgValue); // مسار storage
                                    } else {
                                        // اسم ملف فقط - جرب مسارات مختلفة
                                        if (file_exists(public_path('Admin/images/expenses/' . $imgValue))) {
                                            $imageUrl = asset('Admin/images/expenses/' . $imgValue);
                                        } elseif (file_exists(storage_path('app/public/agent_expenses/' . $imgValue))) {
                                            $imageUrl = asset('storage/agent_expenses/' . $imgValue);
                                        } else {
                                            $imageUrl = getImg($imgValue, 'agent_expenses');
                                        }
                                    }
                                } else {
                                    $imageUrl = '';
                                }

                                $date = $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('Y-m-d H:i') : '';
                            } elseif ($isPayingcar) {
                                // سداد المديونية للسيارات (منصرف)
                                $expenseValue = (float) ($item->value ?? 0);
                                $category = 'سداد المديونية للسيارات';
                                $service = 'سداد مديونية السيارة - ' . ($item->car?->car_number ?? '');
                                $agentName = $item->user?->name ?? '-';
                                $date = $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('Y-m-d H:i') : '';

                                // الحصول على الصورة
                                if ($item->image) {
                                    $imgValue = $item->image;
                                    // معالجة الصورة حسب نوعها
                                    if (strpos($imgValue, 'http') === 0 || strpos($imgValue, 'https') === 0) {
                                        $imageUrl = $imgValue; // رابط كامل
                                    } elseif (strpos($imgValue, 'data:image') === 0) {
                                        $imageUrl = $imgValue; // base64
                                    } elseif (strpos($imgValue, '/') === 0 || strpos($imgValue, 'storage/') !== false) {
                                        $imageUrl = asset($imgValue); // مسار storage
                                    } else {
                                        // اسم ملف فقط
                                        if (file_exists(public_path('Admin/images/banks/' . $imgValue))) {
                                            $imageUrl = asset('Admin/images/banks/' . $imgValue);
                                        } else {
                                            $imageUrl = asset($imgValue);
                                        }
                                    }
                                } else {
                                    $imageUrl = '';
                                }

                                // محاولة الحصول على رقم الشحنة من البوليصة
                                if ($item->delivery_policy_id && $item->delivery_policy) {
                                    $firstContainer = $item->delivery_policy->booking_containers->first();
                                    if ($firstContainer) {
                                        $bookingNumber = $firstContainer->booking?->booking_number ?? '';
                                        $bookingId = $firstContainer->booking_id;
                                    }
                                }

                                // الحصول على الملاحظات
                                $notes = $item->notes ?? '';
                            }
                        ?>
                        <tr>
                            <td class="text-center align-middle">
                                <?php if($imageUrl): ?>
                                    <?php
                                        // تحديد المسار الصحيح للصورة (تمت معالجتها مسبقاً)
                                        $imagePath = $imageUrl;
                                        // إذا كانت الصورة مسار نسبي ولم تبدأ بـ http أو data، أضف asset
                                        if (strpos($imagePath, 'http') !== 0 && strpos($imagePath, 'data:image') !== 0 && strpos($imagePath, '/') === 0) {
                                            $imagePath = asset($imagePath);
                                        }
                                    ?>
                                    <?php
                                        $fallbackImage = asset('assets/img/avatar_logo.png');
                                    ?>
                                    <a href="<?php echo e($imagePath); ?>" data-lightbox="expense-<?php echo e($item->id); ?>" data-title="إيصال المصروف - <?php echo e($category); ?>">
                                        <img src="<?php echo e($imagePath); ?>" alt="صورة المصروف" class="img-thumbnail" style="width: 60px; height: 60px; object-fit: cover; cursor: pointer; border-radius: 8px;" onerror="this.src='<?php echo e($fallbackImage); ?>'; this.onerror=null;" />
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center align-middle">
                                <span class="badge badge-info badge-pill"><?php echo e($category); ?></span>
                            </td>
                            <td class="align-middle"><?php echo e($service); ?></td>
                            <td class="text-center align-middle">
                                <?php if($expenseValue > 0): ?>
                                    <strong class="text-danger font-weight-bold"><?php echo e(number_format($expenseValue, 2)); ?></strong>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center align-middle">
                                <?php if($incomeValue > 0): ?>
                                    <strong class="text-success font-weight-bold"><?php echo e(number_format($incomeValue, 2)); ?></strong>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center align-middle">
                                <?php if($bookingId && $bookingNumber): ?>
                                    <a href="<?php echo e(route('bookings.show', $bookingId)); ?>" class="btn btn-sm btn-link font-weight-bold text-primary">
                                        <i class="fas fa-link mr-1"></i><?php echo e($bookingNumber); ?>

                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center align-middle">
                                <small class="text-muted"><?php echo e($date); ?></small>
                            </td>
                            <td class="align-middle">
                                <span class="font-weight-bold"><?php echo e($agentName); ?></span>
                            </td>
                            <td class="align-middle">
                                <?php if(!empty($notes)): ?>
                                    <span class="text-muted" style="font-size: 0.9rem;" title="<?php echo e($notes); ?>"><?php echo e(Str::limit($notes, 50)); ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="fas fa-inbox fa-3x mb-3"></i>
                                    <p class="font-weight-bold">لا توجد بيانات</p>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="bg-light font-weight-bold">
                        <tr>
                            <td colspan="3" class="text-right align-middle">
                                <strong>الإجمالي:</strong>
                            </td>
                            <td class="text-center text-danger align-middle">
                                <strong><?php echo e(number_format($totalExpenses ?? 0, 2)); ?></strong>
                            </td>
                            <td class="text-center text-success align-middle">
                                <strong><?php echo e(number_format($totalIncome ?? 0, 2)); ?></strong>
                            </td>
                            <td colspan="4" class="text-right align-middle">
                                <span class="text-muted">الصافي: </span>
                                <strong class="<?php echo e((($totalIncome ?? 0) - ($totalExpenses ?? 0)) >= 0 ? 'text-success' : 'text-danger'); ?>">
                                    <?php echo e(number_format(($totalIncome ?? 0) - ($totalExpenses ?? 0), 2)); ?>

                                </strong>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Pagination -->
            <?php if($expenses->hasPages()): ?>
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <div class="text-muted">
                        صفحة <?php echo e($expenses->currentPage()); ?> من <?php echo e($expenses->lastPage()); ?>

                    </div>
                    <div>
                        <?php echo e($expenses->appends(request()->query())->links('pagination::bootstrap-4')); ?>

                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <!--end::Card-->
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('js'); ?>
    <!-- Lightbox CSS & JS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>

    <script>
        function changePerPage(value) {
            const url = new URL(window.location.href);
            url.searchParams.set('per_page', value);
            url.searchParams.set('page', '1'); // العودة للصفحة الأولى عند تغيير عدد العناصر
            window.location.href = url.toString();
        }
    </script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make("layouts.admin", \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home3/cloudtal/cloudymenue.cloudy-digital.com/resources/views/admin/reports/general_expenses.blade.php ENDPATH**/ ?>