@extends('admin.Layouts.base')
@include('admin.Layouts.head')

@include('admin.Layouts.side_overlay')
@include('admin.Layouts.sidebar')
@include('admin.Layouts.page_header')

@section('css')
<style>
    /* pc 既存の表示への影響をしないよう */
    @media screen and (min-width: 961px) {

        table.item-list-pc,
        div.item-list-pc {
            display: block;
        }

        table.item-list-sp,
        div.item-list-sp {
            display: none;
        }

        div.content-item-list-pc {
            max-width: 1720px;
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

        table {
            table-layout: fixed;
            width: 100%;
            margin-bottom: 0 !important;
        }

        table.item-list-pc,
        div.item-list-pc {
            display: none;
        }

        table.item-list-sp,
        div.item-list-sp {
            display: block;
        }

        table.item-list-sp a {
            /* padding: 8px 0 !important; */
            padding-left: 4px !important;
            padding-right: 4px !important;
        }

        .block-content {
            padding: 20px 8px 1px;
        }
        .block-header {
            padding: 14px 16px !important;
        }
        .list-content {
            padding-top: 0;
            padding-left: 16px;
            padding-right: 16px;
        }

        .table.item-list-sp {
            table-layout: fixed;
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
            width: 100%;
            text-align: left !important;
        }

        .table.item-list-sp label {
            text-align: left !important;
            display: -ms-flexbox;
            display: flex;
            -ms-flex-align: left;
            align-items: center;
            -ms-flex-pack: start;
            justify-content: left;
            margin-bottom: 8px;
        }

        .table.item-list-sp.reservationList tr {
            border-bottom: 1px solid #e4e7ed;
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
            background-color: transparent !important;
            margin-bottom: 8px !important;
            padding-bottom: 2rem !important;
        }

        table.list-item tr {
            padding-top: 0 !important;
            padding-bottom: 0;
            border-bottom: none !important;
        }

        table.list-item td {
            width: auto !important;
            /* width: 100% !important; */
            text-align: left !important;
            padding-top: 0;
            padding-bottom: 0;
        }

        table.list-item td.pencil-btn {
            text-align: right !important;
        }

        h3.block-title {
            font-size: 14px;
            font-weight: 400;
        }

        #published {
            margin-bottom: 8px;
        }

        /*
        div.content-item-list-pc {
            max-width: 1720px;
        }
        */
    }

    @media screen and (max-width: 425px) {
        .cond-date {
            /* width: 140px; */
            font-size: 1em !important;
        }
    }

    @media screen and (max-width: 375px) {
        .cond-date {
            width: 140px !important;
            /* font-size: .8em !important; */
            letter-spacing: -0.04em;
        }
    }

    @media screen and (max-width: 320px) {
        .cond-date {
            width: 136px !important;
            /* font-size: .9em !important; */
            letter-spacing: -0.05em;
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
<div class="content" class="content-item-list-pc">
    @include('admin.Layouts.flash_message')
    <!-- Default Table Style -->
    <h2 class="content-heading">{{ $store->name }} キャンセル料一覧</h2>

    <!-- Table for PC -->
    <div class="block item-list-pc">
        <div class="block-header block-header-default">
            <h3 class="block-title">総件数 : {{ $cancelFees->count() }}件</h3>
            <div class="block-options">
                <div class="block-options-item">
                    <a href="{{ route('admin.store.cancelFee.addForm', ['id' => $store->id]) }}" class="btn btn-sm btn-secondary" data-toggle="tooltip" title="Add">
                        <i class="fa fa-plus"></i>
                    </a>
                </div>
            </div>
        </div>
        <div class="block-content">
            <table class="table table-borderless table-vcenter">
                <thead>
                    <tr>
                        <th>@sortablelink('app_cd','利用サービス')</th>
                        <th>@sortablelink('apply_term_from','適用開始日')</th>
                        <th>@sortablelink('apply_term_to','適用終了日')</th>
                        <th>@sortablelink('visit','来店前/後')</th>
                        <th>@sortablelink('cancel_limit','期限(日/時間)')</th>
                        <th>@sortablelink('cancel_limit_unit','期限単位')</th>
                        <th>@sortablelink('cancel_fee','キャンセル料(%/円)')</th>
                        <th>@sortablelink('cancel_fee_unit','計上単位')</th>
                        <th>@sortablelink('fraction_unit','端数処理(単位)')</th>
                        <th>@sortablelink('fraction_round','端数処理(round)')</th>
                        <th>@sortablelink('cancel_fee_max','最高額')</th>
                        <th>@sortablelink('cancel_fee_min','最低額')</th>
                        <th class="text-center" style="width: 100px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($cancelFees as $cancelFee)
                    <tr @if(!$cancelFee->published)class="table-dark" @endif>
                        <td>
                            {{ config('const.storeCancelFee.app_cd')[$cancelFee->app_cd] }}
                        </td>
                        <td>{{ $cancelFee->apply_term_from->format('Y/m/d') }}</td>
                        <td>{{ $cancelFee->apply_term_to->format('Y/m/d') }}</td>
                        <td>{{ config('const.storeCancelFee.visit')[$cancelFee->visit] }}</td>
                        <td>{{ $cancelFee->cancel_limit }}</td>
                        <td>{{ is_null($cancelFee->cancel_limit_unit) ? '' : config('const.storeCancelFee.cancel_limit_unit')[$cancelFee->cancel_limit_unit] }}</td>
                        <td>{{ $cancelFee->cancel_fee }}@if($cancelFee->cancel_fee_unit === 'FIXED_RATE')<span>%</span>@else<span>円</span>@endif</td>
                        <td>{{ config('const.storeCancelFee.cancel_fee_unit')[$cancelFee->cancel_fee_unit] }}</td>
                        <td>{{ $cancelFee->fraction_unit }}</td>
                        <td>{{ config('const.storeCancelFee.fraction_round')[$cancelFee->fraction_round] }}</td>
                        <td>{{ $cancelFee->cancel_fee_max }}</td>
                        <td>{{ $cancelFee->cancel_fee_min }}</td>
                        <td class="text-center">
                            <div class="btn-group">
                                <a href="{{ route('admin.store.cancelFee.editForm', ['id' => $store->id, 'cancel_fee_id' => $cancelFee->id]) }}" class="btn btn-sm btn-secondary" data-toggle="tooltip" title="Edit">
                                    <i class="fa fa-pencil"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-secondary delete-confirm" data-id={{ $store->id }} data-cancel_fee_id={{ $cancelFee->id }} data-toggle="tooltip" title="Delete">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <!-- END Table -->
    <!-- Table for Mobile -->
    <div class="block item-list-sp">
        <div class="block-header block-header-default">
            <h3 class="block-title">総件数 : {{ $cancelFees->count() }}件</h3>
            <div class="block-options">
                <div class="block-options-item">
                    <a href="{{ route('admin.store.cancelFee.addForm', ['id' => $store->id]) }}" class="btn btn-sm btn-secondary" data-toggle="tooltip" title="Add">
                        <i class="fa fa-plus"></i>
                    </a>
                </div>
            </div>
        </div>
        <div class="block-content">
            <table class="table table-borderless table-vcenter">
                <tbody>
                    @foreach ($cancelFees as $cancelFee)
                    <tr  style="border-bottom:1px solid #ccc" @if(!$cancelFee->published)class="table-dark" @endif>
                        <td class="pt-0">
                            <table class="list-item">
                                <tr>
                                    <td colspan="2">
                                        {{ config('const.storeCancelFee.app_cd')[$cancelFee->app_cd] }}
                                    </td>
                                    <td class="text-right" rowspan="7">
                                        <div class="text-right">
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-secondary delete-confirm float-right" data-id={{ $store->id }} data-cancel_fee_id={{ $cancelFee->id }} data-toggle="tooltip" title="Delete">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                                &nbsp;
                                                &nbsp;
                                                <a href="{{ route('admin.store.cancelFee.editForm', ['id' => $store->id, 'cancel_fee_id' => $cancelFee->id]) }}" class="btn btn-sm btn-secondary float-right pr-6 pl-6" stylee="" data-toggle="tooltip" title="Edit">
                                                    <i class="fa fa-pencil"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="3">
                                        {{ $cancelFee->apply_term_from->format('Y/m/d') }}〜
                                        {{ $cancelFee->apply_term_to->format('Y/m/d') }}
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="3">
                                        {{ config('const.storeCancelFee.visit')[$cancelFee->visit] }}
                                        {{ $cancelFee->cancel_limit }}
                                        {{ is_null($cancelFee->cancel_limit_unit) ? '' : config('const.storeCancelFee.cancel_limit_unit')[$cancelFee->cancel_limit_unit] }}
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="3">
                                        {{ config('const.storeCancelFee.cancel_fee_unit')[$cancelFee->cancel_fee_unit] }}
                                        {{ $cancelFee->cancel_fee }}@if($cancelFee->cancel_fee_unit === 'FIXED_RATE')<span>%</span>@else<span>円</span>@endif
                                        （{{ $cancelFee->fraction_unit }}の位を{{ config('const.storeCancelFee.fraction_round')[$cancelFee->fraction_round] }}）
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="3">
                                        最高額：{{ $cancelFee->cancel_fee_max }}
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="3">
                                        最低額：{{ $cancelFee->cancel_fee_min }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <!-- END Table -->
    <button type="button" class="btn btn-secondary" onclick="location.href='{{ session('storeCancelFeeRedirectTo', route('admin.store')) }}'">戻る</button>
</div>
@include('admin.Layouts.js_files')
<script src="{{ asset('vendor/admin/assets/js/cancelFee.js').'?'.time() }}"></script>
@endsection

@include('admin.Layouts.footer')
