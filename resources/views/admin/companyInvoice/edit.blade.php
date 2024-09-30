@extends('layouts.admin')
@section('content')
    <!--begin::Card-->
    <div class="card card-custom gutter-b">
        <div class="card-header">
            <div class="card-title">
                اضافة استلام
            </div>
        </div>
        {!! Form::open([
            'url' => route('companyInvoice.update', [$invoice->company_id]),
            'method' => 'put',
            'enctype' => 'multipart/form-data',
            'files' => true,
        ]) !!}

        <div class="card-body">
            <div class="row">

                <!-- For loop this div -->
                <div class="col-md-6 col-sm-12">
                    <div class="form-group">
                        {!! Form::label('input_image', 'صورة التحويل') !!}
                        {!! Form::file('image', [
                            'class' => 'form-control',
                            'id' => 'input_image',
                            'placeholder' => 'صورة التحويل',
                        ]) !!}
                        @error('image')
                            <small class="aleart text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6 col-sm-12">
                    <div class="form-group">
                        {!! Form::label('input_total', 'القيمة المرسلة') !!}
                        {!! Form::text('total', $invoice->total ?? old('total'), [
                            'class' => 'form-control',
                            'id' => 'input_total',
                            'placeholder' => 'القيمة المرسلة',
                            'required' => true,
                        ]) !!}
                        @error('total')
                            <small class="aleart text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <!-- Add hidden input field here -->
                {!! Form::hidden('invoice_id', $invoice->id) !!}
                <!-- You can also add multiple hidden fields as needed -->

            </div>
        </div>

        <div class="card-footer">
            {!! Form::submit(__('admin.save'), [
                'class' => 'btn btn-primary btn-md text-uppercase font-weight-bold chat-send py-2 px-6',
            ]) !!}
        </div>

        {!! Form::close() !!}
        <!-- /.card-body -->
    </div>
@endsection
