@extends('admin.Layouts.base')
@include('admin.Layouts.head')

@include('admin.Layouts.side_overlay')
@include('admin.Layouts.sidebar')
@include('admin.Layouts.page_header')

@section('css')
<style>
    /* pc 既存の表示への影響をしないよう */
    @media screen and (min-width: 961px) {
        div.item-list-pc {
            display: block;
        }

        div.item-list-sp {
            display: none;
        }

        .block-content {
            padding: 20px 20px 1px !important;
        }

        /*
    .form-material {
        padding-top: 20px !important;
    } */

        .form-material>div .form-control,
        #menuPrices .form-control {
            padding-left: 0;
            padding-right: 0;
            border-color: transparent;
            border-radius: 0;
            background-color: transparent;
            box-shadow: 0 1px 0 #d4dae3;
            transition: box-shadow .3s ease-out;
        }

        .form-material>label {
            position: static;
        }

        .modal-dialog {
            width: 500px !important;
        }

        .form-material>div .form-control,
        #menuPrices .form-control {
            padding-left: 0;
            padding-right: 0;
            border-color: transparent;
            border-radius: 0;
            background-color: transparent;
            box-shadow: 0 1px 0 #d4dae3;
            transition: box-shadow .3s ease-out;
        }

        .modal-dialog {
            width: 500px !important;
        }

        .box-input,
        .add_form-group {
            /* box-shadow: 0 1px 0 #d4dae3; */
        }

        .modal-dialog {
            width: 500px !important;
        }

        .content-heading {
            border-bottom: 0;
        }

    }

    /* sp 表示設定 */
    @media screen and (max-width: 960px) {
        div.item-list-pc {
            display: none;
        }

        div.item-list-sp {
            display: block;
        }

        .block-content {
            padding: 20px 0 1px !important;
        }

        .block-content {
            padding: 20px 0 1px !important;
        }

        /*
    .form-material {
        padding-top: 20px !important;
    } */

        .form-material>div .form-control,
        #menuPrices .form-control {
            padding-left: 0;
            padding-right: 0;
            border-color: transparent;
            border-radius: 0;
            background-color: transparent;
            box-shadow: 0 1px 0 #d4dae3;
            transition: box-shadow .3s ease-out;
        }

        .modal-dialog {
            width: 500px !important;
        }

        .box-input,
        .add_form-group {
            /* box-shadow: 0 1px 0 #d4dae3; */
        }

        .form-material>label {
            position: static;
        }

        .modal-dialog {
            width: 500px !important;
        }

    }
</style>
@endsection

