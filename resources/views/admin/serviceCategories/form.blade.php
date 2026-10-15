@php
    $titleEn = old('title.en', old('title[en]', isset($serviceCategory) ? ($serviceCategory->getTranslations('title')['en'] ?? '') : ''));
    $titleAr = old('title.ar', old('title[ar]', isset($serviceCategory) ? ($serviceCategory->getTranslations('title')['ar'] ?? '') : ''));
    $serviceStatus = old('service_status', isset($serviceCategory) ? $serviceCategory->service_status : null);
    $printSection = old('invoice_print_section', isset($serviceCategory) ? $serviceCategory->invoice_print_section : null);
    $statusOptions = ['' => __('admin.select')] + ($status ?? []);
    $printOptions = ['' => __('admin.select')] + ($print_sections ?? []);
@endphp

@if ($method == 'POST')
    {!! Form::open([
        'url' => $action,
        'method' => $method,
        'enctype' => 'multipart/form-data',
        'files' => true,
        'class' => 'lft-simple-form',
    ]) !!}
@elseif ($method == 'PUT')
    {!! Form::model($serviceCategory, [
        'url' => [$action],
        'method' => $method,
        'enctype' => 'multipart/form-data',
        'files' => true,
        'class' => 'lft-simple-form',
    ]) !!}
@endif

<div class="card-body">
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="input_title_en" class="required-field">{{ __('admin.en_title') }}</label>
                <input
                    type="text"
                    name="title[en]"
                    id="input_title_en"
                    class="form-control"
                    value="{{ $titleEn }}"
                    placeholder="{{ __('admin.en_title') }}"
                    required
                >
                @error('title.en')
                    <small class="text-danger d-block mt-1">{{ $message }}</small>
                @enderror
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <label for="input_title_ar" class="required-field">{{ __('admin.ar_title') }}</label>
                <input
                    type="text"
                    name="title[ar]"
                    id="input_title_ar"
                    class="form-control"
                    value="{{ $titleAr }}"
                    placeholder="{{ __('admin.ar_title') }}"
                    required
                >
                @error('title.ar')
                    <small class="text-danger d-block mt-1">{{ $message }}</small>
                @enderror
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <label for="input_service_status" class="required-field">{{ __('admin.add_to_invoice') }}</label>
                <select
                    name="service_status"
                    id="input_service_status"
                    class="form-control"
                    data-native-select
                    required
                >
                    @foreach ($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected($value === '' ? ($serviceStatus === null || $serviceStatus === '') : (string) $serviceStatus === (string) $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('service_status')
                    <small class="text-danger d-block mt-1">{{ $message }}</small>
                @enderror
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group mb-0">
                <label for="input_invoice_print_section" class="required-field">{{ __('admin.invoice_print_section') }}</label>
                <select
                    name="invoice_print_section"
                    id="input_invoice_print_section"
                    class="form-control"
                    data-native-select
                    required
                >
                    @foreach ($printOptions as $value => $label)
                        <option value="{{ $value }}" @selected($value === '' ? ($printSection === null || $printSection === '') : (string) $printSection === (string) $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('invoice_print_section')
                    <small class="text-danger d-block mt-1">{{ $message }}</small>
                @enderror
                <small class="text-muted d-block mt-1">
                    مكان ظهور الفئة عند طباعة الفاتورة: ضريبية / إيصالات / خدمات إضافية
                </small>
            </div>
        </div>
    </div>
</div>

<div class="card-footer lft-simple-form-footer">
    @if ($method == 'POST')
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save ml-1"></i>
            {{ __('admin.save') }}
        </button>
    @else
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-edit ml-1"></i>
            {{ __('admin.update') }}
        </button>
    @endif
    <a href="{{ route('serviceCategories.index') }}" class="btn btn-secondary">
        <i class="fas fa-times ml-1"></i>
        {{ __('main.back') }}
    </a>
</div>

{!! Form::close() !!}
