@extends('admin.Layouts.base')
@include('admin.Layouts.head')

@include('admin.Layouts.side_overlay')
@include('admin.Layouts.sidebar')
@include('admin.Layouts.page_header')
@section('css')
<style>
    /* pc 既存の表示への影響をしないよう */
    @media screen and (min-width: 961px) {

        table.item-list-pc {
            display: block;
        }

        table.item-list-sp {
            display: none;
        }
    }

    /* sp 表示設定 */
    @media screen and (max-width: 960px) {
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

        table.item-list-sp a {
            /* padding: 8px 8px !important; */
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
            /* padding-left: 10px;
            padding-right: 10px; */
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

        table.list-item {
            table-layout: fixed;
            width: 100%;
            padding-bottom: 2rem !important;
            border-bottom: 1px solid #e4e7ed;
            background-color: transparent !important;
            margin-bottom: 8px !important;
        }

        table.list-item tr {
            padding-top: 0;
            padding-bottom: 0;
        }

        table.list-item td {
            /* width: auto !important; */
            padding-top: 0;
            padding-bottom: 0;
        }

        table.list-item td.pencil-btn {
            text-align: right;
        }

        h3.block-title {
            font-size: 14px;
            font-weight: 400;
        }

        #published {
            margin-bottom: 8px;
        }

        .btn-group-sm>.btn,
        .btn-sm {
            /* width: 30px !important; */
        }
    }

    input.form-control,
    select.form-control,
    button {
        margin-bottom: 8px;
    }
