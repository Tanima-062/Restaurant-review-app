@extends('admin.Layouts.base')
@include('admin.Layouts.head')

@include('admin.Layouts.side_overlay')
@include('admin.Layouts.sidebar')
@include('admin.Layouts.page_header')

@section('css')
<style>
    .form-material> div .form-control, #menuPrices .form-control {
        padding-left: 0;
        padding-right: 0;
        border-color: transparent;
        border-radius: 0;
        background-color: transparent;
        box-shadow: 0 1px 0 #d4dae3;
        transition: box-shadow .3s ease-out;
    }
    .modal-dialog { width: 500px!important; }
</style>
@endsection

@section('content')
    <!-- Content -->
    <div class="content">
        <!-- Validation Message -->
        <span id="result"></span>
        <!-- Default Table Style -->
        <h2 class="content-heading">{{ $menu->name }} 料金追加</h2>
        <!-- Floating Labels -->
        <form id="add_form" method="post">
            @csrf
            <div class="block col-md-9">
                <div class="block-content" id="dynamicForm">
                    <input type="hidden" name="menu_id" value="{{ $menu->id }}">
                    <input type="hidden" name="menu_name" value="{{ $menu->name }}">
                    <input type="hidden" name="redirect_to" value="{{ old('redirect_to', url()->previous()) }}">
                    <div id="menuPrice_form">

                    </div>
                </div>

                <div class="block-content form-group" style="padding-bottom: 20px;">
                    <div class="text-right">
                        <button type="button" id="add" class="btn btn-sm btn-secondary" data-toggle="tooltip" title="Add Form">
                            <i class="fa fa-plus"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-md-9 d-flex justify-content-between">
                <div>
                    <button type="button" class="btn btn-secondary" onclick="location.href='{{ route('admin.menu.price.edit', ['id' => $menu->id]) }}'">戻る</button>
                </div>
                <div style="padding-right: 20px;">
                    <button type="submit" class="btn btn-alt-primary" id="save" value="save">保存</button>
                </div>
            </div>
        </form>
    </div>
    <!-- END Content -->

    @include('admin.Layouts.js_files')

@endsection

@include('admin.Layouts.footer')

@section('js')
    <script src="{{ asset('vendor/admin/assets/js/menuPrice.js').'?'.time() }}"></script>
    <script>
        $(document).ready(function() {
            let count = 1;
            dynamic_field(count);

            function dynamic_field(number) {
                html =  '<div class="add_form-group">';
                html += '<input type="hidden" name="menu['+count+'][add_price]">';
                html += '<div class="form-group"><div class="form-material col-6 col-md-4 pl-0">';
                html += '<label for="price_cd">料金コード（追加）<span class="badge bg-danger ml-2 text-white">必須</span></label><select class="form-control" name="menu['+count+'][price_cd]">';
                html += '<option value="">選択してください</option>';
                html += '@foreach($menuPriceCodes as $key => $value)';
                html += '<option value="{{ $key }}">{{ $value }}</option>';
                html += '@endforeach';
                html += '</select></div></div>';
                html += '<label for="menuPrices" class="mb-0">日付<span class="badge bg-danger ml-2 text-white">必須</span></label>';
                html += '<div class="form-group row" id="menuPrices">';
                html += '<div class="col-5 col-md-3"><input type="text" class="form-control price_start_date datepicker" name="menu['+count+'][price_start_date]" autocomplete="off"></div>';
                html += '<span style="margin-top:8px">～</span>';
                html += '<div class="col-5 col-md-3"><input type="text" class="form-control price_end_date" name="menu['+count+'][price_end_date]" autocomplete="off"></div></div>';
                html += '<div class="form-group"><div class="form-material">';
                html += '<label for="price">金額（税込）<span class="badge bg-danger ml-2 text-white">必須</span></label><div class="d-flex pl-0 justify-content-between">';
                html += '<div class="d-flex"><input type="text" class="form-control" name="menu['+count+'][price]">';
                html += '<span class="m-3" id="minus-btn">円</span></div>';
                html += '</div></div>';
                html += '</div>';

                if (number > 1) {
                    html += '<div class="text-right"><button type="button" class="btn btn-sm btn-secondary remove-form" data-toggle="tooltip" title="Erase">';
                    html += '<i class="fa fa-minus"></i></button></div>';
                    $('#menuPrice_form').append(html);
                } else {
                    $('#menuPrice_form').html(html);
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

            $('#add_form').on('submit', function(event) {
                event.preventDefault();
                $.ajax({
                    url:'{{ route("admin.menu.price.add", ['id' => $menu->id]) }}',
                    method:'post',
                    data:$(this).serialize(),
                    dataType:'json',
                    beforeSend:function() {
                        $('#save').attr('disabled','disabled');
                    },
                    success:function(data) {
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
