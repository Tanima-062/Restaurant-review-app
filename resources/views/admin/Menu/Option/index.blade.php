@extends('admin.Layouts.base')
@include('admin.Layouts.head')

@include('admin.Layouts.side_overlay')
@include('admin.Layouts.sidebar')
@include('admin.Layouts.page_header')

@section('css')
<style>
    .content-heading {
        border-bottom: 0 !important;
    }

    /* pc 既存の表示への影響をしないよう */
    @media screen and (max-width: 1280px) {

        table.item-list-pc {
            table-layout: auto;
            width: 100%;
            display: inline-table;
        }

        table.item-list-sp {
            display: none;
        }

        .form-material>div .form-control,
        #menuOptions .form-control {
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

        .option-border {
            /*color: #575757;*/
            /*background-color: #fff;*/
            background-clip: padding-box;
            border: 1px solid #e4e7ed;
        }

        .option-border_last {
            border-bottom: 1px solid #e4e7ed;
        }

        .ajustment-plus-button {
            padding-left: 0 !important;
            padding-right: 0.35rem !important;
        }
    }
</style>
<style>
    /* sp 表示設定 */
    @media screen and (max-width: 960px) {

        table.item-list-pc {
            display: none;
        }

        table.item-list-sp {
            display: inline-table;
        }

        .table td,
        .table th {
            padding-left: 10px;
            padding-right: 10px;
            padding-top: 5px;
            padding-bottom: 5px;
            border-top: none !important;
        }

        .table-bordered {
            border: none !important;
        }

        .table-bordered td,
        .table-bordered th {
            border: none !important;
        }

        .form-control {
            height: auto !important;
        }

        .form-material>div,
        #menuOptions .form-control {
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

        .content-heading {
            border-bottom: 0;
        }

        .option-border {
            /*color: #575757;*/
            /*background-color: #fff;*/
            background-clip: padding-box;
            /* border: 1px solid #e4e7ed; */
        }

        .option-border_last {
            border-bottom: 1px solid #e4e7ed;
        }

        .ajustment-plus-button {
            padding-left: 0 !important;
            padding-right: 0.35rem !important;
        }

        .content {
            padding: 0 !important;
        }

        .content-heading {
            padding-left: 16px !important;

        }

        table.item-list-pc {
            display: none;
        }

        table.item-list-sp {
            display: block;
        }

        .block-content {
            padding: 20px 16px 1px;
        }

        table {
            margin-bottom: 0 !important;
        }

        .table.item-list-sp {
            width: 100%;
        }

        .table td,
        .table th {
            padding-left: 0;
            padding-right: 0;
            padding-top: 5px;
            padding-bottom: 5px;
        }

        .table td.search-btn {
            text-align: right;
        }

        .block-title {
            font-size: 1rem;
            font-weight: normal;
        }


        h3.block-title {
            font-size: 14px;
            font-weight: 400;
        }

        table.item-list-sp {
            width: 100%;
            table-layout: fixed;
        }

        div.block-content {
            padding-top: 0 !important;
        }

        table.item-list-sp td {
            font-size: 14px;
            /* display: block; */
            padding-top: 0 !important;
            padding-bottom: 4px !important;
            padding-left: 4px !important;
            padding-right: 4px !important;
            width: 100% !important;
        }

        /* table.item-list-sp td:first-child {} */
        table.item-list-sp tr {
            border-bottom: 1px solid #e4e7ed;
        }

        table.item-list-sp tr:last-child {
            border-bottom: none !important;
        }

        table.list-item {
            table-layout: fixed;
            width: 100%;
            margin-top: 8px;
            padding-bottom: 1rem !important;
            background-color: transparent !important;
        }

        table.list-item tr {
            border-bottom: none !important;
            padding-top: 0;
            padding-bottom: 0;
        }

        table.list-item td {
            /* width: auto !important; */
            /* padding-top: 0 !important;
            padding-bottom: 0 !important;
            padding-left: 1rem !important; */
        }

        table.list-item td.pencil-btn {
            text-align: right;
        }

        table.list-item-topping {
            table-layout: fixed !important;
            width: 100%;
            margin-top: 8px;
            padding-bottom: 1rem !important;
            background-color: transparent !important;
        }

        table.list-item-topping tr {
            border-bottom: none !important;
            padding-top: 0;
            padding-bottom: 0;
        }

        .fa-trash,
        .fa-pencil {
            font-size: 125%;
            /* width: 36px;
            height: 36px; */
        }
    }