</style>
@endsection
@section('content')
<!-- Content -->
<div class="content">
    @include('admin.Layouts.flash_message')
    <!-- Default Table Style -->
    <h2 class="content-heading">メニュー一覧</h2>
    <!-- Floating Labels -->
    <div class="block">
        <div class="block-content block-content-full">
            <form action="{{ route('admin.menu') }}" method="get" class="form-inline">
                <div class="row">
                    <div class="col-12">
                        <input class="form-control mr-sm-2" name="id" placeholder="ID" value="{{ old('id', \Request::query('id')) }}">
                        <select class="form-control mr-sm-2" name="app_cd">
                            <option value="">利用サービス</option>
                            @foreach(config('code.appCd') as $key => $appCd)
                            @php if(strlen($key) > 2) continue; @endphp
                            <option value="{{ strtoupper($key) }}" {{ old('app_cd', \Request::query('app_cd')) == strtoupper($key) ? 'selected' : '' }}>{{ $appCd[strtoupper($key)] }}</option>
                            @endforeach
                        </select>
                        <input class="form-control mr-sm-2" name="name" placeholder="名前" value="{{ old('name', \Request::query('name')) }}">
                        @can('inAndOutHouseGeneral-only')
                        <input class="form-control mr-sm-2" name="store_name" placeholder="店舗名" value="{{ old('store_name', \Request::query('store_name')) }}">
                        @endcan
                    </div>
                    <div class="col-12 text-right">
                        <button type="submit" class="btn btn-alt-primary" name="type" value="search">検索する</button>&nbsp;
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Table -->
    <div class="block">
        <div class="block-header block-header-default">
            <h3 class="block-title">総件数 : {{ $menus->total() }}件</h3>
            <div class="block-options">
                <div class="block-options-item">
                    <a href="{{ route('admin.menu.add') }}" class="btn btn-sm btn-secondary" data-toggle="tooltip" title="Add">
                        <i class="fa fa-plus"></i>
                    </a>
                </div>
            </div>
        </div>
        <div class="block-content">
            <table class="table table-borderless table-vcenter item-list-sp">
                <tbody>
                    @foreach ($menus as $menu)
                    <tr>
                        <td>
                            <table class="table list-item">
                                <tr>
                                    <td>
                                        <b>#{{ sprintf("%03d", $menu->id) }}</b>
                                    </td>
                                    <td class="text-right">
                                        {{ $menu->updated_at }}
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        {{ $menu->name }}
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2">

                                        {{ config('code.appCd.'.strtolower($menu->app_cd))[$menu->app_cd] }}
                                    </td>
                                </tr>
                                @can('inAndOutHouseGeneral-only')
                                <tr>
                                    <td colspan="2">

                                        {{ optional($menu->store)->name }}
                                    </td>
                                </tr>
                                @endcan
                                @if (Gate::check('inHouseGeneral-higher'))
                                @if (!empty($menu->anotherStaff->name))
                                <tr>
                                    <td colspan="2">

                                        {{ $menu->anotherStaff->name }}
                                    </td>
                                </tr>
                                @else
                                <td colspan="2">
                                </td>
                                @endif
                                @endif
                                @can('outHouseGeneral-onlySelf', [$menu->staff_id])
                                <tr>
                                    <td colspan="2" class="text-left pt-5">
                                        <div class="btn-group d-flex" style="width:100%">
                                            @if ($menu->app_cd === key(config('code.appCd.to')))
                                            <a href="{{ route('admin.menu.option', ['id' => $menu->id]) }}" class="btn btn-sm btn-secondary flex-fill" data-toggle="tooltip">
                                                オプション
                                            </a>
                                            @else
                                            <a class="btn btn-sm btn-secondary flex-fill" data-toggle="tooltip" title="レストランメニューにはオプションを設定できません" style="color: #999">
                                                オプション
                                            </a>
                                            @endif
                                            <a href="{{ route('admin.menu.genre.edit', ['id' => $menu->id]) }}" class="btn btn-sm btn-secondary flex-fill" data-toggle="tooltip">
                                                ジャンル
                                            </a>
                                            <a href="{{ route('admin.menu.image.editForm', ['id' => $menu->id]) }}" class="btn btn-sm btn-secondary flex-fill" data-toggle="tooltip">
                                                画像
                                            </a>
                                            <a href="{{ route('admin.menu.price.editForm', ['id' => $menu->id]) }}" class="btn btn-sm btn-secondary flex-fill" data-toggle="tooltip">
                                                料金
                                            </a>
                                            @if ($menu->app_cd === key(config('code.appCd.to')))
                                            <a href="{{ route('admin.menu.stock', ['id' => $menu->id]) }}" class="btn btn-sm btn-secondary flex-fill" data-toggle="tooltip">
                                                在庫
                                            </a>
                                            @else
                                            <a class="btn btn-sm btn-secondary flex-fill" data-toggle="tooltip" title="レストランメニューには在庫を設定できません" style="color: #999">
                                                在庫
                                            </a>
                                            @endif

                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-left pt-4 pb-4">
                                        <div class="text-left">
                                            @if($common['isPublishable'] === 1)
                                            @if ($menu->published === 0)
                                            <form action="{{ route('admin.menu.publish',['id' => $menu->id]) }}" name="set_published" method="post">
                                                @csrf
                                                <label class="css-control css-control css-control-primary css-switch"
                                                    >
                                                    <input type="hidden" id="published" name="published" value="1">
                                                    <input type="checkbox" class="css-control-input" value="1">
                                                    <span class="css-control-indicator" onclick="publish_alert_sp(event);return false"></span> 非公開
                                                </label>
                                            </form>
                                            @endif

                                            @if ($menu->published === 1)
                                            <form action="{{ route('admin.menu.private',['id' => $menu->id]) }}" name="set_private" method="post">
                                                @csrf
                                                <label class="css-control css-control css-control-primary css-switch"
                                                    >
                                                    <input type="hidden" id="published" name="published" value="0">
                                                    <input type="checkbox" class="css-control-input" value="0" checked>
                                                    <span class="css-control-indicator" onclick="private_alert_sp(event);return false"></span> 公開
                                                </label>
                                            </form>
                                            @endif
                                            @endif
                                        </div>
                                        <!-- @if($common['isPublishable'] === 1)
                                        @if ($menu->published === 0)
                                        <form action="{{ route('admin.menu.publish',['id' => $menu->id]) }}" name="set_published" method="post">
                                            @csrf
                                            <input type="hidden" id="published" name="published" value="1">
                                            <button class="btn btn-sm btn-secondary" data-toggle="tooltip" title="公開する" onClick="publish_alert(event);return false">
                                                &nbsp;&nbsp;公開&nbsp;&nbsp;
                                            </button>
                                        </form>
                                        @endif

                                        @if ($menu->published === 1)
                                        <form action="{{ route('admin.menu.private',['id' => $menu->id]) }}" name="set_private" method="post">
                                            @csrf
                                            <input type="hidden" id="published" name="published" value="0">
                                            <button class="btn btn-sm btn-secondary" data-toggle="tooltip" title="非公開にする" onClick="private_alert(event);return false">
                                                非公開
                                            </button>
                                        </form>
                                        @endif
                                        @endif -->
                                    </td>
                                    <td class="text-right pt-4 pb-4">
                                        <!-- <button type="button" class="btn btn-sm btn-secondary delete-confirm" data-id="{{ $menu->id }}" data-name="{{ $menu->name }}" data-toggle="tooltip" title="Delete">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                        <a href="{{ route('admin.menu.edit', ['id' => $menu->id]) }}" class="btn btn-sm btn-secondary" data-toggle="tooltip" title="Edit">
                                            <i class="fa fa-pencil"></i>
                                        </a> -->
                                        <table class="float-right inline">
                                            <tr>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-secondary delete-confirm" data-id="{{ $menu->id }}" data-name="{{ $menu->name }}" data-toggle="tooltip" title="Delete">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </td>
                                                <td style="padding-bottom:8px;padding-left:8px">
                                                    <a href="{{ route('admin.menu.edit', ['id' => $menu->id]) }}" class="btn btn-sm btn-secondary" data-toggle="tooltip" title="Edit">
                                                        <i class="fa fa-pencil"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>

                                    </td>
                                </tr>
                                @endcan
                            </table>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>


            <table class="table table-borderless table-vcenter item-list-pc">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 50px;">@sortablelink('id','#')</th>
                        <th>@sortablelink('name','名前')</th>
                        <th>@sortablelink('settlementCompanies','利用サービス')</th>
                        @can('inAndOutHouseGeneral-only')
                        <th>@sortablelink('tel','店舗名')</th>
                        @endcan
                        @if (Gate::check('inHouseGeneral-higher'))
                        <th>@sortablelink('staff_name','最終更新ユーザー')</th>
                        @endif
                        <th>@sortablelink('updated_at','更新日時')</th>
                        <th class="text-center" style="width: 200px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($menus as $menu)
                    <tr @if(!$menu->published) class="table-dark" @endif>
                        <th class="text-center" scope="row">{{ $menu->id }}</th>
                        <td>{{ $menu->name }}</td>
                        <td>{{ config('code.appCd.'.strtolower($menu->app_cd))[$menu->app_cd] }}</td>
                        @can('inAndOutHouseGeneral-only')
                        <td>{{ optional($menu->store)->name }}</td>
                        @endcan
                        @if (Gate::check('inHouseGeneral-higher'))
                        @if (!empty($menu->anotherStaff->name))
                        <td>{{ $menu->anotherStaff->name }}</td>
                        @else
                        <td></td>
                        @endif
                        @endif
                        <td>{{ $menu->updated_at }}</td>
                        @can('outHouseGeneral-onlySelf', [$menu->staff_id])
                        <td class="text-center">
                            <div class="btn-group">
                                @if($common['isPublishable'] === 1)
                                @if ($menu->published === 0)
                                <form action="{{ route('admin.menu.publish',['id' => $menu->id]) }}" name="set_published" method="post">
                                    @csrf
                                    <input type="hidden" id="published" name="published" value="1">
                                    <button class="btn btn-sm btn-secondary" data-toggle="tooltip" title="公開する" onClick="publish_alert(event);return false">
                                        &nbsp;&nbsp;公開&nbsp;&nbsp;
                                    </button>
                                </form>
                                @endif

                                @if ($menu->published === 1)
                                <form action="{{ route('admin.menu.private',['id' => $menu->id]) }}" name="set_private" method="post">
                                    @csrf
                                    <input type="hidden" id="published" name="published" value="0">
                                    <button class="btn btn-sm btn-secondary" data-toggle="tooltip" title="非公開にする" onClick="private_alert(event);return false">
                                        非公開
                                    </button>
                                </form>
                                @endif
                                @endif
                                @if ($menu->app_cd === key(config('code.appCd.to')))
                                <a href="{{ route('admin.menu.option', ['id' => $menu->id]) }}" class="btn btn-sm btn-secondary" data-toggle="tooltip">
                                    オプション
                                </a>
                                @else
                                <a class="btn btn-sm btn-secondary" data-toggle="tooltip" title="レストランメニューにはオプションを設定できません" style="color: #999">
                                    オプション
                                </a>
                                @endif
                                <a href="{{ route('admin.menu.genre.edit', ['id' => $menu->id]) }}" class="btn btn-sm btn-secondary" data-toggle="tooltip">
                                    ジャンル
                                </a>
                                <a href="{{ route('admin.menu.image.editForm', ['id' => $menu->id]) }}" class="btn btn-sm btn-secondary" data-toggle="tooltip">
                                    画像
                                </a>
                                <a href="{{ route('admin.menu.price.editForm', ['id' => $menu->id]) }}" class="btn btn-sm btn-secondary" data-toggle="tooltip">
                                    料金
                                </a>
                                @if ($menu->app_cd === key(config('code.appCd.to')))
                                <a href="{{ route('admin.menu.stock', ['id' => $menu->id]) }}" class="btn btn-sm btn-secondary" data-toggle="tooltip">
                                    在庫
                                </a>&nbsp;
                                @else
                                <a class="btn btn-sm btn-secondary" data-toggle="tooltip" title="レストランメニューには在庫を設定できません" style="color: #999">
                                    在庫
                                </a>&nbsp;
                                @endif
                                <a href="{{ route('admin.menu.edit', ['id' => $menu->id]) }}" class="btn btn-sm btn-secondary" data-toggle="tooltip" title="Edit">
                                    <i class="fa fa-pencil"></i>
                                </a>&nbsp;
                                <button type="button" class="btn btn-sm btn-secondary delete-confirm" data-id="{{ $menu->id }}" data-name="{{ $menu->name }}" data-toggle="tooltip" title="Delete">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                        </td>
                        @endcan
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="block-content block-content-full block-content-sm bg-body-light font-size-md">
            {{ $menus->appends(\Request::except('page'))->render() }}
        </div>
    </div>
    <!-- END Table -->

</div>
<!-- END Content -->

@endsection

@section('js')
<script src="{{ asset('vendor/admin/assets/js/menu.js').'?'.time() }}"></script>
<script>
    function publish_alert(e) {
        if (!window.confirm('本当に公開しますか？')) {
            return false;
        }
        document.set_published.submit();
        return true;
    };

    function private_alert(e) {
        if (!window.confirm('本当に非公開にしますか？')) {
            return false;
        }
        document.set_private.submit();
        return true;
    };

    function publish_alert_sp(e) {
        if (!window.confirm('本当に公開しますか？')) {
            return false;
        }
        e.target.closest('form').submit();
        return true;
    };

    function private_alert_sp(e) {
        if (!window.confirm('本当に非公開にしますか？')) {
            return false;
        }
        e.target.closest('form').submit();
        return true;
    };
</script>
@endsection

@include('admin.Layouts.footer')
