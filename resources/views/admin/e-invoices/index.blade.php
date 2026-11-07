@extends('layouts.admin')

@push('css')
<style>
    .table-separate {
        border-collapse: separate;
        border-spacing: 0 10px;
    }
    .table-separate thead th {
        border: none;
        background-color: var(--surface-secondary) !important;
        color: var(--text-secondary) !important;
        font-weight: 700;
        padding: 15px 10px;
        font-size: 13px;
    }
    .table-separate tbody tr {
        background-color: var(--surface) !important;
        border: 1px solid var(--border) !important;
        border-radius: var(--radius-sm);
    }
    .table-separate tbody td {
        border: none;
        padding: 14px 10px;
        vertical-align: middle;
        color: var(--text-primary);
    }
    .e-inv-status-Valid { background: #e8f5e9; color: #2e7d32; }
    .e-inv-status-Invalid, .e-inv-status-Rejected, .e-inv-status-Failed { background: #ffebee; color: #c62828; }
    .e-inv-status-Processing { background: #fff8e1; color: #f9a825; }
    .uuid-cell { font-family: monospace; font-size: 12px; direction: ltr; text-align: left; }
</style>
@endpush

@section('content')
<div class="container-fluid lft-simple-page">
    @include('layouts.includes.breadcrumb', ['page' => 'الفواتير الإلكترونية'])

    <div class="card card-custom gutter-b lft-simple-card lft-simple-card--wide">
        <div class="card-header flex-wrap align-items-center py-5">
            <div class="card-title mb-0">
                <h3 class="card-label mb-0">الفواتير الإلكترونية (ETA)</h3>
            </div>
            <div class="card-toolbar">
                <button type="button" class="btn btn-light-primary mr-2" data-toggle="modal" data-target="#eInvoiceFilterModal">
                    <i class="fas fa-filter"></i> تصفية
                </button>
                <button type="button" class="btn btn-primary" id="btnOpenDownloadOptions">
                    <i class="fas fa-download"></i> خيارات التحميل
                </button>
            </div>
        </div>

        <div class="card-body">
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <form id="eInvoiceListFilters" method="get" action="{{ route('e-invoices.index') }}" class="mb-4">
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="بحث UUID / رقم حجز / submission">
                    </div>
                    <div class="col-md-3 mb-2">
                        <select name="company_id" class="form-control">
                            <option value="">كل الشركات</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}" @selected(request('company_id') == $company->id)>{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <select name="invoice_status" class="form-control">
                            <option value="">كل الحالات</option>
                            @foreach($statuses as $status)
                                <option value="{{ $status }}" @selected(request('invoice_status') == $status)>{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
                    </div>
                    <div class="col-md-2 mb-2">
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-2">
                    <div class="text-muted">إجمالي المطابق للفلتر: <strong>{{ $filteredCount }}</strong></div>
                    <div>
                        <a href="{{ route('e-invoices.index') }}" class="btn btn-light">إعادة تعيين</a>
                        <button type="submit" class="btn btn-primary">تطبيق</button>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-separate table-head-custom no-datatable" id="eInvoicesTable">
                    <thead>
                        <tr>
                            <th style="width:40px">
                                <label class="checkbox checkbox-single">
                                    <input type="checkbox" id="selectAllEInvoices">
                                    <span></span>
                                </label>
                            </th>
                            <th>الحجز</th>
                            <th>الشركة</th>
                            <th>UUID</th>
                            <th>Submission</th>
                            <th>الحالة</th>
                            <th>التاريخ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bookings as $booking)
                            <tr>
                                <td>
                                    <label class="checkbox checkbox-single">
                                        <input type="checkbox" class="e-inv-row-check" value="{{ $booking->id }}" data-uuid="{{ $booking->invoice_uuid }}">
                                        <span></span>
                                    </label>
                                </td>
                                <td>
                                    <div class="font-weight-bold">{{ $booking->booking_number ?? $booking->id }}</div>
                                    @if($booking->invoice)
                                        <small class="text-muted">{{ $booking->invoice->invoice_number }}</small>
                                    @endif
                                </td>
                                <td>{{ $booking->company?->name ?? '—' }}</td>
                                <td class="uuid-cell">{{ $booking->invoice_uuid }}</td>
                                <td class="uuid-cell">{{ $booking->submission_id ?? '—' }}</td>
                                <td>
                                    @php $st = $booking->invoice_status ?? '—'; @endphp
                                    <span class="badge e-inv-status-{{ $st }}">{{ $st }}</span>
                                </td>
                                <td>
                                    @if($booking->created_at)
                                        <span>{{ $booking->created_at->format('Y-m-d') }}</span>
                                        <div class="text-muted font-size-sm">{{ $booking->created_at->format('H:i') }}</div>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-10">
                                    لا توجد فواتير إلكترونية مسجّلة (invoice_uuid) بعد.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $bookings->links() }}
            </div>
        </div>
    </div>