</style>
@endsection

@section('content')
<!-- Content -->
<div class="content">
    <div class="content" style="max-width: 1680px">
        @include('admin.Layouts.flash_message')
        <!-- Default Table Style -->
        <h2 class="content-heading">{{ $menu->name }} オプション設定</h2>

        <div class="block-header">
            <h3 class="content-heading" style="margin-bottom: 0; padding-top: 0;">お好み</h3>
        </div>
        <!-- Floating Labels -->

        <!-- Start OKONOMI -->
        <div>
            <div class="block-header block-header-default">
                <h3 class="block-title">総件数 : {{ collect($menuOptionOkonomis)->count() }}件</h3>
                <div class="block-options">
                    <div class="block-options-item">
                        <a href="{{ route('admin.menu.option.okonomiKeyword.addForm', ['id' => $menu->id]) }}" class="btn btn-sm btn-secondary" data-toggle="tooltip" title="Add">
                            <i class="fa fa-plus"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="block-content block" style="padding-bottom: 20px;">
                <input type="hidden" name="menu_id" value="{{ $menu->id }}">
                <input type="hidden" name="menu_name" value="{{ $menu->name }}">
                <input type="hidden" name="redirect_to" value="{{ old('redirect_to', url()->previous()) }}">
                <!-- Start Table -->
                @if(! empty($menuOptionOkonomiExists))

                <!-- PC -->
                <table class="table table-bordered table-vcenter item-list-pc">
                    <thead>
                        <tr>
                            <th class="text-center table-secondary" style="width: 50px;">@sortablelink('id','#')</th>
                            <th class="text-center table-secondary" style="width: 100px;">@sortablelink('required','必須/任意')</th>
                            <th class="text-center table-secondary">@sortablelink('keyword_id', '項目')</th>
                            <th class="text-center table-secondary">@sortablelink('contents_id', '内容')</th>
                            <th class="text-center table-secondary" style="width: 150px;">@sortablelink('price','金額（税込）')</th>
                            <th class="text-center table-secondary" style="width: 50px;"></th>
                            <th class="text-center table-secondary" style="width: 200px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($menuOptionOkonomis as $groupedOkonomi)
                        @foreach($groupedOkonomi as $okonomi)
                        <tr>
                            @if($loop->first)
                            <th class="text-center option-border" rowspan="{{ count($groupedOkonomi) }}">{{ $okonomi->keyword_id }}</th>
                            <td class="text-center option-border" rowspan="{{ count($groupedOkonomi) }}">{{ $okonomi->required === 1 ? '必須' : '任意' }}</td>
                            <td class="text-center option-border" rowspan="{{ count($groupedOkonomi) }}">{{ $okonomi->keyword }}</td>
                            @endif
                            <td class="py-3 text-center {{ $loop->last ? "option-border_last" : null }}">
                                {{ $okonomi->contents }}
                            </td>
                            <td class="py-3 text-right {{ $loop->last ? "option-border_last" : null }}"" style=" padding-right: 25px">
                                {{ number_format($okonomi->price) }}
                                <span>円</span>
                            </td>
                            <td class="py-3 delete-confirm {{ $loop->last ? "option-border_last" : null }}"">
                                <button data-id=" {{ $okonomi->id }}" data-cd="{{ $okonomi->option_cd }}" data-keyword="{{ $okonomi->keyword }}" data-contents="{{ $okonomi->contents }}" type="button" class="btn btn-sm btn-secondary" data-toggle="tooltip" title="Delete">
                                <i class="fa fa-trash"></i>
                                </button>
                            </td>
                            @if($loop->first)
                            <td class="text-center py-3 option-border" rowspan="{{ count($groupedOkonomi) }}">
                                <span data-toggle="modal" data-target="#addContentsModal" class="add-contents-btn" data-id="{{ $okonomi->id }}" data-title="{{ $okonomi->keyword }}" data-url="{{ route('admin.menu.option.okonomiContents.add', ['id' => $okonomi->id]) }}">
                                    @if ($menu['app_cd'] !== key(config('code.appCd.rs')))
                                    <button type="button" class="btn btn-sm btn-secondary" data-toggle="tooltip">
                                        内容追加
                                    </button>
                                    @endif
                                </span>
                                <a href="{{ route('admin.menu.option.okonomi.editForm', ['id' => $okonomi->menu_id, 'keyword_id' => $okonomi->keyword_id]) }}" class="btn btn-sm btn-secondary" data-toggle="tooltip" title="Edit">
                                    <i class="fa fa-pencil"></i>
                                </a>
                            </td>
                            @endif
                        </tr>
                        @endforeach
                        @endforeach
                    </tbody>
                </table>
                <!-- SP -->
                <table class="table table-bordered table-vcenter item-list-sp">

                    <tbody>
                        @foreach($menuOptionOkonomis as $groupedOkonomi)

                        <tr>
                            <td>
                                <table class="list-item">
                                    <tr>
                                        <td colspan="3" style="font-weight:bold;width: 100%;">
                                            #{{ sprintf("%03d", $groupedOkonomi[0]->keyword_id) }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="3">
                                            {{ $groupedOkonomi[0]->required === 1 ? '必須' : '任意' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" style="font-weight:bold">
                                            {{ $groupedOkonomi[0]->keyword }}
                                        </td>
                                    </tr>
                                    @foreach($groupedOkonomi as $okonomi)
                                    <tr>
                                        <td class="pl-4 text-left {{ $loop->last ? "option-border_last" : null }}">
                                            　{{ $okonomi->contents }}
                                        </td>
                                        <td class="text-right {{ $loop->last ? "option-border_last" : null }}">
                                            {{ number_format($okonomi->price) }}円
                                        </td>
                                        <td style="width:20px !important" class="delete-confirm  text-right {{ $loop->last ? 'option-border_last' : null }}">
                                            <button data-id=" {{ $groupedOkonomi[0]->id }}" data-cd="{{ $groupedOkonomi[0]->option_cd }}" data-keyword="{{ $okonomi->keyword }}" data-contents="{{ $okonomi->contents }}" type="button" class="btn btn-sm btn-secondary" data-toggle="tooltip" title="Delete">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                        <!-- <td class="text-right {{ $loop->last ? "option-border_last" : null }}"" style=" padding-right: 25px">
                                            円
                                        </td> -->
                                    </tr>
                                    @endforeach
                                    <tr>
                                        <!-- <td class="text-center option-border" rowspan="{{ count($groupedOkonomi) }}"> -->
                                        <td colspan="3" class="text-right option-border">
                                            <span data-toggle="modal" data-target="#addContentsModal" class="add-contents-btn" data-id="{{ $groupedOkonomi[0]->id }}" data-title="{{ $groupedOkonomi[0]->keyword }}" data-url="{{ route('admin.menu.option.okonomiContents.add', ['id' => $groupedOkonomi[0]->id]) }}">
                                                @if ($menu['app_cd'] !== key(config('code.appCd.rs')))
                                                <button type="button" class="btn btn-sm btn-secondary" data-toggle="tooltip">
                                                    内容追加
                                                </button>
                                                @endif
                                            </span>

                                            <a href="{{ route('admin.menu.option.okonomi.editForm', ['id' => $groupedOkonomi[0]->menu_id, 'keyword_id' => $groupedOkonomi[0]->keyword_id]) }}" class="btn btn-sm btn-secondary" data-toggle="tooltip" title="Edit">
                                                <i class="fa fa-pencil"></i>
                                            </a>
                                        </td>
                                        <!-- <td class="delete-confirm {{ $loop->last ? 'option-border_last' : null }}">
                                            <button data-id=" {{ $groupedOkonomi[0]->id }}" data-cd="{{ $groupedOkonomi[0]->option_cd }}" data-keyword="{{ $okonomi->keyword }}" data-contents="{{ $okonomi->contents }}" type="button" class="btn btn-sm btn-secondary" data-toggle="tooltip" title="Delete">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.menu.option.okonomi.editForm', ['id' => $groupedOkonomi[0]->menu_id, 'keyword_id' => $groupedOkonomi[0]->keyword_id]) }}" class="btn btn-sm btn-secondary" data-toggle="tooltip" title="Edit">
                                                <i class="fa fa-pencil"></i>
                                            </a>
                                        </td> -->
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="block-content">
                <table class="table table-borderless table-vcenter">
                    <thead>
                        <tr>
                            <th class="text-center">お好みの登録がありません</th>
                        </tr>
                    </thead>
                </table>
            </div>
            @endif
            <!-- End Table -->
        </div>
        <!-- End Okonomi -->
        <!-- Start AddOkonomiContent -->
        @include('admin.Menu.Option.Okonomi.addContents')
        <!-- End AddOkonomiContent -->
    </div>

    <!-- Start TOPPING -->
    @if ($menu['app_cd'] !== key(config('code.appCd.rs')))
    <div class="@if(empty($menuOptionToppingExists) && ! empty($menuOptionOkonomiExists)) content
                        @elseif (! empty($menuOptionToppingExists) && ! empty($menuOptionOkonomiExists)) content @endif" style="max-width: 1680px">
        <div>
            <!-- Start Label -->
            <div class="block-header col-md-9">
                <h3 class="content-heading" style="margin-bottom: 0; padding-top: 0;">トッピング</h3>
            </div>
            <!-- End Label -->

            <!-- Start Table -->
            <div class=" col-12 pl-0 pr-0 col-md-9 pl-md-0">
                <div class="block-header block-header-default">
                    <h3 class="block-title">総件数 : {{ collect($menuOptionToppings)->count() }}件</h3>
                    <div class="block-options ajustment-plus-button">
                        <div class="block-options-item">
                            <a href="{{ route('admin.menu.option.topping.addForm', ['id' => $menu->id]) }}" class="btn btn-sm btn-secondary" data-toggle="tooltip" title="Add">
                                <i class="fa fa-plus"></i>
                            </a>
                        </div>
                    </div>
                </div>
                @if(! empty($menuOptionToppingExists))
                <form action="{{ route('admin.menu.option.topping.edit', ['id' => $menu->id]) }}" method="post">
                    @csrf
                    <div class="block-content block">
                        <input type="hidden" name="menu_id" value="{{ $menu->id }}">
                        <input type="hidden" name="menu_name" value="{{ $menu->name }}">
                        <input type="hidden" name="redirect_to" value="{{ old('redirect_to', url()->previous()) }}">
                        <table class="table table-borderless table-vcenter item-list-pc" style="margin-bottom: 0!important;">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 50px;">@sortablelink('id','#')</th>
                                    <th class="text-center">@sortablelink('contents_id', '内容')</th>
                                    <th class="text-center" style="width: 150px;">@sortablelink('price', '金額（税込）')</th>
                                    <th class="text-center" style="width: 50px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($menuOptionToppings as $menuOptionTopping)
                                <tr>
                                    <input type="hidden" name="menuOptionTopping[{{ $loop->index }}][option_id]" value="{{ $menuOptionTopping['id'] }}">
                                    <th class="text-center pt-0" scope="row">{{ $menuOptionTopping['contents_id'] }}</th>
                                    <td class="text-center pt-0">
                                        <div class="form-group">
                                            <div class="form-material pt-0">
                                                <input type="text" class="form-control" name="menuOptionTopping[{{ $loop->index }}][contents]" value="{{ old('menuOptionTopping.'.$loop->index.'.contents', $menuOptionTopping['contents']) }}">
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center pt-0">
                                        <div class="form-group">
                                            <div class="form-material pt-0">
                                                <div class="d-flex pl-0 justify-content-between" style="padding-top: 12px;">
                                                    <input type="text" class="form-control text-right" name="menuOptionTopping[{{ $loop->index }}][price]" value="{{ old('menuOptionTopping.'.$loop->index.'.price', $menuOptionTopping['price']) }}">
                                                    <span class="m-3">円</span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center pt-0">
                                        <div class="btn-group delete-confirm">
                                            <button data-id="{{ $menuOptionTopping['id'] }}" data-cd="{{ $menuOptionTopping['option_cd'] }}" data-keyword="{{ $menuOptionTopping['keyword'] }}" data-contents="{{ $menuOptionTopping['contents'] }}" type="button" class="btn btn-sm btn-secondary btn-danger" data-toggle="tooltip" title="Delete">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <table class="table table-borderless table-vcenter item-list-sp style=" margin-bottom: 0!important;">
                            <tbody>
                                @foreach($menuOptionToppings as $menuOptionTopping)
                                <tr style="border:none;">
                                    <td>
                                        <input type="hidden" name="menuOptionTopping[{{ $loop->index }}][option_id]" value="{{ $menuOptionTopping['id'] }}">
                                        <table class="list-item-topping">
                                            <tr>
                                                <td colspan="3" style="width: 90% !important;">
                                                    <b>#{{ sprintf("%03d", $menuOptionTopping['contents_id']) }}</b>
                                                </td>
                                                <td rowspan="2" class="text-right pt-0" style="width: 10% !important;">
                                                    <div class="btn-group delete-confirm">
                                                    <button data-id="{{ $menuOptionTopping['id'] }}" data-cd="{{ $menuOptionTopping['option_cd'] }}" data-keyword="{{ $menuOptionTopping['keyword'] }}" data-contents="{{ $menuOptionTopping['contents'] }}" type="button" class="btn btn-sm btn-secondary btn-danger" data-toggle="tooltip" title="Delete">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-center form-material" style="width:60% !important">
                                                    <!-- <div class="form-group">
                                                        <div class="form-material pt-0"> -->
                                                    <input type="text" class="form-control" name="menuOptionTopping[{{ $loop->index }}][contents]" value="{{ old('menuOptionTopping.'.$loop->index.'.contents', $menuOptionTopping['contents']) }}">
                                                    <!-- </div>
                                                    </div> -->
                                                </td>
                                                <!-- <td class="text-center form-material d-flex justify-content-between"> -->
                                                <td class="text-center form-material" style="width:25% !important">
                                                    <!-- <div class="form-group">
                                                        <div class="form-material pt-0">
                                                            <div class="d-flex pl-0 justify-content-between" style="padding-top: 12px;"> -->
                                                    <input type="text" class="form-control text-right" name="menuOptionTopping[{{ $loop->index }}][price]" value="{{ old('menuOptionTopping.'.$loop->index.'.price', $menuOptionTopping['price']) }}">
                                                    <!-- <span class="m-3">円</span> -->
                                                    <!-- </div>
                                                        </div>
                                                    </div> -->
                                                </td>
                                                <td class="text-center form-material" style="width:5% !important;text-align:left;">
                                                    円
                                                </td>

                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-between mb-4">
                        <div style="padding-left: 20px;">
                            <button type="button" class="btn btn-secondary" onclick="location.href='{{ session('menuOptionRedirectTo', route('admin.menu')) }}'">戻る</button>
                        </div>
                        @if(! empty($menuOptionExists))
                        <div style="padding-right: 20px;">
                            <button type="submit" class="btn btn-alt-primary" id="save" value="save">保存</button>
                        </div>
                        @endif
                    </div>
                </form>
                @else
                <div class="block-content block">
                    <table class="table table-borderless table-vcenter">
                        <thead>
                            <tr>
                                <th class="text-center">トッピングの登録がありません</th>
                            </tr>
                        </thead>
                    </table>
                </div>
                <div class="d-flex justify-content-between">
                    <div>
                        <button type="button" class="btn btn-secondary" onclick="location.href='{{ session('menuOptionRedirectTo', route('admin.menu')) }}'">戻る</button>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
    <!-- End Topping -->
    @endif

    @if ($menu['app_cd'] === key(config('code.appCd.rs')))
    <div class="d-flex justify-content-between">
        <div>
            <button type="button" class="btn btn-secondary" onclick="location.href='{{ session('menuOptionRedirectTo', route('admin.menu')) }}'">戻る</button>
        </div>
    </div>
    @endif
</div>
<!-- End Content -->

@include('admin.Layouts.js_files')

@endsection

@include('admin.Layouts.footer')

@section('js')
<script src="{{ asset('vendor/admin/assets/js/common.js').'?'.time() }}"></script>
<script src="{{ asset('vendor/admin/assets/js/menuOption.js').'?'.time() }}"></script>
@endsection
