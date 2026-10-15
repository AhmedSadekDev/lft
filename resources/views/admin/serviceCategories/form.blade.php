@php
    $titleEn = old('title.en', old('title[en]', isset($serviceCategory) ? ($serviceCategory->getTranslations('title')['en'] ?? '') : ''));
    $titleAr = old('title.ar', old('title[ar]', isset($serviceCategory) ? ($serviceCategory->getTranslations('title')['ar'] ?? '') : ''));
    $serviceStatus = old('service_status', isset($serviceCategory) ? $serviceCategory->service_status : null);
    $printSection = old('invoice_print_section', isset($serviceCategory) ? $serviceCategory->invoice_print_section : null);
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
                {!! Form::label('input_title_en', __('admin.en_title'), ['class' => 'required-field']) !!}
                {!! Form::text('title[en]', $titleEn, [
                    'class' => 'form-control',
                    'id' => 'input_title_en',
                    'placeholder' => __('admin.en_title'),
                    'required' => true,
                ]) !!}
                @error('title.en')
                    <small class="text-danger d-block mt-1">{{ $message }}</small>
                @enderror
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('input_title_ar', __('admin.ar_title'), ['class' => 'required-field']) !!}
                {!! Form::text('title[ar]', $titleAr, [
                    'class' => 'form-control',
                    'id' => 'input_title_ar',
                    'placeholder' => __('admin.ar_title'),
                    'required' => true,
                ]) !!}
                @error('title.ar')
                    <small class="text-danger d-block mt-1">{{ $message }}</small>
                @enderror
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('input_service_status', __('admin.add_to_invoice'), ['class' => 'required-field']) !!}
                {!! Form::select('service_status', $status, $serviceStatus, [
                    'class' => 'form-control',
                    'id' => 'input_service_status',
                    'placeholder' => __('admin.service_status'),
                    'required' => true,
                ]) !!}
                @error('service_status')
                    <small class="text-danger d-block mt-1">{{ $message }}</small>
                @enderror
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group mb-0">
                {!! Form::label('input_invoice_print_section', __('admin.invoice_print_section'), ['class' => 'required-field']) !!}
                {!! Form::select(
                    'invoice_print_section',
                    $print_sections ?? [],
                    $printSection,
                    [
                        'class' => 'form-control',
                        'id' => 'input_invoice_print_section',
                        'placeholder' => __('admin.invoice_print_section'),
                        'required' => true,
                        'data-container' => 'body',
                    ]
                ) !!}
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