@section('content')
<!-- Content -->
<div class="content item-list-pc">
    @include('admin.Layouts.flash_message')

    <!-- Default Table Style -->
    <div class="block" style="background-color: #f0f2f5; margin-bottom: 0;">
        <div class=" col-md-9 block-header">
            <h2 class="content-heading" style="margin-bottom: 0; padding-top: 0;">{{ $menu->name }} 料金設定</h2>
        </div>
    </div>
    <!-- Floating Labels -->
    <form id="price_form" action="{{ route('admin.menu.price.edit', ['id' => $menu->id]) }}" method="post">
        @csrf
        <div class="block col-md-9">

            <div class="block-content" id="dynamicForm">
                <input type="hidden" name="menu_id" value="{{ $menu->id }}">
                <input type="hidden" name="menu_name" value="{{ $menu->name }}">
                <input type="hidden" name="menu_published" id="menu_published-sp" value="{{ $menu->published }}">
                <input type="hidden" name="redirect_to" value="{{ old('redirect_to', url()->previous()) }}">
                @if(!empty($menuPriceExists))
                <div class="mb-3">
                    @foreach($menuPrices as $menuPrice)
                    <div class="box-input">
                        <input type="hidden" name="menu[{{ $loop->index }}][price_id]" value="{{ $menuPrice['id'] }}">
                        <div class="form-group">
                            <div class="form-material col-12 col-md-4 px-0">
                                <label for="price_cd">料金コード{{ $loop->iteration }}</label>
                                <select class="form-control" name="menu[{{ $loop->index }}][price_cd]">
                                    <option value="">選択してください</option>
                                    @foreach($menuPriceCodes as $key => $value)
                                    <option value="{{ $key }}" {{ $key == old('menu.'.$loop->index.'.price_cd', $menuPrice['price_cd']) ? 'selected' : '' }}>
                                        {{ $value }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <label for="menuPrices" class="mb-0">日付</label>
                        <div class="row form-material  align-items-center" style="margin-top:-26px;margin-bottom: 16px;">
                            <div class="col-5"> <input type="text" style="width:100px" class="form-control price_start_date" name="menu[{{ $loop->index }}][price_start_date]" value="{{ old('menu.'.$loop->index.'.price_start_date', $menuPrice['start_date']) }}" autocomplete="off" required>
                            </div>
                            <div class="col-2"><span class="form-control-plaintext">～</span></div>
                            <div class="col-5"> <input type="text" style="width:100px" class="form-control price_end_date" name="menu[{{ $loop->index }}][price_end_date]" value="{{ old('menu.'.$loop->index.'.price_end_date', $menuPrice['end_date']) }}" autocomplete="off" required>
                            </div>
                        </div>
                        <label for="price">金額（税込）</label>
                        <div class="form-group row form-material form-inline pt-0">
                            <div class="col-11 col-md-3 pr-0">
                                <input type="text" class="form-control" name="menu[{{ $loop->index }}][price]" value="{{ old('menu.'.$loop->index.'.price', $menuPrice['price']) }}">
                            </div>
                            <div class="col-1 col-md-1 pl-0">
                                円
                            </div>
                        </div>
                        <div class="form-group row text-right">
                            <div class="col-12 col-md-12 text-right">
                                <button type="button" class="btn btn-sm btn-secondary btn-danger delete-confirm" data-id="{{ $menuPrice['id'] }}" data-menu_id="{{ $menu->id }}" data-title="料金コード{{ $loop->iteration }}" data-toggle="tooltip" title="Delete">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    @php $i = ($loop->count) - 1; @endphp
                    @endforeach
                </div>
                @else
                <div data-nodata=1>
                    <div class="form-group">
                        料金設定の登録がありません。
                    </div>
                    @php $i = 0; @endphp
                </div>
                @endif

                <!-- Start addForm -->
                <div id="menuPrice_form" class="mb-3">
                    @php
                    /* バリデーションエラー後の入力内容を取得し表示する */
                    $addInputCount = 0;
                    @endphp
                    @if (!empty(old('menu') ))
                    @foreach (old('menu') as $addIndex => $store)
                    @if (old('menu.'.$addIndex.'.price_id') === null)
                    <div class="add_form-group">
                        <div class="form-group row form-material col-12">
                            <input type="hidden" name="menu[{{$addIndex}}][add_price]">
                            <div class="col-md-4 pl-0 pt-5">
                                <label for="price_cd">料金コード（追加）<span class="badge bg-danger ml-2 text-white">必須</span></label>
                            </div>
                            <select class="form-control mt-5" name="menu[{{$addIndex}}][price_cd]">
                                <option value="">選択してください</option>
                                @foreach($menuPriceCodes as $key => $value)
                                <option value="{{ $key }}" {{ $key == old('menu.'.$addIndex.'.price_cd') ? 'selected' : '' }}>{{ $value }}</option>
                                @endforeach
                            </select>
                        </div>
                        <label for="menuPrices" class="mb-0">日付<span class="badge bg-danger ml-2 text-white">必須</span></label>
                        <div class="form-group row" id="menuPrices">
                            <div class="col-5 col-md-3">
                                <input type="text" class="form-control price_start_date datepicker" name="menu[{{$addIndex}}][price_start_date]" value="{{ old('menu.'.$addIndex.'.price_start_date') }}" autocomplete="off">
                            </div>
                            <span style="margin-top:8px">～</span>
                            <div class="col-5 col-md-3">
                                <input type="text" class="form-control price_end_date" name="menu[{{$addIndex}}][price_end_date]" value="{{ old('menu.'.$addIndex.'.price_end_date') }}" autocomplete="off">
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="form-material col-12">
                                <label for="price">金額（税込）<span class="badge bg-danger ml-2 text-white">必須</span></label>
                                <div class="d-flex pl-0 justify-content-between">
                                    <div class="d-flex"><input type="text" class="form-control" name="menu[{{$addIndex}}][price]" value="{{ old('menu.'.$addIndex.'.price') }}">
                                        <span class="m-3" id="minus-btn">円</span>
                                    </div>
                                    @if (!($loop->count == 1 && empty($menuPriceExists)))
                                    <div class="text-right mb-1"><button type="button" class="btn btn-sm btn-secondary remove-form" data-toggle="tooltip" title="Erase">
                                            <i class="fa fa-minus"></i></button></div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                    @php
                    $addInputCount++;
                    @endphp
                    @endforeach
                    @endif
                </div>
                <!-- End Form -->

                <div class="form-group text-right mb-3">
                    <button type="button" id="add" class="btn btn-sm btn-secondary" data-toggle="tooltip" title="Add">
                        <i class="fa fa-plus"></i>
                    </button>
                </div>
            </div>

        </div>

        <div class="col-md-9 d-flex justify-content-between">
            <div>
                <button type="button" class="btn btn-secondary" onclick="location.href='{{ session('menuPriceRedirectTo', route('admin.menu')) }}'">戻る</button>
            </div>
            <div style="padding-right: 20px;">
                <button type="submit" class="btn btn-alt-primary" id="save" value="save">保存</button>
            </div>
        </div>
    </form>
</div>
<div class="content item-list-sp">
    @include('admin.Layouts.flash_message')

    <!-- Default Table Style -->
    <div class="block" style="background-color: #f0f2f5; margin-bottom: 0;">
        <div class=" col-md-9 block-header">
            <h2 class="content-heading" style="margin-bottom: 0; padding-top: 0;">{{ $menu->name }} 料金設定</h2>
        </div>
    </div>
    <!-- Floating Labels -->
    <form id="price_form" action="{{ route('admin.menu.price.edit', ['id' => $menu->id]) }}" method="post">
        @csrf
        <div class="block col-md-9">

            <div class="block-content" id="dynamicForm">
                <input type="hidden" name="menu_id" value="{{ $menu->id }}">
                <input type="hidden" name="menu_name" value="{{ $menu->name }}">
                <input type="hidden" name="menu_published" id="menu_published-sp" value="{{ $menu->published }}">
                <input type="hidden" name="redirect_to" value="{{ old('redirect_to', url()->previous()) }}">
                @if(!empty($menuPriceExists))
                <div class="mb-3">
                    @foreach($menuPrices as $menuPrice)
                    <div class="box-input">
                        <input type="hidden" name="menu[{{ $loop->index }}][price_id]" value="{{ $menuPrice['id'] }}">
                        <div class="form-group">
                            <div class="form-material col-12 col-md-4 px-0">
                                <label for="price_cd">料金コード{{ $loop->iteration }}</label>
                                <select class="form-control" name="menu[{{ $loop->index }}][price_cd]">
                                    <option value="">選択してください</option>
                                    @foreach($menuPriceCodes as $key => $value)
                                    <option value="{{ $key }}" {{ $key == old('menu.'.$loop->index.'.price_cd', $menuPrice['price_cd']) ? 'selected' : '' }}>
                                        {{ $value }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <label for="menuPrices" class="mb-0">日付</label>
                        <div class="row form-material  align-items-center" style="margin-top:-26px;margin-bottom: 16px;">
                            <div class="col-5"> <input type="text" style="width:100px" class="form-control price_start_date" name="menu[{{ $loop->index }}][price_start_date]" value="{{ old('menu.'.$loop->index.'.price_start_date', $menuPrice['start_date']) }}" autocomplete="off" required>
                            </div>
                            <div class="col-2"><span class="form-control-plaintext">～</span></div>
                            <div class="col-5"> <input type="text" style="width:100px" class="form-control price_end_date" name="menu[{{ $loop->index }}][price_end_date]" value="{{ old('menu.'.$loop->index.'.price_end_date', $menuPrice['end_date']) }}" autocomplete="off" required>
                            </div>
                        </div>
                        <label for="price">金額（税込）</label>
                        <div class="form-group row form-material form-inline pt-0">
                            <div class="col-11 col-md-3 pr-0">
                                <input type="text" class="form-control" name="menu[{{ $loop->index }}][price]" value="{{ old('menu.'.$loop->index.'.price', $menuPrice['price']) }}">
                            </div>
                            <div class="col-1 col-md-1 pl-0">
                                円
                            </div>
                        </div>
                        <div class="form-group row text-right">
                            <div class="col-12 col-md-12 text-right">
                                <button type="button" class="btn btn-sm btn-secondary btn-danger delete-confirm" data-id="{{ $menuPrice['id'] }}" data-menu_id="{{ $menu->id }}" data-title="料金コード{{ $loop->iteration }}" data-toggle="tooltip" title="Delete">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    @php $i = ($loop->count) - 1; @endphp
                    @endforeach
                </div>
                @else
                <div data-nodata=1>
                    <div class="form-group">
                        料金設定の登録がありません。
                    </div>
                    @php $i = 0; @endphp
                </div>
                @endif

                <!-- Start addForm -->
                <div id="menuPrice_form" class="mb-3">
                    @php
                    /* バリデーションエラー後の入力内容を取得し表示する */
                    $addInputCount = 0;
                    @endphp
                    @if (!empty(old('menu') ))
                    @foreach (old('menu') as $addIndex => $store)
                    @if (old('menu.'.$addIndex.'.price_id') === null)
                    <div class="add_form-group">
                        <div class="form-group row form-material col-12">
                            <input type="hidden" name="menu[{{$addIndex}}][add_price]">
                            <div class="col-md-4 pl-0 pt-5">
                                <label for="price_cd">料金コード（追加）<span class="badge bg-danger ml-2 text-white">必須</span></label>
                            </div>
                            <select class="form-control mt-5" name="menu[{{$addIndex}}][price_cd]">
                                <option value="">選択してください</option>
                                @foreach($menuPriceCodes as $key => $value)
                                <option value="{{ $key }}" {{ $key == old('menu.'.$addIndex.'.price_cd') ? 'selected' : '' }}>{{ $value }}</option>
                                @endforeach
                            </select>
                        </div>
                        <label for="menuPrices" class="mb-0">日付<span class="badge bg-danger ml-2 text-white">必須</span></label>
                        <div class="form-group row" id="menuPrices">
                            <div class="col-5 col-md-3">
                                <input type="text" class="form-control price_start_date datepicker" name="menu[{{$addIndex}}][price_start_date]" value="{{ old('menu.'.$addIndex.'.price_start_date') }}" autocomplete="off">
                            </div>
                            <span style="margin-top:8px">～</span>
                            <div class="col-5 col-md-3">
                                <input type="text" class="form-control price_end_date" name="menu[{{$addIndex}}][price_end_date]" value="{{ old('menu.'.$addIndex.'.price_end_date') }}" autocomplete="off">
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="form-material col-12">
                                <label for="price">金額（税込）<span class="badge bg-danger ml-2 text-white">必須</span></label>
                                <div class="d-flex pl-0 justify-content-between">
                                    <div class="d-flex"><input type="text" class="form-control" name="menu[{{$addIndex}}][price]" value="{{ old('menu.'.$addIndex.'.price') }}">
                                        <span class="m-3" id="minus-btn">円</span>
                                    </div>
                                    @if (!($loop->count == 1 && empty($menuPriceExists)))
                                    <div class="text-right mb-1"><button type="button" class="btn btn-sm btn-secondary remove-form" data-toggle="tooltip" title="Erase">
                                            <i class="fa fa-minus"></i></button></div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                    @php
                    $addInputCount++;
                    @endphp
                    @endforeach
                    @endif
                </div>
                <!-- End Form -->

                <div class="form-group text-right mb-3">
                    <button type="button" id="add" class="btn btn-sm btn-secondary" data-toggle="tooltip" title="Add">
                        <i class="fa fa-plus"></i>
                    </button>
                </div>
            </div>

        </div>

        <div class="col-md-9 d-flex justify-content-between">
            <div>
                <button type="button" class="btn btn-secondary" onclick="location.href='{{ session('menuPriceRedirectTo', route('admin.menu')) }}'">戻る</button>
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
        console.log($('div.item-list-pc').css('display'));
        console.log($('div.item-list-sp').css('display'));
        // if ($('div.item-list-pc').css('display') == 'none') {
        //     $('div.item-list-pc').remove();
        // }
        // if ($('div.item-list-sp').css('display') == 'none') {
        //     $('div.item-list-sp').remove();
        // }
        let count = {{(!(empty($menuPriceExists)) || $addInputCount > 0) ? count($menuPrices) + $addInputCount: 0}};
        @if (empty($menuPriceExists) && $addInputCount <= 0)
        $('button[value=save]').hide();
        @else
        $('[data-nodata=1]').hide()
        @endif

        function dynamic_field(number) {
            console.log('dynamic_field('+number+')!!!!!!!!!!!');
            html = '<div class="add_form-group">';
            html += '<input type="hidden" name="menu[' + number + '][add_price]">';
            html += '<div class="form-group"><div class="form-material col-12 col-md-6 pl-0">';
            html += '<label for="price_cd">料金コード（追加）<span class="badge bg-danger ml-2 text-white">必須</span></label><select class="form-control mt-4" name="menu[' + number + '][price_cd]">';
            html += '<option value="">選択してください</option>';
            html += '@foreach($menuPriceCodes as $key => $value)';
            html += '<option value="{{ $key }}">{{ $value }}</option>';
            html += '@endforeach';
            html += '</select></div></div>';
            html += '<label for="menuPrices" class="mb-0">日付<span class="badge bg-danger ml-2 text-white">必須</span></label>';
            html += `<div class="form-group row justify-content-evenly" id="menuPrices">
                            <div class="col">
                            <input type="text" class="form-control price_start_date datepicker" name="menu[` + number + `][price_start_date]" autocomplete="off" style="width:100px">
                            </div>
                            <div class="col text-center pt-2 pl-0 pr-0">
                                <span style="width:14px">～</span>
                            </div>
                            <div class="col">
                            <input type="text" class="form-control price_end_date" name="menu[` + number + `][price_end_date]" autocomplete="off" style="width:100px">
                            </div>
                        </div>`;


            // html += '<div class="form-group row" id="menuPrices">';
            // html += '<div class="col-5 col-md-3"><input type="text" class="form-control price_start_date datepicker" name="menu[' + number + '][price_start_date]" autocomplete="off"></div>';
            // html += '<span style="margin-top:8px">～</span>';
            // html += '<div class="col-5 col-md-3"><input type="text" class="form-control price_end_date" name="menu[' + number + '][price_end_date]" autocomplete="off"></div></div>';
            html += '<div class="form-group"><div class="form-material">';
            html += '<label for="price">金額（税込）<span class="badge bg-danger ml-2 text-white">必須</span></label><div class="d-flex pl-0 justify-content-between">';
            html += '<div class="d-flex"><input type="text" class="form-control" name="menu[' + number + '][price]">';
            html += '<span class="m-3" id="minus-btn">円</span></div>';
            if (number > 1) {
                html += '<div class="text-right mb-1"><button type="button" class="btn btn-sm btn-secondary remove-form" data-toggle="tooltip" title="Erase">';
                html += '<i class="fa fa-minus"></i></button></div>';
            }
            html += '</div></div>';
            html += '</div>';

            if (number > 1) {
                $('#menuPrice_form').append(html);
            } else {
                $('#menuPrice_form').html(html);
            }
            $('[data-nodata=1]').hide();
            $('button[value=save]').show();
        }

        $(document).on('click', '#add', function() {
            count++;
            dynamic_field(count);
        });

        $(document).on('click', '.remove-form', function() {
            $(this).parents('.add_form-group').remove();
        });
    });


    /* ID被りを排除 */
    function update_ids() {
        if (!($('div.item-list-pc').length) || $('div.item-list-pc').css('display') == 'none') {
            $('div.item-list-pc').find('*').each(function(i,e) {
                if ($(this).attr('id')) {
                    $(this).attr('id', $(this).attr('id').split('_--_')[0]+'_--_' + i);
                }
                if ($(this).attr('name')) {
                    $(this).attr('name', $(this).attr('name').split('_--_')[0]+'_--_' + i);
                }
                if ($(this).attr('class')) {
                    $(this).attr('class', $(this).attr('class').split('_--_')[0]+'_--_' + i);
                }
            });
            $('div.item-list-sp').find('*').each(function(i,e) {
                if ($(this).attr('id')) {
                    $(this).attr('id', $(this).attr('id').split('_--_')[0]);
                }
                if ($(this).attr('name')) {
                    $(this).attr('name', $(this).attr('name').split('_--_')[0]);
                }
                if ($(this).attr('class')) {
                    $(this).attr('class', $(this).attr('class').split('_--_')[0]);
                }
            });
        } else {
            $('div.item-list-pc').find('*').each(function(i,e) {
                if ($(this).attr('id')) {
                    $(this).attr('id', $(this).attr('id').split('_--_')[0]);
                }
                if ($(this).attr('name')) {
                    $(this).attr('name', $(this).attr('name').split('_--_')[0]);
                }
                if ($(this).attr('class')) {
                    $(this).attr('class', $(this).attr('class').split('_--_')[0]);
                }
            });
            $('div.item-list-sp').find('*').each(function(i,e) {
                if ($(this).attr('id')) {
                    $(this).attr('id', $(this).attr('id').split('_--_')[0]+'_--_' + i);
                }
                if ($(this).attr('name')) {
                    $(this).attr('name', $(this).attr('name').split('_--_')[0]+'_--_' + i);
                }
                if ($(this).attr('class')) {
                    $(this).attr('class', $(this).attr('class').split('_--_')[0]+'_--_' + i);
                }
            });
        }

        // for debug
        console.log('PC!!!!!!!!');
        $('div.item-list-pc').find('*').each(function(i,e) {
            if ($(this).attr('id')) {
                console.log($(this).attr('id'));
            }
            if ($(this).attr('name')) {
                console.log($(this).attr('name'));
            }
        });
        console.log('SP!!!!!!!!');
        $('div.item-list-sp').find('*').each(function(i,e) {
            if ($(this).attr('id')) {
                console.log($(this).attr('id'));
            }
            if ($(this).attr('name')) {
                console.log($(this).attr('name'));
            }
        });
    }
    $(window).resize(function () {
        update_ids();
    });
    $(document).ready(function() {
        update_ids();
    });
</script>
@endsection
