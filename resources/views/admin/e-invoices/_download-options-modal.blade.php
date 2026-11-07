{{-- Download options modal for dashboard invoices --}}
<div class="modal fade" id="eInvoiceDownloadModal" tabindex="-1" role="dialog" aria-labelledby="eInvoiceDownloadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content e-inv-modal">
            <div class="modal-header e-inv-modal__header">
                <div>
                    <h5 class="modal-title mb-1" id="eInvoiceDownloadModalLabel">خيارات التحميل</h5>
                    <div class="text-muted font-size-sm">فواتير اللوحة — اختر الحقول ثم صيغة التصدير</div>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <ul class="nav nav-tabs e-inv-modal__tabs px-4" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-toggle="tab" href="#tabDownloadOptions" role="tab">خيارات التحميل</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#tabUuidDownload" role="tab">بأرقام الفواتير</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#tabTaxpayerSearch" role="tab">بحث شركة</a>
                </li>
            </ul>

            <div class="modal-body e-inv-modal__body">
                <div id="eInvExportAlert" class="alert d-none mb-4" role="alert"></div>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="tabDownloadOptions" role="tabpanel">
                        <div class="row">
                            <div class="col-lg-6 mb-4">
                                <div class="e-inv-panel e-inv-panel--blue">
                                    <div class="e-inv-panel__title">خيارات اسم الملف — PDF</div>
                                    <div class="e-inv-panel__body">
                                        <div class="e-inv-checks">
                                            <label class="e-inv-check"><input type="checkbox" data-opt="filename.internal_id" checked> رقم الفاتورة</label>
                                            <label class="e-inv-check"><input type="checkbox" data-opt="filename.electronic_id" checked> معرف الفاتورة</label>
                                            <label class="e-inv-check"><input type="checkbox" data-opt="filename.invoice_date"> تاريخ الفاتورة</label>
                                            <label class="e-inv-check"><input type="checkbox" data-opt="filename.document_type"> نوع العملية</label>
                                            <label class="e-inv-check"><input type="checkbox" data-opt="filename.folder_per_seller"> مجلد لكل بائع</label>
                                            <label class="e-inv-check"><input type="checkbox" data-opt="filename.folder_per_buyer"> مجلد لكل شركة</label>
                                            <label class="e-inv-check"><input type="checkbox" data-opt="filename.seller_name"> اسم البائع</label>
                                            <label class="e-inv-check"><input type="checkbox" data-opt="filename.seller_tax"> الرقم الضريبي للبائع</label>
                                            <label class="e-inv-check"><input type="checkbox" data-opt="filename.buyer_name"> اسم الشركة</label>
                                            <label class="e-inv-check"><input type="checkbox" data-opt="filename.buyer_tax"> الرقم الضريبي للشركة</label>
                                            <label class="e-inv-check"><input type="checkbox" data-opt="filename.po_reference"> رقم الحجز</label>
                                            <label class="e-inv-check"><input type="checkbox" data-opt="filename.so_reference"> معرف الحجز</label>
                                        </div>
                                        <div class="e-inv-scope mt-3">
                                            <label class="e-inv-check mb-0">
                                                <input type="checkbox" id="downloadAllFiltered" data-opt="use_all_filtered">
                                                تحميل كل الصفحات — <strong id="filteredInvoiceCount">{{ $filteredCount ?? 0 }}</strong> فاتورة
                                            </label>
                                            <div class="text-muted font-size-sm mt-2">PDF بحد أقصى 40 فاتورة لتجنّب ضغط السيرفر</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-6 mb-4">
                                <div class="e-inv-panel e-inv-panel--green">
                                    <div class="e-inv-panel__title">أعمدة ملف الإكسيل / TABLE</div>
                                    <div class="e-inv-panel__body">
                                        <div class="e-inv-checks">
                                            <label class="e-inv-check"><input type="checkbox" data-opt="excel.document_type" checked> نوع العملية</label>
                                            <label class="e-inv-check"><input type="checkbox" data-opt="excel.document_version"> مصدر المستند</label>
                                            <label class="e-inv-check"><input type="checkbox" data-opt="excel.status" checked> حالة السداد</label>
                                            <label class="e-inv-check"><input type="checkbox" data-opt="excel.date_issued" checked> تاريخ الإصدار</label>
                                            <label class="e-inv-check"><input type="checkbox" data-opt="excel.date_received" checked> تاريخ التحديث</label>
                                            <label class="e-inv-check"><input type="checkbox" data-opt="excel.seller_data" checked> بيانات البائع</label>
                                            <label class="e-inv-check"><input type="checkbox" data-opt="excel.buyer_data" checked> بيانات الشركة</label>
                                            <label class="e-inv-check"><input type="checkbox" data-opt="excel.electronic_id" checked> معرف الفاتورة</label>
                                            <label class="e-inv-check"><input type="checkbox" data-opt="excel.signature"> جهة التوقيع</label>
                                            <label class="e-inv-check"><input type="checkbox" data-opt="excel.external_link"> رابط الفاتورة</label>
                                            <label class="e-inv-check"><input type="checkbox" data-opt="excel.po_reference"> رقم الحجز</label>
                                            <label class="e-inv-check"><input type="checkbox" data-opt="excel.so_reference"> معرف الحجز</label>
                                            <label class="e-inv-check"><input type="checkbox" data-opt="excel.tax_columns" checked> أعمدة الضريبة</label>
                                            <label class="e-inv-check"><input type="checkbox" data-opt="excel.discount_columns" checked> أعمدة الخصم</label>
                                            <label class="e-inv-check"><input type="checkbox" data-opt="excel.group_items"> تفصيل البنود</label>
                                            <label class="e-inv-check"><input type="checkbox" data-opt="excel.credit_note_negative" checked> الحفاظ على المبالغ السالبة</label>
                                            <label class="e-inv-check"><input type="checkbox" data-opt="excel.ignore_rejected"> تجاهل الفواتير بدون حجز</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tabUuidDownload" role="tabpanel">
                        <label class="font-weight-bold d-block mb-2" for="uuidListInput">أرقام الفواتير</label>
                        <textarea id="uuidListInput" class="form-control e-inv-textarea" rows="7" placeholder="2026-48291-015&#10;2026-001-134"></textarea>
                        <p class="text-muted mt-2 mb-0">سطر لكل رقم، أو افصل بفاصلة. تُستخدم نفس خيارات التاب الأول.</p>
                    </div>

                    <div class="tab-pane fade" id="tabTaxpayerSearch" role="tabpanel">
                        <div class="form-row align-items-end">
                            <div class="form-group col-md-8 mb-md-0">
                                <label for="taxpayerQuery">اسم الشركة أو الرقم الضريبي</label>
                                <input type="text" id="taxpayerQuery" class="form-control" placeholder="ابحث في شركات اللوحة...">
                            </div>
                            <div class="form-group col-md-4 mb-0">
                                <button type="button" class="btn btn-primary btn-block" id="btnTaxpayerSearch">
                                    <i class="fas fa-search"></i> بحث
                                </button>
                            </div>
                        </div>
                        <div id="taxpayerSearchMessage" class="alert alert-light d-none mt-3 mb-2"></div>
                        <div class="table-responsive mt-2">
                            <table class="table table-sm table-bordered mb-0" id="taxpayerResultsTable">
                                <thead>
                                    <tr>
                                        <th>الشركة</th>
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

            <div class="e-inv-excel-panel d-none" id="eInvExcelPanel">
                <div class="e-inv-excel-panel__head">
                    <strong>اختر تقرير Excel</strong>
                    <button type="button" class="btn btn-sm btn-light" id="btnCloseExcelPanel">&times;</button>
                </div>
                <div class="e-inv-excel-panel__grid">
                    <button type="button" class="e-inv-excel-option e-inv-export-btn" data-format="excel" data-excel-type="comprehensive">
                        <i class="fas fa-file-excel"></i>
                        <span>تقرير شامل</span>
                    </button>
                    <button type="button" class="e-inv-excel-option e-inv-export-btn" data-format="excel" data-excel-type="transactions">
                        <i class="fas fa-exchange-alt"></i>
                        <span>بيان تعاملات</span>
                        <em class="e-inv-new">New</em>
                    </button>
                    <button type="button" class="e-inv-excel-option e-inv-export-btn" data-format="excel" data-excel-type="vat_sales">
                        <i class="fas fa-chart-line"></i>
                        <span>تقرير مبيعات قيمة مضافة</span>
                    </button>
                    <button type="button" class="e-inv-excel-option e-inv-export-btn" data-format="excel" data-excel-type="vat_purchases">
                        <i class="fas fa-shopping-cart"></i>
                        <span>تقرير مشتريات قيمة مضافة</span>
                    </button>
                    <button type="button" class="e-inv-excel-option e-inv-export-btn" data-format="excel" data-excel-type="form41">
                        <i class="fas fa-file-alt"></i>
                        <span>نموذج 41</span>
                    </button>
                    <button type="button" class="e-inv-excel-option e-inv-export-btn" data-format="excel" data-excel-type="account_certificate">
                        <i class="fas fa-file-invoice-dollar"></i>
                        <span>كشف حساب / شهادة تعاملات</span>
                    </button>
                </div>
            </div>

            <div class="modal-footer e-inv-modal__footer">
                <button type="button" class="btn e-inv-btn e-inv-btn--close" data-dismiss="modal">إغلاق</button>
                <div class="e-inv-actions">
                    <button type="button" class="btn e-inv-btn e-inv-btn--db e-inv-export-btn" data-format="database">
                        <i class="fas fa-database"></i> قاعدة البيانات
                    </button>
                    <button type="button" class="btn e-inv-btn e-inv-btn--table e-inv-export-btn" data-format="table">
                        <i class="fas fa-table"></i> TABLE
                    </button>
                    <button type="button" class="btn e-inv-btn e-inv-btn--code e-inv-export-btn" data-format="json_xml">
                        <i class="fas fa-file-code"></i> JSON/XML
                    </button>
                    <button type="button" class="btn e-inv-btn e-inv-btn--pdf e-inv-export-btn" data-format="pdf">
                        <i class="fas fa-file-pdf"></i> PDF
                    </button>
                    <button type="button" class="btn e-inv-btn e-inv-btn--excel" id="btnToggleExcelPanel">
                        <i class="fas fa-file-excel"></i> EXCEL
                    </button>
                </div>
            </div>

            <div id="eInvExportBusy" class="e-inv-busy d-none">
                <div class="e-inv-busy__card">
                    <div class="spinner-border text-primary mb-3" role="status"></div>
                    <div class="font-weight-bold">جاري تجهيز الملف...</div>
                    <div class="text-muted font-size-sm mt-1">لا تغلق النافذة</div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .e-inv-modal {
        border: 0; border-radius: 16px; overflow: visible;
        background: var(--surface, #fff); position: relative;
    }
    .e-inv-modal__header { border-bottom: 1px solid var(--border, #e5e7eb); padding: 1.1rem 1.4rem; align-items: flex-start; }
    .e-inv-modal__header .modal-title { font-weight: 800; font-size: 1.15rem; color: var(--text-primary, #111); }
    .e-inv-modal__tabs { border-bottom: 1px solid var(--border, #e5e7eb); gap: .25rem; }
    .e-inv-modal__tabs .nav-link {
        border: 0; border-bottom: 2px solid transparent; color: var(--text-secondary, #6b7280);
        font-weight: 600; padding: .85rem 1rem;
    }
    .e-inv-modal__tabs .nav-link.active { color: #1e88e5; border-bottom-color: #1e88e5; background: transparent; }
    .e-inv-modal__body { padding: 1.25rem 1.4rem; background: var(--surface-secondary, #f8fafc); }
    .e-inv-panel { border-radius: 14px; overflow: hidden; border: 1px solid var(--border, #e5e7eb); background: var(--surface, #fff); height: 100%; }
    .e-inv-panel__title { padding: .85rem 1rem; font-weight: 800; color: #fff; }
    .e-inv-panel--blue .e-inv-panel__title { background: #1e88e5; }
    .e-inv-panel--green .e-inv-panel__title { background: #43a047; }
    .e-inv-panel__body { padding: 1rem; }
    .e-inv-checks { display: grid; grid-template-columns: 1fr 1fr; gap: .55rem .75rem; }
    .e-inv-check {
        display: flex; align-items: flex-start; gap: .55rem; margin: 0;
        font-size: 13px; color: var(--text-primary, #111); cursor: pointer; line-height: 1.35;
    }
    .e-inv-check input { margin-top: .15rem; flex: 0 0 auto; }
    .e-inv-scope {
        border: 1px dashed var(--border, #cbd5e1); border-radius: 10px;
        padding: .85rem 1rem; background: var(--surface-secondary, #f8fafc);
    }
    .e-inv-textarea { border-radius: 10px; }
    .e-inv-excel-panel {
        margin: 0 1.25rem 0; padding: 1rem;
        border: 1px solid rgba(67, 160, 71, .35);
        border-radius: 12px;
        background: rgba(67, 160, 71, .08);
    }
    .e-inv-excel-panel__head {
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: .75rem; color: var(--text-primary, #111);
    }
    .e-inv-excel-panel__grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .65rem;
    }
    .e-inv-excel-option {
        display: flex; align-items: center; gap: .65rem;
        width: 100%; text-align: right; white-space: normal;
        border: 1px solid var(--border, #d1d5db);
        background: var(--surface, #fff);
        color: var(--text-primary, #111);
        border-radius: 10px; padding: .75rem .9rem;
        font-weight: 600; line-height: 1.35;
    }
    .e-inv-excel-option:hover {
        border-color: #43a047; background: rgba(67, 160, 71, .12);
    }
    .e-inv-excel-option i { color: #43a047; width: 18px; text-align: center; flex: 0 0 auto; }
    .e-inv-excel-option span { flex: 1 1 auto; }
    .e-inv-new {
        background: #e53935; color: #fff; border-radius: 999px;
        font-style: normal; font-size: 10px; padding: .1rem .4rem; font-weight: 700;
    }
    .e-inv-modal__footer {
        display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center;
        gap: .75rem; padding: 1rem 1.25rem; border-top: 1px solid var(--border, #e5e7eb);
        background: var(--surface, #fff);
    }
    .e-inv-actions { display: flex; flex-wrap: wrap; gap: .5rem; justify-content: flex-end; }
    .e-inv-btn {
        border: 0; border-radius: 10px; font-weight: 700; min-width: 118px;
        padding: .7rem 1rem; color: #fff !important;
    }
    .e-inv-btn i { margin-left: .35rem; }
    .e-inv-btn--close { background: #e53935; }
    .e-inv-btn--db { background: #fb8c00; }
    .e-inv-btn--table { background: #8e24aa; }
    .e-inv-btn--code { background: #546e7a; }
    .e-inv-btn--pdf { background: #1e88e5; }
    .e-inv-btn--excel { background: #43a047; }
    .e-inv-busy {
        position: absolute; inset: 0; background: rgba(15, 23, 42, .45);
        display: flex; align-items: center; justify-content: center; z-index: 5;
        border-radius: 16px;
    }
    .e-inv-busy__card {
        background: var(--surface, #fff); border-radius: 14px; padding: 1.5rem 2rem;
        text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,.18);
    }
    @media (max-width: 767px) {
        .e-inv-checks, .e-inv-excel-panel__grid { grid-template-columns: 1fr; }
        .e-inv-modal__footer { flex-direction: column-reverse; align-items: stretch; }
        .e-inv-actions { justify-content: stretch; }
        .e-inv-btn { width: 100%; }
    }
</style>
