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
</style>
@endpush

@section('content')
<div class="container-fluid lft-simple-page">
    @include('layouts.includes.breadcrumb', ['page' => 'الفواتير الإلكترونية'])

    <div class="card card-custom gutter-b lft-simple-card lft-simple-card--wide">
        <div class="card-header flex-wrap align-items-center py-5">
            <div class="card-title mb-0">
                <h3 class="card-label mb-0">فواتير اللوحة</h3>
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
                    <div class="col-md-4 mb-2">
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="بحث رقم فاتورة / رقم حجز">
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
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
                    </div>
                    <div class="col-md-2 mb-2">
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
                    </div>
                    <div class="col-md-1 mb-2">
                        <button type="submit" class="btn btn-primary btn-block">تطبيق</button>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-2">
                    <div class="text-muted">إجمالي المطابق للفلتر: <strong>{{ $filteredCount }}</strong></div>
                    <a href="{{ route('e-invoices.index') }}" class="btn btn-light btn-sm">إعادة تعيين</a>
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
                            <th>رقم الفاتورة</th>
                            <th>الحجز</th>
                            <th>الشركة</th>
                            <th>قبل الضريبة</th>
                            <th>الإجمالي</th>
                            <th>المدفوع</th>
                            <th>المتبقي</th>
                            <th>التاريخ</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $invoice)
                            @php
                                $beforeTax = (float) ($invoice->invoice_total_before_tax ?? 0);
                                $total = (float) ($invoice->invoice_total_after_tax ?? $beforeTax);
                                $paid = (float) ($invoice->invoicePayments->sum('value') ?? 0);
                                $remaining = round($total - $paid, 2);
                            @endphp
                            <tr>
                                <td>
                                    <label class="checkbox checkbox-single">
                                        <input type="checkbox" class="e-inv-row-check" value="{{ $invoice->id }}" data-number="{{ $invoice->invoice_number }}">
                                        <span></span>
                                    </label>
                                </td>
                                <td>
                                    <span class="font-weight-bold">{{ $invoice->invoice_number }}</span>
                                </td>
                                <td>{{ $invoice->booking?->booking_number ?? $invoice->booking_id }}</td>
                                <td>{{ $invoice->booking?->company?->name ?? '—' }}</td>
                                <td>{{ number_format($beforeTax, 2) }}</td>
                                <td>{{ number_format($total, 2) }}</td>
                                <td>{{ number_format($paid, 2) }}</td>
                                <td>
                                    @if($remaining > 0)
                                        <span class="text-warning font-weight-bold">{{ number_format($remaining, 2) }}</span>
                                    @else
                                        <span class="text-success font-weight-bold">{{ number_format($remaining, 2) }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($invoice->created_at)
                                        <span>{{ $invoice->created_at->format('Y-m-d') }}</span>
                                        <div class="text-muted font-size-sm">{{ $invoice->created_at->format('H:i') }}</div>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('booking-invoices.show', $invoice->id) }}" class="btn btn-sm btn-light-primary" target="_blank">
                                        عرض
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-10">
                                    لا توجد فواتير منشأة في اللوحة بعد.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $invoices->links() }}
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="eInvoiceFilterModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="get" action="{{ route('e-invoices.index') }}">
                <div class="modal-header">
                    <h5 class="modal-title">تصفية فواتير اللوحة</h5>
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
@endsection

