@extends("layouts.admin")

@section("content")
<div class="container-fluid">
    @include("layouts.includes.breadcrumb", [ 'page' => '╪│╪»╪º╪» ╪¡╪│╪º╪¿ - ' . $car->car_number ])

    <!--begin::Card-->
    <div class="card card-custom shadow-sm">
        <div class="card-header border-0 py-4">
            <div class="card-title">
                <h3 class="card-label font-weight-bolder text-dark">
                    <i class="fas fa-money-bill text-success mr-2"></i>
                    ╪│╪»╪º╪» ╪¡╪│╪º╪¿ - {{ $car->car_number }}
                </h3>
            </div>
            <div class="card-toolbar">
                <a href="{{ route('accounts.car.statement', $car->id) }}"
                   class="btn btn-primary font-weight-bold shadow-sm">
                    <i class="fas fa-file-invoice"></i> ┘â╪┤┘ü ╪º┘ä╪¡╪│╪º╪¿
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show m-3">
                {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show m-3">
                {{ session('error') }}
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        @endif

        @if(session('payment_group_uuid'))
            @php
                $receiptPrintUrl = route('accounts.car.statement.payment-receipt', [
                    'carId' => $car->id,
                    'group' => session('payment_group_uuid'),
                ]);
                $receiptIds = session('processed_shipments', []);
                $receiptAmounts = session('processed_shipment_amounts', []);
                $receiptPdfQuery = ['carId' => $car->id, 'shipment_ids' => implode(',', $receiptIds)];
                if (count($receiptAmounts) === count($receiptIds) && count($receiptIds) > 0) {
                    $receiptPdfQuery['amounts'] = implode(',', array_map(static fn ($v) => (string) (float) $v, $receiptAmounts));
                }
            @endphp
            <div class="mx-3 mb-0">
                <div class="card border-info shadow-sm">
                    <div class="card-header bg-info text-white font-weight-bold py-3">
                        <i class="fas fa-file-invoice mr-2"></i> ╪¿┘è╪º┘å ╪│╪»╪º╪» ┘å┘é┘ä╪º╪¬ (┘à╪╣╪º┘è┘å╪⌐ ┘ê╪╖╪¿╪º╪╣╪⌐ ΓÇö ┘å┘ü╪│ ╪┤┘â┘ä ┘â╪┤┘ü ╪º┘ä╪¡╪│╪º╪¿)
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">
                            ╪º┘ä┘à╪╣╪º┘è┘å╪⌐ ╪ú╪»┘å╪º┘ç ┘à╪╖╪º╪¿┘é╪⌐ ┘ä╪¿┘è╪º┘å ╪º┘ä╪│╪»╪º╪» ┘ü┘è ┘â╪┤┘ü ╪º┘ä╪¡╪│╪º╪¿. ╪º╪│╪¬╪«╪»┘à ┬½╪╖╪¿╪º╪╣╪⌐ ╪º┘ä╪¿┘è╪º┘å┬╗ ╪ú┘ê ┬½┘ü╪¬╪¡ ┘ü┘è ┘å╪º┘ü╪░╪⌐ ╪¼╪»┘è╪»╪⌐┬╗ ╪½┘à ╪╖╪¿╪º╪╣╪⌐ ┘à┘å ╪º┘ä┘à╪¬╪╡┘ü╪¡.
                        </p>
                        <iframe id="carPaymentPageReceiptIframe"
                                class="w-100 border rounded"
                                title="╪¿┘è╪º┘å ╪│╪»╪º╪» ┘å┘é┘ä╪º╪¬"
                                data-src="{{ $receiptPrintUrl }}"
                                style="height: 480px; min-height: 320px; background: #fff;"></iframe>
                        <div class="d-flex flex-wrap align-items-center mt-3">
                            <button type="button" class="btn btn-primary font-weight-bold js-print-car-payment-page-receipt ml-2 mb-2">
                                <i class="fas fa-print ml-1"></i> ╪╖╪¿╪º╪╣╪⌐ ╪º┘ä╪¿┘è╪º┘å
                            </button>
                            <a href="{{ $receiptPrintUrl }}"
                               class="btn btn-outline-primary font-weight-bold ml-2 mb-2"
                               target="_blank"
                               rel="noopener">
                                <i class="fas fa-external-link-alt ml-1"></i> ┘ü╪¬╪¡ ┘ü┘è ┘å╪º┘ü╪░╪⌐ ╪¼╪»┘è╪»╪⌐
                            </a>
                            @if(count($receiptIds) > 0)
                                <a href="{{ route('accounts.car.payment.export.pdf', $receiptPdfQuery) }}"
                                   class="btn btn-outline-danger font-weight-bold ml-2 mb-2">
                                    <i class="fas fa-file-pdf ml-1"></i> ╪¬╪¡┘à┘è┘ä PDF
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @elseif(session('processed_shipments'))
            @php
                $receiptIds = session('processed_shipments', []);
                $receiptAmounts = session('processed_shipment_amounts', []);
                $receiptPdfQuery = ['carId' => $car->id, 'shipment_ids' => implode(',', $receiptIds)];
                if (count($receiptAmounts) === count($receiptIds) && count($receiptIds) > 0) {
                    $receiptPdfQuery['amounts'] = implode(',', array_map(static fn ($v) => (string) (float) $v, $receiptAmounts));
                }
            @endphp
            <div class="alert alert-light border m-3 mb-0">
                <a href="{{ route('accounts.car.payment.export.pdf', $receiptPdfQuery) }}"
                   class="btn btn-danger font-weight-bold">
                    <i class="fas fa-file-pdf mr-2"></i> ╪╖╪¿╪º╪╣╪⌐ ╪¿┘è╪º┘å ╪º┘ä╪│╪»╪º╪» PDF
                </a>
            </div>
        @endif

        <div class="card-body">
            <!-- ┘à╪╣┘ä┘ê┘à╪º╪¬ ╪º┘ä╪│┘è╪º╪▒╪⌐ ┘ê╪º┘ä╪¡╪│╪º╪¿ -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5 class="font-weight-bold">┘à╪╣┘ä┘ê┘à╪º╪¬ ╪º┘ä╪│┘è╪º╪▒╪⌐</h5>
                    <p><strong>╪▒┘é┘à ╪º┘ä╪│┘è╪º╪▒╪⌐:</strong> {{ $car->car_number }}</p>
                </div>
                <div class="col-md-6">
                    <h5 class="font-weight-bold">┘à╪╣┘ä┘ê┘à╪º╪¬ ╪º┘ä╪¡╪│╪º╪¿</h5>
                    <p><strong>╪º┘ä╪▒╪╡┘è╪» ╪º┘ä┘à╪│╪¬╪¡┘é (┘å┘é┘ä╪º╪¬ ╪║┘è╪▒ ┘à╪│╪»╪»╪⌐):</strong>
                        <span class="text-danger font-weight-bold" style="font-size: 1.2em">
                            {{ number_format($currentBalance, 2) }} ╪¼┘å┘è┘ç
                        </span>
                    </p>
                    <p><strong>╪º┘ä╪▒╪╡┘è╪» ╪º┘ä┘å┘ç╪º╪ª┘è (┘à╪╖╪º╪¿┘é ┘ä┘â╪┤┘ü ╪º┘ä╪¡╪│╪º╪¿):</strong>
                        <span class="font-weight-bold {{ isset($finalBalance) && $finalBalance >= 0 ? 'text-danger' : 'text-success' }}" style="font-size: 1.1em">
                            {{ number_format($finalBalance ?? 0, 2) }} ╪¼┘å┘è┘ç
                        </span>
                    </p>
                </div>
            </div>

            <!-- ╪º┘ä┘å┘é┘ä╪º╪¬ ╪║┘è╪▒ ╪º┘ä┘à╪│╪»╪»╪⌐ -->
            @if($unpaidShipments->count() > 0)
                <div class="mb-4">
                    <h5 class="font-weight-bold mb-3">╪º┘ä┘å┘é┘ä╪º╪¬ ╪║┘è╪▒ ╪º┘ä┘à╪│╪»╪»╪⌐</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead style="background: linear-gradient(135deg, #DC143C 0%, #B22222 100%); color: #fff;">
                                <tr>
                                    <th>
                                        <input type="checkbox" id="select_all" title="╪¬╪¡╪»┘è╪» ╪º┘ä┘â┘ä">
                                    </th>
                                    <th>#</th>
                                    <th>╪▒┘é┘à ╪º┘ä╪¡╪º┘ê┘è╪⌐</th>
                                    <th>╪¬╪º╪▒┘è╪« ╪º┘ä┘å┘é┘ä╪⌐</th>
                                    <th>╪º┘ä╪¬┘â┘ä┘ü╪⌐</th>
                                    <th>╪º┘ä╪╣┘ç╪»╪⌐</th>
                                    <th>╪º┘ä┘à╪╡╪▒┘ê┘ü╪º╪¬ ╪º┘ä╪Ñ╪╢╪º┘ü┘è╪⌐</th>
                                    <th>╪º┘ä┘à╪│╪»╪»</th>
                                    <th>╪º┘ä┘à╪¬╪¿┘é┘è</th>
                                    <th>╪«╪▒┘ê╪¼</th>
                                    <th>╪¬╪¡┘à┘è┘ä</th>
                                    <th>╪¬╪│┘ä┘è┘à</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($unpaidShipments as $index => $shipment)
                                    <tr>
                                        <td>
                                            <input type="checkbox"
                                                   name="shipment_ids[]"
                                                   class="shipment-checkbox"
                                                   value="{{ $shipment['id'] }}"
                                                   data-remaining="{{ $shipment['remaining'] }}">
                                        </td>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $shipment['container_numbers'] ?: '-' }}</td>
                                        <td>{{ \Carbon\Carbon::parse($shipment['date'])->format('Y-m-d') }}</td>
                                        <td class="font-weight-bold">{{ number_format($shipment['cost'], 2) }} ╪¼.┘à</td>
                                        <td class="text-info">{{ number_format($shipment['financial_custody'], 2) }} ╪¼.┘à</td>
                                        <td class="text-warning">{{ number_format($shipment['extra_expenses'], 2) }} ╪¼.┘à</td>
                                        <td class="text-success">{{ number_format($shipment['paid'], 2) }} ╪¼.┘à</td>
                                        <td class="text-danger font-weight-bold">{{ number_format($shipment['remaining'], 2) }} ╪¼.┘à</td>
                                        <td>{{ $shipment['departure'] ?: '-' }}</td>
                                        <td>{{ $shipment['loading'] ?: '-' }}</td>
                                        <td>{{ $shipment['aging'] ?: '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="alert alert-info mt-3">
                        <strong>╪ú┘é╪╡┘ë ┘à╪¬╪¿┘é┘è ┘ä┘ä┘å┘é┘ä╪º╪¬ ╪º┘ä┘à╪¡╪»╪»╪⌐:</strong> <span id="selected_total" class="font-weight-bold">0.00</span> ╪¼.┘à
                        <span id="selected_count" class="ml-3">(0 ┘å┘é┘ä╪⌐)</span>
                        <div class="small text-muted mt-2">
                            ┘è┘à┘â┘å┘â ╪Ñ╪»╪«╪º┘ä ┘à╪¿┘ä╪║ ╪ú┘é┘ä ┘ü┘è ╪¡┘é┘ä ┬½╪º┘ä┘à╪¿┘ä╪║┬╗ ╪ú╪»┘å╪º┘ç ┘ä╪│╪»╪º╪» ╪¼╪▓╪ª┘è (╪¬┘ê╪▓┘è╪╣ ╪¬┘ä┘é╪º╪ª┘è ┘à┘å ╪º┘ä╪ú┘é╪»┘à ┘ä┘ä╪ú╪¡╪»╪½ ╪¿┘è┘å ╪º┘ä┘å┘é┘ä╪º╪¬ ╪º┘ä┘à╪¡╪»╪»╪⌐).
                        </div>
                    </div>
                </div>
            @else
                <div class="alert alert-warning">
                    ┘ä╪º ╪¬┘ê╪¼╪» ┘å┘é┘ä╪º╪¬ ╪║┘è╪▒ ┘à╪│╪»╪»╪⌐
                </div>
            @endif

            <!-- ┘å┘à┘ê╪░╪¼ ╪º┘ä╪│╪»╪º╪» -->
            <form action="{{ route('accounts.car.payment.process', $car->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="shipment_ids" id="shipment_ids_input" value="">

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold required-field">╪º┘ä┘à╪¿┘ä╪║ <span class="text-danger">*</span></label>
                            <input type="number"
                                   name="amount"
                                   id="amount_input"
                                   class="form-control @error('amount') is-invalid @enderror"
                                   step="0.01"
                                   min="0.01"
                                   value="{{ old('amount') }}"
                                   required>
                            @error('amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted" id="amount_hint">╪º╪«╪¬╪▒ ┘å┘é┘ä╪º╪¬╪º┘ï ┘ä╪╣╪▒╪╢ ╪ú┘é╪╡┘ë ┘à╪¿┘ä╪║ ┘è┘à┘â┘å ╪│╪»╪º╪»┘ç ┘à┘å┘ç╪º.</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold required-field">╪¬╪º╪▒┘è╪« ╪º┘ä╪│╪»╪º╪» <span class="text-danger">*</span></label>
                            <input type="date"
                                   name="payment_date"
                                   class="form-control @error('payment_date') is-invalid @enderror"
                                   value="{{ old('payment_date', date('Y-m-d')) }}"
                                   required>
                            @error('payment_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">┘à┘ä╪º╪¡╪╕╪º╪¬</label>
                            <textarea name="notes"
                                      class="form-control @error('notes') is-invalid @enderror"
                                      rows="3">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">╪╡┘ê╪▒╪⌐ ╪º┘ä╪│╪»╪º╪» (╪º╪«╪¬┘è╪º╪▒┘è)</label>
                            <input type="file"
                                   name="image"
                                   class="form-control @error('image') is-invalid @enderror"
                                   accept="image/*">
                            @error('image')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-success btn-lg font-weight-bold">
                            <i class="fas fa-check-circle mr-2"></i>
                            ╪¬╪│╪¼┘è┘ä ╪º┘ä╪│╪»╪º╪»
                        </button>
                        <a href="{{ route('accounts.car.statement', $car->id) }}" class="btn btn-secondary btn-lg font-weight-bold ml-2">
                            <i class="fas fa-times mr-2"></i>
                            ╪Ñ┘ä╪║╪º╪í
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@push('js')
<script>
    $(document).ready(function() {
        var $receiptIframe = $('#carPaymentPageReceiptIframe');
        if ($receiptIframe.length) {
            var src = $receiptIframe.data('src');
            if (src) {
                $receiptIframe.attr('src', src);
            }
        }

        $(document).on('click', '.js-print-car-payment-page-receipt', function () {
            var iframe = document.getElementById('carPaymentPageReceiptIframe');
            if (!iframe || !iframe.contentWindow) {
                return;
            }
            try {
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
            } catch (e) {
                var fallback = iframe.getAttribute('src');
                if (fallback) {
                    window.open(fallback, '_blank');
                }
            }
        });

        // ╪¬╪¡╪»┘è╪»/╪Ñ┘ä╪║╪º╪í ╪¬╪¡╪»┘è╪» ╪º┘ä┘â┘ä
        $('#select_all').on('change', function() {
            $('.shipment-checkbox').prop('checked', this.checked);
            updateSelectedTotal();
        });

        // ╪¬╪¡╪»┘è╪½ ╪º┘ä┘à╪¼┘à┘ê╪╣ ╪╣┘å╪» ╪¬╪¡╪»┘è╪»/╪Ñ┘ä╪║╪º╪í ╪¬╪¡╪»┘è╪» ┘å┘é┘ä╪⌐
        $('.shipment-checkbox').on('change', function() {
            updateSelectedTotal();
            // ╪¬╪¡╪»┘è╪½ ╪¡╪º┘ä╪⌐ "╪¬╪¡╪»┘è╪» ╪º┘ä┘â┘ä"
            var totalCheckboxes = $('.shipment-checkbox').length;
            var checkedCheckboxes = $('.shipment-checkbox:checked').length;
            $('#select_all').prop('checked', totalCheckboxes === checkedCheckboxes);
        });

        function updateSelectedTotal() {
            var total = 0;
            var count = 0;
            var selectedIds = [];

            $('.shipment-checkbox:checked').each(function() {
                var remaining = parseFloat($(this).data('remaining')) || 0;
                total += remaining;
                count++;
                selectedIds.push($(this).val());
            });

            $('#selected_total').text(total.toLocaleString('ar-EG', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            $('#selected_count').text('(' + count + ' ┘å┘é┘ä╪⌐)');
            $('#shipment_ids_input').val(selectedIds.join(','));
            if (total > 0) {
                $('#amount_input').val(parseFloat(total.toFixed(2)));
                $('#amount_hint').text('╪ú┘é╪╡┘ë ┘à╪¿┘ä╪║ ┘è┘à┘â┘å ╪│╪»╪º╪»┘ç ┘à┘å ╪º┘ä┘å┘é┘ä╪º╪¬ ╪º┘ä┘à╪¡╪»╪»╪⌐: ' + total.toLocaleString('ar-EG', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ╪¼.┘à (┘è┘à┘â┘å ╪¬┘é┘ä┘è┘ä ╪º┘ä┘à╪¿┘ä╪║ ┘ä╪│╪»╪º╪» ╪¼╪▓╪ª┘è).');
            } else {
                $('#amount_input').val('');
                $('#amount_hint').text('╪º╪«╪¬╪▒ ┘å┘é┘ä╪º╪¬╪º┘ï ┘ä╪╣╪▒╪╢ ╪ú┘é╪╡┘ë ┘à╪¿┘ä╪║ ┘è┘à┘â┘å ╪│╪»╪º╪»┘ç ┘à┘å┘ç╪º.');
            }
        }

        // ┘à┘å╪╣ ╪Ñ╪▒╪│╪º┘ä ╪º┘ä┘å┘à┘ê╪░╪¼ ╪¿╪»┘ê┘å ╪¬╪¡╪»┘è╪» ┘å┘é┘ä╪º╪¬ ╪ú┘ê ┘à╪¿┘ä╪║ ╪║┘è╪▒ ╪╡╪º┘ä╪¡
        $('form').on('submit', function(e) {
            var selectedIds = $('#shipment_ids_input').val();
            if (!selectedIds || selectedIds.trim() === '') {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: '╪«╪╖╪ú',
                    text: '┘è╪¼╪¿ ╪¬╪¡╪»┘è╪» ┘å┘é┘ä╪º╪¬ ╪╣┘ä┘ë ╪º┘ä╪ú┘é┘ä'
                });
                return false;
            }
            var maxTotal = 0;
            $('.shipment-checkbox:checked').each(function() {
                maxTotal += parseFloat($(this).data('remaining')) || 0;
            });
            var amount = parseFloat($('#amount_input').val());
            if (!amount || amount < 0.01) {
                e.preventDefault();
                Swal.fire({ icon: 'error', title: '╪«╪╖╪ú', text: '╪ú╪»╪«┘ä ┘à╪¿┘ä╪║╪º┘ï ╪╡╪¡┘è╪¡╪º┘ï (0.01 ╪╣┘ä┘ë ╪º┘ä╪ú┘é┘ä)' });
                return false;
            }
            if (amount - maxTotal > 0.009) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: '╪«╪╖╪ú',
                    text: '╪º┘ä┘à╪¿┘ä╪║ ╪ú┘â╪¿╪▒ ┘à┘å ╪Ñ╪¼┘à╪º┘ä┘è ╪º┘ä┘à╪¬╪¿┘é┘è ┘ä┘ä┘å┘é┘ä╪º╪¬ ╪º┘ä┘à╪¡╪»╪»╪⌐ (' + maxTotal.toFixed(2) + ' ╪¼.┘à)'
                });
                return false;
            }
        });
    });
</script>
@endpush

@endsection
