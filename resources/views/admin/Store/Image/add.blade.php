@extends('admin.Layouts.base')
@include('admin.Layouts.head')

@include('admin.Layouts.side_overlay')
@include('admin.Layouts.sidebar')
@include('admin.Layouts.page_header')

@section('css')
<style>
    .form-material> div .form-control {
        padding-left: 0;
        padding-right: 0;
        border-color: transparent;
        border-radius: 0;
        background-color: transparent;
        box-shadow: 0 1px 0 #d4dae3;
        transition: box-shadow .3s ease-out;
    }
    .modal-dialog { width: 500px!important; }
    .content-heading { border-bottom: 0; }
</style>
@endsection

@section('content')
    <!-- Content -->
    <div class="content pb-3">
        <!-- Validation Message -->
        <span id="result"></span>
        <!-- Default Table Style -->
        <div class="d-flex col-md-9 justify-content-between">
            <h2 class="content-heading">{{ $store->name }} 画像追加</h2>
        </div>
        <!-- Floating Labels -->
        <form id="add_storeImage" method="post" enctype="multipart/form-data">
            @csrf
            <div class="block col-md-9">
                <div class="block-content" id="dynamicForm" style="padding-bottom: 20px;">
                        <input type="hidden" name="store_id" value="{{ $store->id }}">
                        <input type="hidden" name="store_name" value="{{ $store->name }}">
                        <input type="hidden" name="store_code" value="{{ $store->code }}">
                        <input type="hidden" name="redirect_to" value="{{ old('redirect_to', url()->previous()) }}">
                    <!-- Start Form -->
                    <div id="storeImage_form">

                    </div>
                    <!-- End Form -->
                    <div class="form-group mt-4">
                        <div class="text-right">
                            <button type="button" id="add" class="btn btn-sm btn-secondary" data-toggle="tooltip" title="Add">
                                <i class="fa fa-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-9 d-flex justify-content-between">
                <div>
                    <button type="button" class="btn btn-secondary" onclick="location.href='{{ route('admin.store.image.edit', ['id' => $store->id]) }}'">戻る</button>
                </div>
                <div style="padding-right: 20px;">
                    <button type="submit" class="btn btn-alt-primary" value="add" id="save">追加</button>
                </div>
            </div>
        </form>
    </div>
    <!-- END Content -->

    @include('admin.Layouts.js_files')

@endsection

@include('admin.Layouts.footer')


@section('js')
    <script>
        $(document).ready(function() {
            let count = 1;
            dynamic_field(count);

            function dynamic_field(number) {
                html =  '<div class="add_form-group">';
                html += '<div class="form-group"><div class="form-material col-10 col-md-5 pl-0">';
                html += '<label for="image_cd">店舗画像コード（追加）<span class="badge bg-danger ml-2 text-white">必須</span></label><select class="form-control" name="storeImage['+count+'][image_cd]" required>';
                html += '<option value="">選択してください</option>';
                html += '@foreach($storeImageCodes as $key => $value)';
                html += '<option value="{{ $key }}" {{ $key == old('image_cd') ? 'selected' : '' }}>{{ $value }}</option>';
                html += '@endforeach';
                html += '</select></div>';
                html += '<div class="form-group"><div class="form-material">';
                html += '<p class="mb-0">' + "{{ config('const.messageBoard.img_file_desc') }}" + '</p>';
                html += '<input type="file" class="form-control" id="storeImage['+count+'][image_path]" name="storeImage['+count+'][image_path]">';
                html += '<label for="image_path">店舗画像<span class="badge bg-danger ml-2 text-white">必須</span></label>';
                html += '</div></div>';
                html += '</div>';

                if (number > 1) {
                    html += '<div class="text-right">';
                    html += '<button type="button" class="btn btn-sm btn-secondary remove-form" data-toggle="tooltip" title="Erase">';
                    html += '<i class="fa fa-minus"></i>';
                    html += '</button>';
                    html += '</div>';
                    $('#storeImage_form').append(html);
                } else {
                    $('#storeImage_form').html(html);
                }
            }

            $(document).on('click', '#add', function() {
                count++;
                dynamic_field(count);
            });

            $(document).on('click', '.remove-form', function() {
                count--;
                $(this).parents('.add_form-group').remove();
            });

            $('#add_storeImage').on('submit', function(event) {
                let formData = new FormData(this);
                event.preventDefault();
                $.ajax({
                    url:'{{ route("admin.store.image.add") }}',
                    method: 'post',
                    data: formData,
                    contentType: false,
                    processData: false,
                    dataType: 'json',
                    beforeSend: function() {
                        $('#save').attr('disabled','disabled');
                    },
                    success: function(data) {
                        if (data.error) {
                            let error_html = '';
                            for (let count = 0; count < data.error.length; count++) {
                                error_html += '<ul style="margin-bottom: 0"><li>'+data.error[count]+'</li></ul>';
                            }
                            $('#result').html('<div class="alert alert-danger col-md-9">'+error_html+'</div>');
                        } else {
                            dynamic_field(1);
                            $('#result').html('<div class="alert alert-success col-md-9">'+data.success+'</div>');
                            $("#result").fadeIn(300).delay(1000).fadeOut(300).queue(function()
                            {
                                window.location=data.url;
                            });
                        }
                        $('#save').attr('disabled', false);
                    }
                })
            });
        });
    </script>
@endsection
