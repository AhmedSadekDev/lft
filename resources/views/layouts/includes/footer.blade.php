<!--begin::Global Config-->
<script>
    var KTAppSettings = {
        "breakpoints": { "sm": 576, "md": 768, "lg": 992, "xl": 1200, "xxl": 1400 },
        "colors": {
            "theme": {
                "base": { "white": "#ffffff", "primary": "#1E4DAE", "secondary": "#E5EAEE", "success": "#1BC5BD", "info": "#0F75D2", "warning": "#FFA800", "danger": "#F64E60", "light": "#E4E6EF", "dark": "#181C32" },
                "light": { "white": "#ffffff", "primary": "#E8F0FC", "secondary": "#EBEDF3", "success": "#C9F7F5", "info": "#E0F0FF", "warning": "#FFF4DE", "danger": "#FFE2E5", "light": "#F3F6F9", "dark": "#D6D6E0" },
                "inverse": { "white": "#ffffff", "primary": "#ffffff", "secondary": "#3F4254", "success": "#ffffff", "info": "#ffffff", "warning": "#ffffff", "danger": "#ffffff", "light": "#464E5F", "dark": "#ffffff" }
            },
            "gray": { "gray-100": "#F3F6F9", "gray-200": "#EBEDF3", "gray-300": "#E4E6EF", "gray-400": "#D1D3E0", "gray-500": "#B5B5C3", "gray-600": "#7E8299", "gray-700": "#5E6278", "gray-800": "#3F4254", "gray-900": "#181C32" }
        },
        "font-family": "Poppins"
    };
</script>
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
<script>
    if (typeof toastr !== 'undefined') {
        toastr.options = {
            "closeButton": true,
            "debug": false,
            "newestOnTop": true,
            "progressBar": true,
            "positionClass": "toast-top-center",
            "preventDuplicates": true,
            "onclick": null,
            "showDuration": "300",
            "hideDuration": "1000",
            "timeOut": "4500",
            "extendedTimeOut": "1500",
            "showEasing": "swing",
            "hideEasing": "linear",
            "showMethod": "fadeIn",
            "hideMethod": "fadeOut",
            "rtl": true
        };
    }

    window.lftFlashAlert = function (type, message) {
        if (!message) {
            return;
        }

        if (typeof Swal !== 'undefined' && typeof Swal.fire === 'function') {
            Swal.fire({
                icon: type === 'error' ? 'error' : 'success',
                title: message,
                confirmButtonText: 'حسناً',
                confirmButtonColor: type === 'error' ? '#F64E60' : '#1BC5BD',
                timer: type === 'error' ? undefined : 2800,
                timerProgressBar: type !== 'error',
                allowOutsideClick: true,
                customClass: {
                    popup: 'lft-flash-popup',
                    title: 'lft-flash-title',
                    confirmButton: 'lft-flash-confirm'
                }
            });
            return;
        }

        if (typeof toastr !== 'undefined') {
            if (type === 'error') {
                toastr.error(message);
            } else {
                toastr.success(message);
            }
        }
    };
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

    @if (Session::has('error'))
        window.lftFlashAlert('error', @json(session('error')));
    @elseif (Session::has('success'))
        window.lftFlashAlert('success', @json(session('success')));
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
<script src="{{ asset('assets/js/admin-ui.js') }}?v=5n"></script>

<!--end::Page Scripts-->
</body>
<!--end::Body-->

</html>
