@php
    $isEdit = isset($role);
    $selectedIds = collect(old('permissions', $isEdit ? $role->permissions->pluck('id')->all() : []))->map(fn ($id) => (int) $id)->all();

    $grouped = $permissions
        ->sortBy('name')
        ->groupBy(function ($permission) {
            $parts = explode('.', $permission->name);
            return count($parts) > 1 ? $parts[0] : 'other';
        });

    $groupLabels = [
        'dashboard' => 'لوحة التحكم',
        'companies' => 'الشركات',
        'superagents' => 'المندوبين المسؤولين',
        'agents' => 'المندوبين',
        'bookings' => 'طلبات النقل',
        'bookingContainers' => 'حاويات الطلبات',
        'bookingServices' => 'خدمات الطلبات',
        'invoices' => 'الفواتير',
        'cars' => 'السيارات',
        'drivers' => 'السائقين',
        'employees' => 'الموظفين',
        'factories' => 'المصانع',
        'branches' => 'الفروع',
        'cities' => 'المدن والمناطق',
        'containers' => 'الحاويات',
        'serviceCategories' => 'فئات الخدمات',
        'services' => 'الخدمات',
        'shippingAgents' => 'التوكيلات الملاحية',
        'transportations' => 'النقل',
        'shipments' => 'الشحنات',
        'yards' => 'الساحات',
        'vaults' => 'الخزن',
        'banks' => 'البنوك',
        'suppliers' => 'الموردين',
        'permissions' => 'الصلاحيات',
        'users' => 'المستخدمين',
        'settings' => 'الإعدادات',
        'reports' => 'التقارير',
        'daily_reports' => 'التقارير اليومية',
        'financial_custody_agents' => 'عهدة المندوبين',
        'financial_custody_superagents' => 'عهدة المسؤولين',
        'staticPages' => 'الصفحات الثابتة',
        'ourServices' => 'خدماتنا',
        'chooseUs' => 'لماذا أخترتنا',
        'sponsers' => 'الرعاة',
        'reviews' => 'التقييمات',
        'contactUs' => 'اتصل بنا',
        'accounts' => 'الحسابات',
        'receipts' => 'الإيصالات',
        'other' => 'أخرى',
    ];

    $actionLabels = [
        'index' => 'عرض',
        'create' => 'إنشاء',
        'update' => 'تحديث',
        'udpate' => 'تحديث',
        'edit' => 'تعديل',
        'delete' => 'حذف',
        'show' => 'عرض',
        'store' => 'حفظ',
    ];

    $resolvePermLabel = function (string $name) use ($groupLabels, $actionLabels): string {
        $translated = __('admin.' . $name);
        if ($translated !== 'admin.' . $name) {
            return $translated;
        }

        $parts = explode('.', $name);
        $groupKey = $parts[0] ?? $name;
        $actionKey = $parts[1] ?? '';
        $group = $groupLabels[$groupKey] ?? $groupKey;
        $action = $actionLabels[$actionKey] ?? $actionKey;

        if ($actionKey === 'index') {
            return 'عرض ' . $group;
        }
        if ($action !== '') {
            return $action . ' — ' . $group;
        }

        return $group;
    };
@endphp

