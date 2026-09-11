
<?php $__env->startSection('content'); ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <style>
        .booking-edit-page { direction: rtl; background: #f0f2f5; padding-bottom: 2rem; }
        .booking-edit-header {
            background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 50%, #3d7ab5 100%);
            border-radius: 14px;
            padding: 1.5rem 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 6px 24px rgba(30, 58, 95, 0.25);
        }
        .booking-edit-header-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .booking-edit-icon {
            width: 52px;
            height: 52px;
            background: rgba(255,255,255,0.2);
            border: 2px solid rgba(255,255,255,0.4);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .booking-edit-icon i { font-size: 1.6rem; color: #fff; }
        .booking-edit-title { color: #fff; font-size: 1.4rem; font-weight: 700; margin: 0 0 0.25rem 0; }
        .booking-edit-subtitle { color: rgba(255,255,255,0.95); font-size: 0.9rem; margin: 0; }
        .booking-edit-back {
            background: #fff;
            color: #1e3a5f;
            padding: 0.6rem 1.25rem;
            border-radius: 10px;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .booking-edit-back:hover { color: #1e3a5f; text-decoration: none; }

        .booking-edit-card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 2px 14px rgba(0,0,0,0.06);
            border: 1px solid #e8ecf1;
            overflow: hidden;
        }
        .booking-edit-card .card-body { padding: 1.5rem 2rem; }

        .booking-form-label {
            font-weight: 700;
            color: #1a1d21;
            margin-bottom: 0.4rem;
        }
        .booking-form-control {
            border: 1px solid #e8ecf1;
            border-radius: 10px;
            padding: 0.6rem 1rem;
        }
        .booking-form-control:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.15);
        }

        .booking-container-block {
            background: #f8fafc;
            border: 1px solid #e8ecf1;
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 1.25rem;
        }
        .booking-container-block .form-group label {
            font-weight: 700;
            color: #1a1d21;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .booking-container-block .form-group label::before {
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            font-size: 0.9rem;
            color: #0d6efd;
        }
        .booking-container-block .form-group:has(select[name*="branch_id"]) label::before { content: "\f1ad"; }
        .booking-container-block .form-group:has(select[name*="container_id"]) label::before { content: "\f466"; }
        .booking-container-block .form-group:has(input[name*="arrival_date"]) label::before { content: "\f073"; }
        .booking-container-block .form-group:has(input[name*="containers_count"]) label::before { content: "\f0ca"; }

        .booking-submit-wrap {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e8ecf1;
        }
        .booking-submit-btn {
            background: #198754;
            color: #fff;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 10px;
            font-weight: 700;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: background 0.2s, transform 0.2s;
        }
        .booking-submit-btn:hover { color: #fff; background: #157347; transform: translateY(-2px); }
        .booking-submit-btn i { font-size: 1rem; }
    </style>

    <div class="container booking-edit-page">
        <?php echo $__env->make('layouts.includes.breadcrumb', ['page' => __('admin.edit_booking_information')], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

        <div class="booking-edit-header">
            <div class="booking-edit-header-inner">
                <div class="d-flex align-items-center">
                    
                    <div class="mr-3">
                        <h1 class="booking-edit-title"><?php echo e(__('admin.edit_booking_information')); ?></h1>
                        <p class="booking-edit-subtitle"><?php echo e(__('admin.booking_number')); ?>: <?php echo e($booking->booking_number ?? '-'); ?></p>
                    </div>
                </div>
                <a href="<?php echo e(route('bookings.show', $booking)); ?>" class="booking-edit-back">
                    <i class="fas fa-arrow-right"></i>
                    رجوع لتفاصيل الطلب
                </a>
            </div>
        </div>

        <div class="booking-edit-card card card-custom gutter-b">
            <div class="card-header bg-light border-0 py-3">
                <h2 class="card-title mb-0 font-weight-bold text-dark">
                    <i class="fas fa-clipboard-list text-primary ml-2"></i>
                    بيانات الطلب والحاويات
                </h2>
            </div>
            <?php echo $__env->make('admin.bookings.form', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        </div>
    </div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home3/cloudtal/cloudymenue.cloudy-digital.com/resources/views/admin/bookings/edit.blade.php ENDPATH**/ ?>