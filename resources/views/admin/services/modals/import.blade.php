<div class="modal fade effect-newspaper show" id="import_excels" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
     aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 style="font-family: 'Cairo', sans-serif;" class="modal-title" id="exampleModalLabel">
                    {{ __('admin.drag_and_drop') }}
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form style="display:flex;align-items :center ; gap :20px" action="{{ $import_route }}"
                      method="POST" enctype="multipart/form-data" id="submit_import_form">
                    @csrf
                    <div class="row mb-4">
                        <div class="col-sm-12 col-md-12" style="width: 478px;height: 182px">
                            <input type="file" name="file" class="dropify" id="import_excel"  accept=".xls,.xlsx, .csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer" style="justify-content: space-between !important;">
                <a download="/services_sample.xlsx" type="button" class="btn btn-warning" href="{{ asset('/storage/samples/services_sample.xlsx') }}"> <i class="bi bi-download"></i>
                    <i class="fas fa-download"></i>
                    {{ __('admin.sample') }}
                </a>
                <div class="btn">
                    <button type="button" id="import_excel_btn" class="btn btn-success">
                        {{ __('admin.save') }}
                    </button>
                    <button type="button" class="btn btn-secondary"
                        data-dismiss="modal">
                        {{ __('admin.close') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('js')
    <script>
        $(document).ready( function () {

            $(document).on('click', '#import_excel_btn', function (e) {
                let import_excel = $('#import_excel').val();
                if (import_excel == '' || import_excel == null) {
                    e.preventDefault();
                    Swal.fire("Cancelled", "Please Choose Excel File!", "warning");
                } else {
                    $('#submit_import_form').submit();
                }
            });
        })
    </script>
@endpush
