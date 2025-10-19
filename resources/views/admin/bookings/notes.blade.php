@extends("layouts.admin")
@section("content")
    <!--begin::Card-->
    <div class="card card-custom gutter-b">
        <div class="card-header">
            <div class="card-title">
                الملاحظات
            </div>
        </div>

        <div class="card-body">
            <div class="row">
                @foreach ($notes as $paper)
                    <div class="col-md-3">
                        <div class="card card-custom gutter-b">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12 position-relative">
                                        <!-- Images -->
                                        <div class="image-gallery mb-3">
                                            @foreach($paper->images as $image)
                                                <img src="{{ $image->image }}" class="img-fluid mb-2" alt="image">
                                            @endforeach
                                        </div>

                                        <!-- Note Content -->
                                        <div class="note-content mb-3">
                                            <h6><strong>الملاحظة:</strong></h6>
                                            <p>{{ $paper->notes }}</p>
                                        </div>

                                        <!-- Note Owner -->
                                        <div class="note-owner mb-3">
                                            <h6><strong>صاحب الملاحظة:</strong></h6>
                                            <p>{{ $paper->attacher->name }}</p>
                                        </div>

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