</div>

{{-- Simple filter modal (same fields, alternate entry) --}}
<div class="modal fade" id="eInvoiceFilterModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="get" action="{{ route('e-invoices.index') }}">
                <div class="modal-header">
                    <h5 class="modal-title">تصفية الفواتير الإلكترونية</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>بحث</label>
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>الشركة</label>
                        <select name="company_id" class="form-control">
                            <option value="">كل الشركات</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}" @selected(request('company_id') == $company->id)>{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>الحالة</label>
                        <select name="invoice_status" class="form-control">
                            <option value="">كل الحالات</option>
                            @foreach($statuses as $status)
                                <option value="{{ $status }}" @selected(request('invoice_status') == $status)>{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>من</label>
                            <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
                        </div>
                        <div class="form-group col-md-6">
                            <label>إلى</label>
                            <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">إغلاق</button>
                    <button type="submit" class="btn btn-primary">تطبيق</button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('admin.e-invoices._download-options-modal')

<form id="eInvoiceExportForm" method="post" action="{{ route('e-invoices.export') }}" class="d-none" target="_blank">
    @csrf
    <input type="hidden" name="format" id="exportFormat">
    <input type="hidden" name="excel_type" id="exportExcelType">
    <input type="hidden" name="use_all_filtered" id="exportUseAll" value="0">
    <input type="hidden" name="uuids" id="exportUuids">
    <div id="exportBookingIds"></div>
    <div id="exportFilenameFields"></div>
    <div id="exportExcelFields"></div>
    <div id="exportFilterFields"></div>
</form>
@endsection

@push('js')
<script>
(function () {
    var PREFS_KEY = 'leader.eInvoice.downloadPrefs.v1';
    var exportUrl = @json(route('e-invoices.export'));
    var lookupUrl = @json(route('e-invoices.lookup-uuid'));
    var taxpayerUrl = @json(route('e-invoices.taxpayer-search'));

    function selectedBookingIds() {
        return Array.from(document.querySelectorAll('.e-inv-row-check:checked')).map(function (el) {
            return el.value;
        });
    }

    function collectOptions() {
        var filename = {};
        var excel = {};
        document.querySelectorAll('[data-opt]').forEach(function (el) {
            var path = el.getAttribute('data-opt');
            var checked = !!el.checked;
            if (path.indexOf('filename.') === 0) {
                filename[path.replace('filename.', '')] = checked;
            } else if (path.indexOf('excel.') === 0) {
                excel[path.replace('excel.', '')] = checked;
            }
        });
        return {
            filename: filename,
            excel: excel,
            use_all_filtered: !!(document.getElementById('downloadAllFiltered') || {}).checked
        };
    }

    function savePrefs() {
        try { localStorage.setItem(PREFS_KEY, JSON.stringify(collectOptions())); } catch (e) {}
    }

    function loadPrefs() {
        try {
            var raw = localStorage.getItem(PREFS_KEY);
            if (!raw) return;
            var prefs = JSON.parse(raw);
            document.querySelectorAll('[data-opt]').forEach(function (el) {
                var path = el.getAttribute('data-opt');
                var parts = path.split('.');
                var group = parts[0];
                var key = parts[1];
                if (prefs[group] && typeof prefs[group][key] !== 'undefined') {
                    el.checked = !!prefs[group][key];
                }
            });
            if (typeof prefs.use_all_filtered !== 'undefined') {
                var all = document.getElementById('downloadAllFiltered');
                if (all) all.checked = !!prefs.use_all_filtered;
            }
        } catch (e) {}
    }

    function appendHidden(container, name, value) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;
        container.appendChild(input);
    }

    function fillNestedFields(container, prefix, obj) {
        container.innerHTML = '';
        Object.keys(obj || {}).forEach(function (key) {
            appendHidden(container, prefix + '[' + key + ']', obj[key] ? '1' : '0');
        });
    }

    function currentFilters() {
        return {
            search: @json(request('search')),
            company_id: @json(request('company_id')),
            invoice_status: @json(request('invoice_status')),
            date_from: @json(request('date_from')),
            date_to: @json(request('date_to'))
        };
    }

    function submitExport(format, excelType) {
        var opts = collectOptions();
        savePrefs();

        var uuidTabActive = document.querySelector('#tabUuidDownload.active, a[href="#tabUuidDownload"].active');
        var uuidText = (document.getElementById('uuidListInput') || {}).value || '';
        var usingUuidTab = !!(document.querySelector('#tabUuidDownload.show') || document.querySelector('#tabUuidDownload.active'));

        // Bootstrap 4 tab panes use .active.show
        usingUuidTab = document.getElementById('tabUuidDownload').classList.contains('active');

        var form = document.getElementById('eInvoiceExportForm');
        form.action = usingUuidTab && uuidText.trim() ? lookupUrl : exportUrl;
        document.getElementById('exportFormat').value = format;
        document.getElementById('exportExcelType').value = excelType || '';
        document.getElementById('exportUseAll').value = opts.use_all_filtered ? '1' : '0';
        document.getElementById('exportUuids').value = usingUuidTab ? uuidText.trim() : '';

        var idsWrap = document.getElementById('exportBookingIds');
        idsWrap.innerHTML = '';
        if (!usingUuidTab && !opts.use_all_filtered) {
            var ids = selectedBookingIds();
            if (!ids.length) {
                alert('اختر فاتورة واحدة على الأقل، أو فعّل «تحميل كل الصفحات»، أو استخدم تاب UUID.');
                return;
            }
            ids.forEach(function (id) {
                appendHidden(idsWrap, 'booking_ids[]', id);
            });
        }

        fillNestedFields(document.getElementById('exportFilenameFields'), 'filename', opts.filename);
        fillNestedFields(document.getElementById('exportExcelFields'), 'excel', opts.excel);

        var filtersWrap = document.getElementById('exportFilterFields');
        filtersWrap.innerHTML = '';
        var filters = currentFilters();
        Object.keys(filters).forEach(function (key) {
            if (filters[key]) {
                appendHidden(filtersWrap, 'filters[' + key + ']', filters[key]);
            }
        });

        form.submit();
    }

    document.getElementById('selectAllEInvoices')?.addEventListener('change', function () {
        document.querySelectorAll('.e-inv-row-check').forEach(function (el) {
            el.checked = document.getElementById('selectAllEInvoices').checked;
        });
    });

    document.getElementById('btnOpenDownloadOptions')?.addEventListener('click', function () {
        loadPrefs();
        $('#eInvoiceDownloadModal').modal('show');
    });

    document.querySelectorAll('[data-opt]').forEach(function (el) {
        el.addEventListener('change', savePrefs);
    });

    document.querySelectorAll('.e-inv-export-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            submitExport(btn.getAttribute('data-format'), btn.getAttribute('data-excel-type'));
        });
    });

    document.getElementById('btnTaxpayerSearch')?.addEventListener('click', function () {
        var q = (document.getElementById('taxpayerQuery') || {}).value || '';
        var msg = document.getElementById('taxpayerSearchMessage');
        var tbody = document.querySelector('#taxpayerResultsTable tbody');
        msg.classList.remove('d-none', 'alert-success', 'alert-danger', 'alert-light');
        msg.classList.add('alert-light');
        msg.textContent = 'جاري البحث...';
        tbody.innerHTML = '';

        fetch(taxpayerUrl + '?q=' + encodeURIComponent(q), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); })
          .then(function (res) {
              if (!res.ok || !res.data.status) {
                  msg.classList.remove('alert-light');
                  msg.classList.add('alert-danger');
                  msg.textContent = (res.data && res.data.message) ? res.data.message : 'فشل البحث';
                  return;
              }
              msg.classList.remove('alert-light');
              msg.classList.add('alert-success');
              msg.textContent = 'تم جلب النتائج';

              var items = [];
              var data = res.data.data;
              if (Array.isArray(data)) items = data;
              else if (data && Array.isArray(data.result)) items = data.result;
              else if (data && Array.isArray(data.taxpayers)) items = data.taxpayers;
              else if (data && typeof data === 'object') items = [data];

              if (!items.length) {
                  tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">لا توجد نتائج</td></tr>';
                  return;
              }

              items.forEach(function (item) {
                  var name = item.name || item.Name || item.taxpayerName || '—';
                  var tin = item.rin || item.Rin || item.taxId || item.id || item.Tin || '—';
                  var extra = item.address || item.Address || JSON.stringify(item).slice(0, 120);
                  var tr = document.createElement('tr');
                  tr.innerHTML = '<td>' + name + '</td><td dir="ltr">' + tin + '</td><td><small>' + extra + '</small></td>';
                  tbody.appendChild(tr);
              });
          }).catch(function () {
              msg.classList.remove('alert-light');
              msg.classList.add('alert-danger');
              msg.textContent = 'تعذر الاتصال بخدمة البحث';
          });
    });

    loadPrefs();
})();
</script>
@endpush