<form action="{{ $action }}" method="post" class="lft-simple-form lft-perm-form" id="lftPermForm">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="card-body">
        <div class="lft-perm-name-block">
            <div class="form-group mb-0">
                <label class="required-field" for="roleNameInput">اسم الدور</label>
                <input id="roleNameInput"
                       class="form-control form-control-lg"
                       type="text"
                       name="name"
                       placeholder="مثال: محاسب، مشرف عمليات..."
                       value="{{ old('name', $isEdit ? $role->name : '') }}"
                       required
                       autocomplete="off">
                @error('name')
                    <div class="text-danger mt-1">{{ $message }}</div>
                @enderror
            </div>
            @if ($isEdit)
                <div class="lft-perm-meta">
                    <span class="lft-perm-meta-chip">
                        <i class="fas fa-key" aria-hidden="true"></i>
                        {{ count($selectedIds) }} صلاحية مفعّلة
                    </span>
                    <span class="lft-perm-meta-chip lft-perm-meta-chip--muted">
                        <i class="fas fa-layer-group" aria-hidden="true"></i>
                        {{ $grouped->count() }} مجموعة
                    </span>
                </div>
            @endif
        </div>

        <div class="lft-perm-toolbar">
            <div class="lft-perm-search">
                <i class="fas fa-search" aria-hidden="true"></i>
                <input type="search"
                       id="lftPermSearch"
                       class="form-control"
                       placeholder="ابحث عن صلاحية أو مجموعة..."
                       autocomplete="off"
                       data-native-select>
            </div>
            <div class="lft-perm-toolbar-actions">
                <button type="button" class="btn btn-light-primary btn-sm" id="lftPermSelectAll">
                    <i class="fas fa-check-double" aria-hidden="true"></i> تحديد الكل
                </button>
                <button type="button" class="btn btn-light-danger btn-sm" id="lftPermClearAll">
                    <i class="fas fa-times" aria-hidden="true"></i> إلغاء الكل
                </button>
            </div>
        </div>

        @error('permissions')
            <div class="alert alert-danger py-2 px-3 mb-3">{{ $message }}</div>
        @enderror

        <div class="lft-perm-groups" id="lftPermGroups">
            @foreach ($grouped as $groupKey => $groupPermissions)
                @php
                    $groupTitle = $groupLabels[$groupKey]
                        ?? ((__('admin.' . $groupKey) !== 'admin.' . $groupKey) ? __('admin.' . $groupKey) : $groupKey);
                    $groupChecked = $groupPermissions->filter(fn ($p) => in_array((int) $p->id, $selectedIds, true))->count();
                    $groupTotal = $groupPermissions->count();
                @endphp
                <section class="lft-perm-group" data-group="{{ $groupKey }}">
                    <header class="lft-perm-group-header">
                        <div class="lft-perm-group-title">
                            <label class="lft-perm-group-check mb-0">
                                <input type="checkbox"
                                       class="lft-perm-group-toggle"
                                       data-group-toggle="{{ $groupKey }}"
                                       {{ $groupChecked === $groupTotal && $groupTotal > 0 ? 'checked' : '' }}
                                       data-native-select>
                                <span>{{ $groupTitle }}</span>
                            </label>
                            <span class="lft-perm-group-count" data-group-count="{{ $groupKey }}">
                                {{ $groupChecked }}/{{ $groupTotal }}
                            </span>
                        </div>
                    </header>
                    <div class="lft-perm-grid">
                        @foreach ($groupPermissions as $permission)
                            @php
                                $label = $resolvePermLabel($permission->name);
                                $action = str_contains($permission->name, '.')
                                    ? last(explode('.', $permission->name))
                                    : $permission->name;
                                $actionAr = $actionLabels[$action] ?? $action;
                            @endphp
                            <label class="lft-perm-item @if(in_array((int) $permission->id, $selectedIds, true)) is-checked @endif"
                                   data-search="{{ mb_strtolower($label . ' ' . $groupTitle . ' ' . $actionAr, 'UTF-8') }}">
                                <input id="permission{{ $permission->id }}"
                                       class="lft-perm-checkbox"
                                       type="checkbox"
                                       name="permissions[]"
                                       value="{{ $permission->id }}"
                                       data-group="{{ $groupKey }}"
                                       {{ in_array((int) $permission->id, $selectedIds, true) ? 'checked' : '' }}
                                       data-native-select>
                                <span class="lft-perm-item-body">
                                    <span class="lft-perm-item-label">{{ $label }}</span>
                                    <span class="lft-perm-item-action lft-perm-item-action--{{ $action }}">{{ $actionAr }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>

        <div class="lft-perm-empty d-none" id="lftPermEmpty">
            <i class="fas fa-search" aria-hidden="true"></i>
            <p>لا توجد صلاحيات مطابقة للبحث</p>
        </div>
    </div>

    <div class="card-footer lft-simple-form-footer">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save ml-1" aria-hidden="true"></i>
            {{ $isEdit ? __('admin.update') : __('admin.save') }}
        </button>
        <a href="{{ route('permissions.index') }}" class="btn btn-secondary">{{ __('main.back') }}</a>
        <span class="lft-perm-footer-count text-muted mr-auto" id="lftPermSelectedCount">
            {{ count($selectedIds) }} محددة
        </span>
    </div>
</form>

@push('js')
<script>
(function () {
    var form = document.getElementById('lftPermForm');
    if (!form) return;

    var searchInput = document.getElementById('lftPermSearch');
    var emptyState = document.getElementById('lftPermEmpty');
    var selectedCountEl = document.getElementById('lftPermSelectedCount');

    function updateItemState(item) {
        var input = item.querySelector('.lft-perm-checkbox');
        item.classList.toggle('is-checked', !!(input && input.checked));
    }

    function updateGroupToggle(groupKey) {
        var group = form.querySelector('.lft-perm-group[data-group="' + groupKey + '"]');
        if (!group) return;
        var boxes = group.querySelectorAll('.lft-perm-checkbox');
        var checked = group.querySelectorAll('.lft-perm-checkbox:checked');
        var toggle = group.querySelector('.lft-perm-group-toggle');
        var countEl = group.querySelector('[data-group-count="' + groupKey + '"]');
        if (toggle) {
            toggle.checked = boxes.length > 0 && checked.length === boxes.length;
            toggle.indeterminate = checked.length > 0 && checked.length < boxes.length;
        }
        if (countEl) countEl.textContent = checked.length + '/' + boxes.length;
    }

    function updateSelectedCount() {
        var n = form.querySelectorAll('.lft-perm-checkbox:checked').length;
        if (selectedCountEl) selectedCountEl.textContent = n + ' محددة';
    }

    function refreshAllGroups() {
        form.querySelectorAll('.lft-perm-group').forEach(function (group) {
            updateGroupToggle(group.getAttribute('data-group'));
        });
        updateSelectedCount();
    }

    form.querySelectorAll('.lft-perm-item').forEach(updateItemState);

    form.addEventListener('change', function (e) {
        var target = e.target;
        if (target.classList.contains('lft-perm-checkbox')) {
            var item = target.closest('.lft-perm-item');
            if (item) updateItemState(item);
            updateGroupToggle(target.getAttribute('data-group'));
            updateSelectedCount();
            return;
        }
        if (target.classList.contains('lft-perm-group-toggle')) {
            var groupKey = target.getAttribute('data-group-toggle');
            var group = form.querySelector('.lft-perm-group[data-group="' + groupKey + '"]');
            if (!group) return;
            group.querySelectorAll('.lft-perm-checkbox').forEach(function (box) {
                box.checked = target.checked;
                var item = box.closest('.lft-perm-item');
                if (item) updateItemState(item);
            });
            updateGroupToggle(groupKey);
            updateSelectedCount();
        }
    });

    var selectAllBtn = document.getElementById('lftPermSelectAll');
    var clearAllBtn = document.getElementById('lftPermClearAll');
    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', function () {
            form.querySelectorAll('.lft-perm-group:not(.is-filtered-out) .lft-perm-checkbox').forEach(function (box) {
                var item = box.closest('.lft-perm-item');
                if (item && item.classList.contains('is-filtered-out')) return;
                box.checked = true;
                if (item) updateItemState(item);
            });
            refreshAllGroups();
        });
    }
    if (clearAllBtn) {
        clearAllBtn.addEventListener('click', function () {
            form.querySelectorAll('.lft-perm-checkbox').forEach(function (box) {
                box.checked = false;
                var item = box.closest('.lft-perm-item');
                if (item) updateItemState(item);
            });
            refreshAllGroups();
        });
    }

    function normalize(s) {
        return (s || '').toLowerCase()
            .replace(/[أإآ]/g, 'ا')
            .replace(/ة/g, 'ه')
            .replace(/ى/g, 'ي')
            .replace(/\s+/g, ' ')
            .trim();
    }

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            var q = normalize(searchInput.value);
            var visibleGroups = 0;
            form.querySelectorAll('.lft-perm-group').forEach(function (group) {
                var visibleItems = 0;
                group.querySelectorAll('.lft-perm-item').forEach(function (item) {
                    var match = !q || normalize(item.getAttribute('data-search')).indexOf(q) !== -1;
                    item.classList.toggle('is-filtered-out', !match);
                    if (match) visibleItems++;
                });
                var hideGroup = visibleItems === 0;
                group.classList.toggle('is-filtered-out', hideGroup);
                if (!hideGroup) visibleGroups++;
            });
            if (emptyState) emptyState.classList.toggle('d-none', visibleGroups > 0);
        });
    }

    refreshAllGroups();
})();
</script>
@endpush
