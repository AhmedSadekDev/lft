@extends("layouts.admin")
@section("content")
    <!--begin::Card-->
    <div class="card card-custom gutter-b">
        <div class="card-header">
            <div class="card-title">
                {{ __('admin.papers') }}
            </div>
        </div>

        <div class="card-body">
            <div class="row">
                @foreach ($booking_papers as $paper)
                    <div class="col-md-4">
                        <div class="card card-custom gutter-b">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <img src="{{ $paper->image->image}}" class="img-fluid" alt="">
                                    </div>
                                    <div class="col-md-12">

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