@push('js')
<script>
(function () {
    var PREFS_KEY = 'leader.eInvoice.downloadPrefs.v2';
    var exportUrl = @json(route('e-invoices.export'));
    var lookupUrl = @json(route('e-invoices.lookup-uuid'));
    var taxpayerUrl = @json(route('e-invoices.taxpayer-search'));
    var csrf = @json(csrf_token());
    var PDF_MAX = 40;

    function selectedInvoiceIds() {
        return Array.from(document.querySelectorAll('.e-inv-row-check:checked')).map(function (el) {
            return el.value;
        });
    }

    function collectOptions() {
        var filename = {};
        var excel = {};
        document.querySelectorAll('[data-opt]').forEach(function (el) {
            var path = el.getAttribute('data-opt') || '';
            if (path.indexOf('filename.') === 0) filename[path.slice(9)] = !!el.checked;
            else if (path.indexOf('excel.') === 0) excel[path.slice(6)] = !!el.checked;
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
                var path = (el.getAttribute('data-opt') || '').split('.');
                if (path.length < 2) return;
                if (prefs[path[0]] && typeof prefs[path[0]][path[1]] !== 'undefined') {
                    el.checked = !!prefs[path[0]][path[1]];
                }
            });
            if (typeof prefs.use_all_filtered !== 'undefined') {
                var all = document.getElementById('downloadAllFiltered');
                if (all) all.checked = !!prefs.use_all_filtered;
            }
        } catch (e) {}
    }

    function currentFilters() {
        return {
            search: @json(request('search')),
            company_id: @json(request('company_id')),
            date_from: @json(request('date_from')),
            date_to: @json(request('date_to'))
        };
    }

    function showAlert(type, message) {
        var el = document.getElementById('eInvExportAlert');
        if (!el) { alert(message); return; }
        el.className = 'alert alert-' + type;
        el.textContent = message;
        el.classList.remove('d-none');
    }

    function clearAlert() {
        var el = document.getElementById('eInvExportAlert');
        if (!el) return;
        el.className = 'alert d-none mb-4';
        el.textContent = '';
    }

    function setBusy(on) {
        var el = document.getElementById('eInvExportBusy');
        if (!el) return;
        el.classList.toggle('d-none', !on);
    }

    function appendBoolMap(fd, prefix, obj) {
        Object.keys(obj || {}).forEach(function (key) {
            fd.append(prefix + '[' + key + ']', obj[key] ? '1' : '0');
        });
    }

    function filenameFromDisposition(header, fallback) {
        if (!header) return fallback;
        var m = /filename\*=UTF-8''([^;]+)|filename=\"?([^\";]+)\"?/i.exec(header);
        var raw = decodeURIComponent((m && (m[1] || m[2])) || fallback);
        return raw;
    }

    function downloadBlob(blob, filename) {
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        a.remove();
        setTimeout(function () { URL.revokeObjectURL(url); }, 1500);
    }

    function submitExport(format, excelType) {
        clearAlert();
        var opts = collectOptions();
        savePrefs();

        var numbersTab = document.getElementById('tabUuidDownload');
        var usingNumbersTab = numbersTab && numbersTab.classList.contains('active');
        var numbersText = ((document.getElementById('uuidListInput') || {}).value || '').trim();
        var ids = selectedInvoiceIds();

        if (!usingNumbersTab && !opts.use_all_filtered && !ids.length) {
            showAlert('warning', 'اختر فاتورة واحدة على الأقل، أو فعّل «تحميل كل الصفحات»، أو استخدم تاب أرقام الفواتير.');
            return;
        }

        if (format === 'pdf') {
            var estimated = opts.use_all_filtered
                ? parseInt((document.getElementById('filteredInvoiceCount') || {}).textContent || '0', 10)
                : (usingNumbersTab ? numbersText.split(/[\s,;]+/).filter(Boolean).length : ids.length);
            if (estimated > PDF_MAX) {
                showAlert('warning', 'PDF بحد أقصى ' + PDF_MAX + ' فاتورة. اختر عدد أقل أو استخدم Excel / قاعدة البيانات.');
                return;
            }
        }

        var fd = new FormData();
        fd.append('_token', csrf);
        fd.append('format', format);
        if (excelType) fd.append('excel_type', excelType);
        fd.append('use_all_filtered', opts.use_all_filtered ? '1' : '0');
        appendBoolMap(fd, 'filename', opts.filename);
        appendBoolMap(fd, 'excel', opts.excel);

        var filters = currentFilters();
        Object.keys(filters).forEach(function (key) {
            if (filters[key]) fd.append('filters[' + key + ']', filters[key]);
        });

        if (usingNumbersTab && numbersText) {
            fd.append('invoice_numbers', numbersText);
        } else if (!opts.use_all_filtered) {
            ids.forEach(function (id) { fd.append('invoice_ids[]', id); });
        }

        var url = (usingNumbersTab && numbersText) ? lookupUrl : exportUrl;
        setBusy(true);

        fetch(url, {
            method: 'POST',
            body: fd,
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-Export-Fetch': '1',
                'Accept': format === 'table' ? 'text/html' : 'application/octet-stream'
            }
        }).then(async function (res) {
            var type = (res.headers.get('Content-Type') || '').toLowerCase();
            if (!res.ok) {
                if (type.indexOf('application/json') !== -1) {
                    var err = await res.json();
                    throw new Error(err.message || 'فشل التصدير');
                }
                var text = await res.text();
                throw new Error('فشل التصدير (HTTP ' + res.status + ')');
            }

            if (format === 'table' || type.indexOf('text/html') !== -1) {
                var html = await res.text();
                var w = window.open('', '_blank');
                if (w) {
                    w.document.open();
                    w.document.write(html);
                    w.document.close();
                } else {
                    showAlert('warning', 'اسمح بالنوافذ المنبثقة لمعاينة TABLE');
                }
                return;
            }

            var blob = await res.blob();
            var name = filenameFromDisposition(
                res.headers.get('Content-Disposition'),
                'export-' + format + '-' + Date.now()
            );
            downloadBlob(blob, name);
            showAlert('success', 'تم تجهيز الملف بنجاح');
        }).catch(function (e) {
            showAlert('danger', e.message || 'حدث خطأ أثناء التصدير');
        }).finally(function () {
            setBusy(false);
        });
    }

    document.getElementById('selectAllEInvoices') && document.getElementById('selectAllEInvoices').addEventListener('change', function () {
        var checked = document.getElementById('selectAllEInvoices').checked;
        document.querySelectorAll('.e-inv-row-check').forEach(function (el) { el.checked = checked; });
    });

    function setExcelPanel(open) {
        var panel = document.getElementById('eInvExcelPanel');
        if (!panel) return;
        panel.classList.toggle('d-none', !open);
    }

    document.getElementById('btnOpenDownloadOptions') && document.getElementById('btnOpenDownloadOptions').addEventListener('click', function () {
        clearAlert();
        setExcelPanel(false);
        loadPrefs();
        $('#eInvoiceDownloadModal').modal('show');
    });

    document.getElementById('btnToggleExcelPanel') && document.getElementById('btnToggleExcelPanel').addEventListener('click', function () {
        var panel = document.getElementById('eInvExcelPanel');
        setExcelPanel(panel && panel.classList.contains('d-none'));
    });

    document.getElementById('btnCloseExcelPanel') && document.getElementById('btnCloseExcelPanel').addEventListener('click', function () {
        setExcelPanel(false);
    });

    document.querySelectorAll('[data-opt]').forEach(function (el) {
        el.addEventListener('change', savePrefs);
    });

    document.querySelectorAll('.e-inv-export-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var format = btn.getAttribute('data-format');
            if (format === 'excel') setExcelPanel(false);
            submitExport(format, btn.getAttribute('data-excel-type'));
        });
    });

    document.getElementById('btnTaxpayerSearch') && document.getElementById('btnTaxpayerSearch').addEventListener('click', function () {
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
              var items = (res.data.data && res.data.data.result) ? res.data.data.result : [];
              if (!items.length) {
                  tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">لا توجد نتائج</td></tr>';
                  return;
              }
              items.forEach(function (item) {
                  var tr = document.createElement('tr');
                  tr.innerHTML = '<td>' + (item.name || '—') + '</td><td dir="ltr">' + (item.taxId || '—') + '</td><td><small>' + (item.address || '') + '</small></td>';
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
