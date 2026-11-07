{{-- Download options modal for ETA e-invoices --}}
<div class="modal fade" id="eInvoiceDownloadModal" tabindex="-1" role="dialog" aria-labelledby="eInvoiceDownloadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content e-invoice-download-modal">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-weight-bold" id="eInvoiceDownloadModalLabel">خيارات التحميل</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <ul class="nav nav-tabs nav-tabs-line nav-tabs-line-2x px-5 pt-2" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-toggle="tab" href="#tabDownloadOptions" role="tab">خيارات التحميل</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#tabUuidDownload" role="tab">تحميل بـ UUID</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#tabTaxpayerSearch" role="tab">بحث عن ممول</a>
                </li>
            </ul>

            <div class="modal-body">
                <div class="tab-content">
                    {{-- Tab 1 --}}
                    <div class="tab-pane fade show active" id="tabDownloadOptions" role="tabpanel">
                        <div class="row">
                            <div class="col-lg-6 mb-4">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-header e-inv-header-blue py-3">
                                        <strong>خيارات اسم الملف - PDF</strong>
                                    </div>
                                    <div class="card-body">
                                        <div class="row" id="pdfFilenameOptions">
                                            <div class="col-md-6">
                                                <label class="checkbox mb-3"><input type="checkbox" data-opt="filename.internal_id" checked> <span></span> المعرف الداخلي</label>
                                                <label class="checkbox mb-3"><input type="checkbox" data-opt="filename.electronic_id" checked> <span></span> المعرف الإلكتروني</label>
                                                <label class="checkbox mb-3"><input type="checkbox" data-opt="filename.invoice_date"> <span></span> تاريخ الفاتورة</label>
                                                <label class="checkbox mb-3"><input type="checkbox" data-opt="filename.document_type"> <span></span> نوع المستند</label>
                                                <label class="checkbox mb-3"><input type="checkbox" data-opt="filename.folder_per_seller"> <span></span> مجلد لكل بائع</label>
                                                <label class="checkbox mb-3"><input type="checkbox" data-opt="filename.folder_per_buyer"> <span></span> مجلد لكل مشتري</label>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="checkbox mb-3"><input type="checkbox" data-opt="filename.seller_name"> <span></span> اسم البائع</label>
                                                <label class="checkbox mb-3"><input type="checkbox" data-opt="filename.seller_tax"> <span></span> الرقم الضريبي للبائع</label>
                                                <label class="checkbox mb-3"><input type="checkbox" data-opt="filename.buyer_name"> <span></span> اسم المشتري</label>
                                                <label class="checkbox mb-3"><input type="checkbox" data-opt="filename.buyer_tax"> <span></span> الرقم الضريبي للمشتري</label>
                                                <label class="checkbox mb-3"><input type="checkbox" data-opt="filename.po_reference"> <span></span> مرجع أمر الشراء</label>
                                                <label class="checkbox mb-3"><input type="checkbox" data-opt="filename.so_reference"> <span></span> مرجع أمر البيع</label>
                                            </div>
                                        </div>
                                        <div class="border rounded p-3 mt-2 bg-light">
                                            <strong class="d-block mb-2">خيارات التحميل</strong>
                                            <label class="checkbox mb-0">
                                                <input type="checkbox" id="downloadAllFiltered" data-opt="use_all_filtered">
                                                <span></span>
                                                تحميل كل الصفحات —
                                                <span id="filteredInvoiceCount">{{ $filteredCount ?? 0 }}</span>
                                                فاتورة
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-6 mb-4">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-header e-inv-header-green py-3">
                                        <strong>أعمدة ملف الإكسيل</strong>
                                    </div>
                                    <div class="card-body" id="excelColumnOptions">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label class="checkbox mb-3"><input type="checkbox" data-opt="excel.document_type" checked> <span></span> نوع المستند</label>
                                                <label class="checkbox mb-3"><input type="checkbox" data-opt="excel.document_version"> <span></span> إصدار المستند</label>
                                                <label class="checkbox mb-3"><input type="checkbox" data-opt="excel.status" checked> <span></span> الحالة</label>
                                                <label class="checkbox mb-3"><input type="checkbox" data-opt="excel.date_issued" checked> <span></span> تاريخ الإصدار</label>
                                                <label class="checkbox mb-3"><input type="checkbox" data-opt="excel.date_received" checked> <span></span> تاريخ الاستلام</label>
                                                <label class="checkbox mb-3"><input type="checkbox" data-opt="excel.seller_data" checked> <span></span> بيانات البائع</label>
                                                <label class="checkbox mb-3"><input type="checkbox" data-opt="excel.buyer_data" checked> <span></span> بيانات المشتري</label>
                                                <label class="checkbox mb-3"><input type="checkbox" data-opt="excel.electronic_id" checked> <span></span> المعرف الإلكتروني</label>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="checkbox mb-3"><input type="checkbox" data-opt="excel.signature"> <span></span> التوقيع الإلكتروني</label>
                                                <label class="checkbox mb-3"><input type="checkbox" data-opt="excel.external_link"> <span></span> الرابط الخارجي</label>
                                                <label class="checkbox mb-3"><input type="checkbox" data-opt="excel.po_reference"> <span></span> مرجع أمر الشراء</label>
                                                <label class="checkbox mb-3"><input type="checkbox" data-opt="excel.so_reference"> <span></span> مرجع أمر البيع</label>
                                                <label class="checkbox mb-3"><input type="checkbox" data-opt="excel.tax_columns" checked> <span></span> أعمدة الضريبة</label>
                                                <label class="checkbox mb-3"><input type="checkbox" data-opt="excel.discount_columns" checked> <span></span> أعمدة الخصم</label>
                                                <label class="checkbox mb-3"><input type="checkbox" data-opt="excel.group_items" checked> <span></span> تجميع البنود في صفحة واحدة</label>
                                                <label class="checkbox mb-3"><input type="checkbox" data-opt="excel.credit_note_negative" checked> <span></span> تحويل مبلغ إشعار الدائن لسالب</label>
                                                <label class="checkbox mb-3"><input type="checkbox" data-opt="excel.ignore_rejected"> <span></span> تجاهل المرفوض والملغي</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Tab 2 --}}
                    <div class="tab-pane fade" id="tabUuidDownload" role="tabpanel">
                        <div class="form-group">
                            <label for="uuidListInput">أدخل معرفات UUID (سطر لكل معرف أو مفصولة بفاصلة)</label>
                            <textarea id="uuidListInput" class="form-control" rows="8" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"></textarea>
                        </div>
                        <p class="text-muted mb-0">سيتم استخدام نفس خيارات اسم الملف وأعمدة الإكسيل من التاب الأول عند التصدير.</p>
                    </div>

                    {{-- Tab 3 --}}
                    <div class="tab-pane fade" id="tabTaxpayerSearch" role="tabpanel">
                        <div class="form-row align-items-end">
                            <div class="form-group col-md-8">
                                <label for="taxpayerQuery">الرقم الضريبي أو الاسم</label>
                                <input type="text" id="taxpayerQuery" class="form-control" placeholder="ابحث عن ممول...">
                            </div>
                            <div class="form-group col-md-4">
                                <button type="button" class="btn btn-primary btn-block" id="btnTaxpayerSearch">
                                    <i class="fas fa-search"></i> بحث
                                </button>
                            </div>
                        </div>
                        <div id="taxpayerSearchMessage" class="alert alert-light d-none"></div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered" id="taxpayerResultsTable">
                                <thead>
                                    <tr>
                                        <th>الاسم</th>
                                        <th>الرقم الضريبي</th>
                                        <th>تفاصيل</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer flex-wrap justify-content-between e-inv-actions">
                <button type="button" class="btn btn-danger btn-lg mb-2" data-dismiss="modal">CLOSE</button>
                <div class="d-flex flex-wrap">
                    <button type="button" class="btn btn-warning btn-lg mb-2 ml-2 e-inv-export-btn" data-format="database">
                        <i class="fas fa-database"></i> قاعدة البيانات
                    </button>
                    <button type="button" class="btn btn-lg mb-2 ml-2 e-inv-export-btn" data-format="table" style="background:#6f42c1;color:#fff;">
                        <i class="fas fa-table"></i> TABLE
                    </button>
                    <button type="button" class="btn btn-secondary btn-lg mb-2 ml-2 e-inv-export-btn" data-format="json_xml">
                        <i class="fas fa-file-code"></i> JSON/XML
                    </button>
                    <button type="button" class="btn btn-primary btn-lg mb-2 ml-2 e-inv-export-btn" data-format="pdf">
                        <i class="fas fa-file-pdf"></i> PDF
                    </button>
                    <div class="btn-group dropup mb-2 ml-2">
                        <button type="button" class="btn btn-success btn-lg dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-file-excel"></i> EXCEL
                        </button>
                        <div class="dropdown-menu dropdown-menu-right shadow">
                            <a class="dropdown-item e-inv-export-btn" href="javascript:;" data-format="excel" data-excel-type="comprehensive">تقرير شامل</a>
                            <a class="dropdown-item e-inv-export-btn" href="javascript:;" data-format="excel" data-excel-type="transactions">بيان تعاملات <span class="badge badge-danger">New</span></a>
                            <a class="dropdown-item e-inv-export-btn" href="javascript:;" data-format="excel" data-excel-type="vat_sales">تقرير مبيعات قيمة مضافة</a>
                            <a class="dropdown-item e-inv-export-btn" href="javascript:;" data-format="excel" data-excel-type="vat_purchases">تقرير مشتريات قيمة مضافة</a>
                            <a class="dropdown-item e-inv-export-btn" href="javascript:;" data-format="excel" data-excel-type="form41">نموذج 41</a>
                            <a class="dropdown-item e-inv-export-btn" href="javascript:;" data-format="excel" data-excel-type="account_certificate">كشف حساب / شهادة تعاملات</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .e-inv-header-blue { background: #1e88e5; color: #fff; }
    .e-inv-header-green { background: #43a047; color: #fff; }
    .e-invoice-download-modal .checkbox > span { margin-left: .5rem; }
    .e-inv-actions .btn { min-width: 120px; }
</style>
