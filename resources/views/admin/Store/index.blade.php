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
            display: inline-table;
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
            display: inline-table;
        }

        .block-content {
            padding: 20px 16px 1px;
        }

        table {
            margin-bottom: 0 !important;
        }

        .table.item-list-sp {
            table-layout: fixed;
            width: 100%;
        }

        .table.item-list-sp td {
            width: 100% !important;
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

        table.list-item {
            table-layout: fixed;
            width: 100%;
            padding-bottom: 1rem !important;
            border-bottom: 1px solid #e4e7ed;
            background-color: transparent !important;
        }

        table.list-item tr {
            padding-top: 0;
            padding-bottom: 0;
        }

        table.list-item td {
            width: auto !important;
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

        table.item-list-sp .btn-group a {
            font-size: 82%;
            display: block;
            padding: 4px 2px !important;
            padding-top: 5px !important;
        }

        .fa-trash,
        .fa-pencil {
            font-size: 125%;
            /* width: 36px;
            height: 36px; */
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
<div class="content" style="max-width: 1680px">
    @include('admin.Layouts.flash_message')
    <!-- Default Table Style -->
    <h2 class="content-heading">@can('inAndOutHouseGeneral-only')店舗一覧@else基本情報管理@endcan</h2>
    <!-- Floating Labels -->
    @can('inAndOutHouseGeneral-only')
    <div class="block">
        <div class="block-content block-content-full">
            <form action="{{ route('admin.store') }}" method="get" class="form-inline">
                <div class="row">
                    <div class="col-12">
                        <input class="form-control mr-sm-2" name="id" placeholder="ID" value="{{ old('id', \Request::query('id')) }}">
                        <input class="form-control mr-sm-2" name="name" placeholder="店舗名" value="{{ old('name', \Request::query('name')) }}">
                        <input class="form-control mr-sm-2" name="settlement_company_name" placeholder="精算会社名" value="{{ old('settlement_company_name', \Request::query('settlement_company_name')) }}">
                        <input class="form-control mr-sm-2" name="code" placeholder="店舗コード" value="{{ old('code', \Request::query('code')) }}">
                    </div>
                    <div class="col-12 text-right">
                        <button type="submit" class="btn btn-alt-primary" name="type" value="search">検索する</button>&nbsp;
                    </div>
                </div>
            </form>
        </div>
    </div>
    @endcan

    <!-- Table -->
    <div class="block">
        <div class="block-header block-header-default">
            <h3 class="block-title">総件数 : {{ $stores->total() }}件</h3>
            @can('inAndOutHouseGeneral-only')
            <div class="block-options">
                <div class="block-options-item">
                    <a href="{{ route('admin.store.add') }}" class="btn btn-sm btn-secondary" data-toggle="tooltip" title="Add">
                        <i class="fa fa-plus"></i>
                    </a>
                </div>
            </div>
            @endcan
        </div>
        <div class="block-content">
            <table class="table table-borderless table-vcenter item-list-pc">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 50px;">@sortablelink('id','#')</th>
                        <th>@sortablelink('name','店舗名')</th>
                        <th>@sortablelink('settlementCompany','精算会社名')</th>
                        <th>@sortablelink('code','店舗コード')</th>
                        @if (Gate::check('inHouseGeneral-higher'))
                        <th>@sortablelink('staff_name','最終更新ユーザー')</th>
                        @endif
                        <th>@sortablelink('updated_at','更新日時')</th>
                        <th class="text-center" style="width: 200px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($stores as $store)
                    <tr @if ($store->published === 0) class="table-dark" @endif>
                        <th class="text-center" scope="row">{{ $store->id }}</th>
                        <td>{{ $store->name }}</td>
                        <td>{{ optional($store->settlementCompany)->name }}</td>
                        <td>{{ $store->code }}</td>
                        @if (Gate::check('inHouseGeneral-higher'))
                        @if (!empty($store->anotherStaff->name))
                        <td>{{ $store->anotherStaff->name }}</td>
                        @else
                        <td></td>
                        @endif
                        @endif
                        <td>{{ $store->updated_at }}</td>
                        @can('outHouseGeneral-onlySelf', [$store->staff_id])
                        <td class="text-left">
                            <div class="btn-group">
                                @if($common['isPublishable'] === 1)
                                @if ($store->published === 0)
                                <form action="{{ route('admin.store.publish',['id' => $store->id]) }}" method="post">
                                    @csrf
                                    <input type="hidden" id="published" name="published" value="1">
                                    <button class="btn btn-sm btn-secondary" data-toggle="tooltip" title="公開する" onClick="publish_alert(event);return false">
                                        &nbsp;&nbsp;公開&nbsp;&nbsp;
                                    </button>
                                </form>
                                @endif

                                @if ($store->published === 1)
                                <form action="{{ route('admin.store.private',['id' => $store->id]) }}" method="post">
                                    @csrf
                                    <input type="hidden" id="published" name="published" value="0">
                                    <button class="btn btn-sm btn-secondary" data-toggle="tooltip" title="非公開にする" onClick="private_alert(event);return false">
                                        非公開
                                    </button>
                                </form>
                                @endif
                                @endif
                                <a href="{{ route('admin.store.cancelFee',['id' => $store->id]) }}" class="btn btn-sm btn-secondary" data-toggle="tooltip">
                                    キャンセル料
                                </a>
                                <a href="{{ route('admin.store.genre.edit', ['id' => $store->id]) }}" class="btn btn-sm btn-secondary" data-toggle="tooltip">
                                    ジャンル
                                </a>
                                <a href="{{ route('admin.store.image.editForm', ['id' => $store->id]) }}" class="btn btn-sm btn-secondary" data-toggle="tooltip">
                                    画像
                                </a>
                                <a href="{{ route('admin.store.open.editForm', ['id' => $store->id]) }}" class="btn btn-sm btn-secondary" data-toggle="tooltip">
                                    営業時間
                                </a>
                                @can('inAndOutHouseGeneral-only')
                                <a href="{{ route('admin.store.api.editForm', ['id' => $store->id]) }}" class="btn btn-sm btn-secondary" data-toggle="tooltip">
                                    API設定
                                </a>
                                @endcan
                                @if(is_null($store->external_api) && $store->app_cd != key(config('code.appCd.to')))
                                <a href="{{ route('admin.store.vacancy', ['id' => $store->id]) }}" class="btn btn-sm btn-secondary" data-toggle="tooltip">
                                    空席
                                </a>
                                @else
                                <a style="background-color:#999; color: rgb(69, 69, 69)" class="btn btn-sm btn-secondary" data-toggle="tooltip">
                                    空席
                                </a>
                                @endif
                                &nbsp;
                                <a href="{{ route('admin.store.edit', ['id' => $store->id]) }}" class="btn btn-sm btn-secondary" data-toggle="tooltip" title="Edit">
                                    <i class="fa fa-pencil"></i>
                                </a>&nbsp;
                                {{-- <span data-toggle="modal" data-target="#deleteModal"--}}
                                {{-- data-title="{{ $store->name }}" data-url="{{ route('admin.store.delete', ['id' => $store->id]) }}">--}}
                                @can('outHouseGeneral-onlySelf', [$store->staff_id])
                                <button type="button" class="btn btn-sm btn-secondary delete-confirm" data-id="{{ $store->id }}" data-title="{{ $store->name }}" data-toggle="tooltip" title="Delete">
                                    <i class="fa fa-trash"></i>
                                </button>
                                @endcan
                                {{-- </span>--}}
                            </div>
                        </td>
                        @endcan
                    </tr>
                    @endforeach
                </tbody>
            </table>



            <table class="table table-borderless table-vcenter item-list-sp">
                <tbody>
                    @foreach ($stores as $store)

                    <tr>
                        <td>
                            <table class="table list-item">
                                <tr>
                                    <td colspan="2">
                                        <b>#{{ sprintf("%03d", $store->id) }}</b>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        {{ $store->name }}
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        {{ optional($store->settlementCompany)->name }}
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        {{ $store->code }}
                                    </td>
                                </tr>

                                @if (Gate::check('inHouseGeneral-higher'))
                                @if (!empty($store->anotherStaff->name))
                                <tr>
                                    <td colspan="2">{{ $store->anotherStaff->name }}</td>
                                </tr>
                                @else
                                <tr>
                                    <td colspan="2"></td>
                                </tr>
                                @endif
                                @endif
                                <tr>
                                    <td colspan="2">
                                        {{ $store->updated_at }}
                                    </td>
                                </tr>
                                @can('outHouseGeneral-onlySelf', [$store->staff_id])
                                <tr>
                                    <td colspan="2" class="text-left pb-5">
                                        <div class="btn-group d-flex pt-5 pb-5">

                                            <a href="{{ route('admin.store.cancelFee',['id' => $store->id]) }}" class="btn btn-sm btn-secondary flex-fill" data-toggle="tooltip">
                                                キャンセル料
                                            </a>
                                            <a href="{{ route('admin.store.genre.edit', ['id' => $store->id]) }}" class="btn btn-sm btn-secondary flex-fill" data-toggle="tooltip">
                                                ジャンル
                                            </a>
                                            <a href="{{ route('admin.store.image.editForm', ['id' => $store->id]) }}" class="btn btn-sm btn-secondary flex-fill" data-toggle="tooltip">
                                                画像
                                            </a>
                                            <a href="{{ route('admin.store.open.editForm', ['id' => $store->id]) }}" class="btn btn-sm btn-secondary flex-fill" data-toggle="tooltip">
                                                営業時間
                                            </a>
                                            @can('inAndOutHouseGeneral-only')
                                            <a href="{{ route('admin.store.api.editForm', ['id' => $store->id]) }}" class="btn btn-sm btn-secondary flex-fill" data-toggle="tooltip">
                                                API設定
                                            </a>
                                            @endcan
                                            @if(is_null($store->external_api) && $store->app_cd != key(config('code.appCd.to')))
                                            <a href="{{ route('admin.store.vacancy', ['id' => $store->id]) }}" class="btn btn-sm btn-secondary flex-fill" data-toggle="tooltip">
                                                空席
                                            </a>
                                            @else
                                            <a style="background-color:#999; color: rgb(69, 69, 69)" class="btn btn-sm btn-secondary flex-fill" data-toggle="tooltip">
                                                空席
                                            </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-left pt-6 pb-4">
                                        <!-- <div class="text-left"> -->
                                        <label class="css-control css-control css-control-primary css-switch">
                                            <input type="checkbox" class="css-control-input" id="published" name="published" value="1" checked="">
                                            <span class="css-control-indicator"></span> 公開する
                                        </label>
                                        <!-- </div> -->
                                        <!-- @if($common['isPublishable'] === 1)
                                            @if ($store->published === 0)
                                            <form action="{{ route('admin.store.publish',['id' => $store->id]) }}" method="post">
                                                @csrf
                                                <input type="hidden" id="published" name="published" value="1">
                                                <button class="btn btn-sm btn-secondary" data-toggle="tooltip" title="公開する" onClick="publish_alert(event);return false">
                                                    &nbsp;&nbsp;公開&nbsp;&nbsp;
                                                </button>
                                            </form>
                                            @endif
                                            @if ($store->published === 1)
                                            <form action="{{ route('admin.store.private',['id' => $store->id]) }}" method="post">
                                                @csrf
                                                <input type="hidden" id="published" name="published" value="0">
                                                <button class="btn btn-sm btn-secondary" data-toggle="tooltip" title="非公開にする" onClick="private_alert(event);return false">
                                                    非公開
                                                </button>
                                            </form>
                                            @endif
                                            @endif -->
                                    </td>
                                    <td class="text-right pt-6 pb-4">
                                        <table class="float-right inline">
                                            <tr>
                                                <td>
                                                    @can('outHouseGeneral-onlySelf', [$store->staff_id])
                                                    <button type="button" class="btn btn-sm btn-secondary delete-confirm" data-id="{{ $store->id }}" data-title="{{ $store->name }}" data-toggle="tooltip" title="Delete">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                    @endcan
                                                </td>
                                                <td style="padding-bottom:8px;padding-left:8px">
                                                    <a style="padding-right: 3px !important;padding-left: 5px !important;" href="{{ route('admin.store.edit', ['id' => $store->id]) }}" class="btn btn-sm btn-secondary" data-toggle="tooltip" title="Edit">
                                                        &nbsp;<i class="fa fa-pencil"></i>&nbsp;
                                                    </a>
                                                    {{-- <span data-toggle="modal" data-target="#deleteModal"--}}
                                                    {{-- data-title="{{ $store->name }}" data-url="{{ route('admin.store.delete', ['id' => $store->id]) }}">--}}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                        @endcan
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="block-content block-content-full block-content-sm bg-body-light font-size-md">
            {{ $stores->appends(\Request::except('page'))->render() }}
        </div>
    </div>
    <!-- END Table -->

</div>
<!-- END Content -->

@endsection

@section('js')
<script src="{{ asset('vendor/admin/assets/js/store.js').'?'.time() }}"></script>
<script>
    function publish_alert(e) {
        if (!window.confirm('本当に公開しますか？')) {
            return false;
        }
        document.deleteform.submit();
    };

    function private_alert(e) {
        if (!window.confirm('本当に非公開にしますか？')) {
            return false;
        }
        document.deleteform.submit();
    };
</script>
@endsection

@include('admin.Layouts.footer')
