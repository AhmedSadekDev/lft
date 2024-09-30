<!--end::Global Config-->
<!--begin::Global Theme Bundle(used by all pages)-->
<script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
<script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>
<!--end::Global Theme Bundle-->

<script src="{{ asset('assets/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('assets/js/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ asset('assets/js/bootstrap-select.min.js') }}"></script>
<!-- =================== CDNs =================== -->
{{-- <script src="https://kit.fontawesome.com/c0f4bce580.js" crossorigin="anonymous"></script> --}}
<script src="{{ asset('assets/js/sweetalert.min.js') }}"></script>
<!-- Toaster Scripts --->
<script src="{{ asset('public/assets/admin/plugins/toastr/toastr.min.js') }}"></script>
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
        $('table').DataTable({
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

    @if (Session::has('error'))
        toastr.error(`{{ session('error') }}`);
    @elseif (Session::has('success'))
        toastr.success(`{{ session('success') }}`);
    @endif
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

@stack('js')

<!--end::Page Scripts-->
</body>
<!--end::Body-->

</html>
