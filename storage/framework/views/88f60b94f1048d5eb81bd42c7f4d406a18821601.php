<!--end::Global Config-->
<!--begin::Global Theme Bundle(used by all pages)-->
<script src="<?php echo e(asset('assets/plugins/global/plugins.bundle.js')); ?>"></script>
<script src="<?php echo e(asset('assets/js/scripts.bundle.js')); ?>"></script>
<!--end::Global Theme Bundle-->

<script src="<?php echo e(asset('assets/js/jquery.dataTables.min.js')); ?>"></script>
<script src="<?php echo e(asset('assets/js/dataTables.bootstrap4.min.js')); ?>"></script>
<script src="<?php echo e(asset('assets/js/bootstrap-select.min.js')); ?>"></script>
<!-- =================== CDNs =================== -->

<script src="<?php echo e(asset('assets/js/sweetalert.min.js')); ?>"></script>
<!-- Toaster Scripts --->
<script src="<?php echo e(asset('public/assets/admin/plugins/toastr/toastr.min.js')); ?>"></script>
<script>
    toastr.options = {
        "closeButton": false,
        "debug": false,
        "newestOnTop": true,
        "progressBar": true,
        "positionClass": "toast-top-center",
        "preventDuplicates": false,
        "onclick": null,
        "showDuration": "300",
        "hideDuration": "1000",
        "timeOut": "5000",
        "extendedTimeOut": "1000",
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut",
        'rtl': false
    }
</script>
<!-- =================== \CDNs =================== -->
<script>
    $(document).ready(function() {
        // تهيئة DataTable على جميع الجداول ما عدا التي تحتوي على class no-datatable
        $('table:not(.no-datatable)').DataTable({
            "order": [
                [0, 'desc']
            ],
            "language": {
                "sProcessing": "جارٍ التحميل...",
                "sLengthMenu": "أظهر _MENU_ مدخلات",
                "sZeroRecords": "لم يعثر على أية سجلات",
                "sInfo": "إظهار _START_ إلى _END_ من أصل _TOTAL_ مدخل",
                "sInfoEmpty": "يعرض 0 إلى 0 من أصل 0 سجل",
                "sInfoFiltered": "(منتقاة من مجموع _MAX_ مُدخل)",
                "sInfoPostFix": "",
                "sSearch": "ابحث:",
                "sUrl": "",
                "oPaginate": {
                    "sFirst": "الأول",
                    "sPrevious": "السابق",
                    "sNext": "التالي",
                    "sLast": "الأخير"
                }
            }
        });
    });

    <?php if(Session::has('error')): ?>
        toastr.error(`<?php echo e(session('error')); ?>`);
    <?php elseif(Session::has('success')): ?>
        toastr.success(`<?php echo e(session('success')); ?>`);
    <?php endif; ?>
</script>

<script>
    function executeToBeDisabledSelections() {
        $("option:selected[value='to_be_disabled']").each(function(index, element) {
            $(element).attr({
                disabled: true,
                selected: true
            });
        });
    }
    executeToBeDisabledSelections();
</script>

<?php echo $__env->yieldPushContent('js'); ?>

<!--end::Page Scripts-->
</body>
<!--end::Body-->

</html>
<?php /**PATH /home3/cloudtal/cloudymenue.cloudy-digital.com/resources/views/layouts/includes/footer.blade.php ENDPATH**/ ?>