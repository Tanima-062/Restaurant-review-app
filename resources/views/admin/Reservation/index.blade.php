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

        table.reservationList {
            margin-left: 10px !important;
        }

        table.reservationList td,
        table.reservationList th {
            padding: 3px;
            text-align: left !important;
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
            padding: 20px 32px 1px;
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

@section('js')
<script>
    /* ID被りを排除 */
    function update_ids() {
        if ($('div.item-list-pc').css('display') == 'none') {
            $('div.item-list-pc').find('*').each(function(i, e) {
                if ($(this).attr('id') && $(this).attr('id') == 'reset') {
                    // console.log($(this).attr('id'));
                    $(this).attr('id', $(this).attr('id').split('_--_')[0] + '_--_' + i);
                }
            });
            $('div.item-list-sp').find('*').each(function(i, e) {
                if ($(this).attr('id') && $(this).attr('id').split('_--_')[0] == 'reset') {
                    // console.log($(this).attr('id'));
                    $(this).attr('id', $(this).attr('id').split('_--_')[0]);
                }
            });
        } else {
            $('div.item-list-pc').find('*').each(function(i, e) {
                if ($(this).attr('id') && $(this).attr('id').split('_--_')[0] == 'reset') {
                    // console.log($(this).attr('id'));
                    $(this).attr('id', $(this).attr('id').split('_--_')[0]);
                }
            });
            $('div.item-list-sp').find('*').each(function(i, e) {
                if ($(this).attr('id') && $(this).attr('id') == 'reset') {
                    // console.log($(this).attr('id'));
                    $(this).attr('id', $(this).attr('id').split('_--_')[0] + '_--_' + i);
                }
            });
        }

        // for debug
        console.log('PC!!!!!!!!');
        $('div.item-list-pc').find('*').each(function(i, e) {
            if ($(this).attr('id')) {
                console.log($(this).attr('id'));
            }
        });
        console.log('SP!!!!!!!!');
        $('div.item-list-sp').find('*').each(function(i, e) {
            if ($(this).attr('id')) {
                console.log($(this).attr('id'));
            }
        });
    }
    $(window).resize(function() {
        update_ids();
    });
    $(document).ready(function() {
        update_ids();
    });
</script>
@endsection

@section('content')
<?php /*echo ('isMobile===' . $isMobile);*/ $isMobile = 0; ?>
<!-- Content -->
<div class="content" style="max-width: 1720px;">
    @include('admin.Layouts.flash_message')
    <!-- Default Table Style -->
    <h2 class="content-heading">予約一覧</h2>
    <div class="block item-list-pc">
        <div class="row">
            <div class="block-content block-content-full col-offset-2 d-flex justify-content-center">
                <form action="{{ route('admin.reservation') }}" method="get" class="form-inline justify-content-center flex-column position-relative">
                    <table class="table table-borderless item-list-pc">
                        <tr>
                            <th>予約番号</th>
                            <td><textarea class="form-control mr-sm-2" name="id" placeholder="予約番号">{{ old('id', \Request::query('id')) }}</textarea></td>
                        </tr>
                        <tr>
                            <th>ステータス</th>
                            <td><select class="form-control mr-sm-2" name="reservation_status">
                                    <option value="">予約ステータス</option>
                                    @foreach(config('code.reservationStatus') as $status)
                                    @if($status['key'] === 'CANCEL' && $isMobile)
                                    @continue
                                    @else
                                    <option value="{{$status['key']}}" @if(old('reservation_status', \Request::query('reservation_status'))==$status['key']) selected @endif>{{$status['value']}}</option>
                                    @endif
                                    @endforeach
                                </select>
                                @can('inHouseGeneral-higher')
                                <select class="form-control mr-sm-2" name="payment_status">
                                    <option value="">決済ステータス</option>
                                    @foreach(config('code.paymentStatus') as $status)
                                    <option value="{{$status['key']}}" @if(old('payment_status', \Request::query('payment_status'))==$status['key']) selected @endif>{{$status['value']}}</option>
                                    @endforeach
                                </select>
                                @endcan
                            </td>
                        </tr>
                        <tr>
                            <th>条件日時</th>
                            <td>
                                <!-- <div class="form-inline"> -->
                                    @if(!$isMobile)
                                    <div class="form-inline pr-5">
                                        <div>
                                            <input class="form-control mr-sm-2" id="created_at_from" name="created_at_from" placeholder="申込日時 from" autocomplete="off" value="{{old('created_at_from', \Request::query('created_at_from'))}}">
                                        </div>
                                        <div>
                                            <input class="form-control mr-sm-2" id="created_at_to" name="created_at_to" placeholder="申込日時 to" autocomplete="off" value="{{old('created_at_to', \Request::query('created_at_to'))}}">
                                        </div>
                                    </div>
                                    @endif
                                    <div class="form-inline">
                                        <div>
                                            <input class="form-control mr-sm-2" id="pick_up_datetime_from" name="pick_up_datetime_from" placeholder="来店日時 from" autocomplete="off" value="{{old('pick_up_datetime_from', \Request::query('pick_up_datetime_from'))}}">
                                        </div>
                                        <div>
                                            <input class="form-control mr-sm-2" id="pick_up_datetime_to" name="pick_up_datetime_to" placeholder="来店日時 to" autocomplete="off" value="{{old('pick_up_datetime_to', \Request::query('pick_up_datetime_to'))}}">
                                        </div>
                                    </div>
                                <!-- </div> -->
                            </td>
                            <!-- <th>条件日時</th>
                            <td>
                                @if(!$isMobile)
                                <input class="form-control mr-sm-2" id="created_at_from" name="created_at_from" placeholder="申込日時 from" autocomplete="off" value="{{old('created_at_from', \Request::query('created_at_from'))}}">
                                <input class="form-control mr-sm-2" id="created_at_to" name="created_at_to" placeholder="申込日時 to" autocomplete="off" value="{{old('created_at_to', \Request::query('created_at_to'))}}">
                                @endif
                                <input class="form-control mr-sm-2" id="pick_up_datetime_from" name="pick_up_datetime_from" placeholder="来店日時 from" autocomplete="off" value="{{old('pick_up_datetime_from', \Request::query('pick_up_datetime_from'))}}">
                                <input class="form-control mr-sm-2" id="pick_up_datetime_to" name="pick_up_datetime_to" placeholder="来店日時 to" autocomplete="off" value="{{old('pick_up_datetime_to', \Request::query('pick_up_datetime_to'))}}">
                            </td> -->
                        </tr>
                        <tr>
                            <th>お客様情報</th>
                            <td>
                                <input class="form-control mr-sm-2" name="last_name" placeholder="姓(カナ)" value="{{old('last_name', \Request::query('last_name'))}}">
                                <input class="form-control mr-sm-2" name="first_name" placeholder="名(カナ)" value="{{old('first_name', \Request::query('first_name'))}}">
                                @if(!$isMobile)
                                <input class="form-control mr-sm-2" name="email" placeholder="メールアドレス" value="{{old('email', \Request::query('email'))}}">
                                @endif
                                <input class="form-control mr-sm-2" name="tel" placeholder="電話番号" value="{{old('tel', \Request::query('tel'))}}">
                            </td>
                        </tr>
                        @can('inHouseGeneral-higher')
                        <tr>
                            <th>店舗</th>
                            <td>
                                <input class="form-control mr-sm-2" name="store_name" placeholder="店舗名" type="text" value="{{old('store_name', \Request::query('store_name'))}}">
                                <input class="form-control mr-sm-2" name="store_tel" placeholder="店舗電話番号" value="{{old('store_tel', \Request::query('store_tel'))}}">
                            </td>
                        </tr>
                        @endcan
                    </table>

                    <div class="mt-5">
                        <button type="submit" class="btn btn-alt-primary action" name="action" value="search">検索する</button>
                        <button type="button" class="btn btn-alt-primary action" name="reset" value="reset" id="reset">リセット</button>
                        <button type="submit" class="btn btn-alt-primary action position-absolute" style="right: calc(0.5rem + 10px);bottom: 0;" name="action" value="csv">CSV出力</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="block item-list-sp">
        <div class="row">
            <div class="block-content block-content-full col-offset-2 d-flex justify-content-center">
                <form action="{{ route('admin.reservation') }}" method="get" class="form-inline justify-content-center flex-column position-relative">

                    <table class="table table-borderless item-list-sp">
                        <tr class="">
                            <td colspan="2">
                                <label class="form-label" for="example-textarea-input">予約番号</label>
                                <textarea class="form-control mr-sm-2" id="example-textarea-input-sp" name="id" rows="4" placeholder="予約番号">{{ old('id', \Request::query('id')) }}</textarea>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <label for="single-select-example">予約ステータス</label>
                                <select class="form-control mr-sm-2" name="reservation_status">
                                    <option value="">予約ステータス</option>
                                    @foreach(config('code.reservationStatus') as $status)
                                    @if($status['key'] === 'CANCEL' && $isMobile)
                                    @continue
                                    @else
                                    <option value="{{$status['key']}}" @if(old('reservation_status', \Request::query('reservation_status'))==$status['key']) selected @endif>{{$status['value']}}</option>
                                    @endif
                                    @endforeach
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <label for="created_at_from">条件日時</label>
                                <!-- <div class="form-inline"> -->
                                    @if(!$isMobile)
                                    <div class="form-inline">
                                        <div style="margin-bottom: 8px;margin-right: 8px;">
                                            <input class="form-control mb-1 cond-date" id="created_at_from-sp" name="created_at_from" placeholder="申込日時 from" autocomplete="off" value="{{old('created_at_from', \Request::query('created_at_from'))}}" type="text">
                                        </div>
                                        <div style="margin-bottom: 8px;">
                                            <input class="form-control mb-1 cond-date" id="created_at_to-sp" name="created_at_to" placeholder="申込日時 to" autocomplete="off" value="{{old('created_at_to', \Request::query('created_at_to'))}}" type="text">
                                        </div>
                                    </div>
                                    @endif
                                    <div class="form-inline">
                                        <div style="margin-bottom: 8px;margin-right: 8px;">
                                            <input class="form-control mb-1 cond-date" id="pick_up_datetime_from-sp" name="pick_up_datetime_from" placeholder="来店日時 from" autocomplete="off" value="{{old('pick_up_datetime_from', \Request::query('pick_up_datetime_from'))}}" type="text">
                                        </div>
                                        <div style="margin-bottom: 8px;">
                                            <input class="form-control mb-1 cond-date" id="pick_up_datetime_to-sp" name="pick_up_datetime_to" placeholder="来店日時 to" autocomplete="off" value="{{old('pick_up_datetime_to', \Request::query('pick_up_datetime_to'))}}" type="text">
                                        </div>
                                    </div>
                                <!-- </div> -->
                            </td>
                        </tr>
                        <!-- <tr>
                        @if(!$isMobile)
                            <td style="padding-right:4px;width:25%">
                                <label for="created_at_from">条件日時</label>
                                <input style="width:150px" class="form-control mr-sm-2 mb-1" id="created_at_from-sp" name="created_at_from" placeholder="申込日時 from" autocomplete="off" value="{{old('created_at_from', \Request::query('created_at_from'))}}" type="text">
                            </td>
                            <td style="padding-left:4px;width:25%">
                                <label for="created_at_from">　　　　</label>
                                <input style="width:150px" class="form-control mr-sm-2 mb-1" id="created_at_to-sp" name="created_at_to" placeholder="申込日時 to" autocomplete="off" value="{{old('created_at_to', \Request::query('created_at_to'))}}" type="text">
                            </td>
                        @endif
                            <td style="padding-right:4px;width:25%">
                            <label for="created_at_from">　　　　</label>
                                <input style="width:150px" class="form-control mr-sm-2 mb-1" id="pick_up_datetime_from-sp" name="pick_up_datetime_from" placeholder="来店日時 from" autocomplete="off" value="{{old('pick_up_datetime_from', \Request::query('pick_up_datetime_from'))}}" type="text">
                            </td>
                            <td style="padding-left:4px;width:25%">
                            <label for="created_at_from">　　　　</label>
                                <input style="width:150px" class="form-control mr-sm-2 mb-1" id="pick_up_datetime_to-sp" name="pick_up_datetime_to" placeholder="来店日時 to" autocomplete="off" value="{{old('pick_up_datetime_to', \Request::query('pick_up_datetime_to'))}}" type="text">
                            </td>
                        </tr> -->
                        <tr>
                            <td colspan="2">
                                <label for="created_at_from">お客様情報</label>
                                <input class="form-control mr-sm-2" name="last_name" placeholder="姓(カナ)" value="{{old('last_name', \Request::query('last_name'))}}">
                                <input class="form-control mr-sm-2" name="first_name" placeholder="名(カナ)" value="{{old('first_name', \Request::query('first_name'))}}">
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" class="pt-0">
                                @if(!$isMobile)
                                <input class="form-control mr-sm-2" name="email" placeholder="メールアドレス" value="{{old('email', \Request::query('email'))}}">
                                @endif
                                <input class="form-control mr-sm-2" name="tel" placeholder="電話番号" value="{{old('tel', \Request::query('tel'))}}">
                            </td>
                        </tr>
                        @can('inHouseGeneral-higher')
                        <tr>
                            <td colspan="2">
                                <label for="created_at_from">店舗</label>
                                <input class="form-control mr-sm-2" name="store_name" placeholder="店舗名" type="text" value="{{old('store_name', \Request::query('store_name'))}}">
                                <input class="form-control mr-sm-2" name="store_tel" placeholder="店舗電話番号" value="{{old('store_tel', \Request::query('store_tel'))}}">
                            </td>
                        </tr>
                        @endcan
                    </table>

                    <div class="mt-5">
                        <button type="submit" class="btn btn-alt-primary action" name="action" value="search">検索する</button>
                        <button type="button" class="btn btn-alt-primary action" name="reset" value="reset" id="reset">リセット</button>
                        <button type="submit" class="btn btn-alt-primary action position-absolute" style="right: calc(0.5rem + 10px);bottom: 0;" name="action" value="csv">CSV出力</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="block">
        <div class="block-header block-header-default">
            <h3 class="block-title" style="font-size:1rem;">総件数 : {{ $reservations->total() }}件</h3>
            <!-- <div class="block-options">
                <div class="block-options-item">
                    <select class="form-control mr-sm-2" name="reservation_status">
                        <option value="">並び替え</option>
                        <option value="">予約番号</option>
                        <option value="">申込者</option>
                        <option value="">電話番号</option>
                        <option value="">来店日時</option>
                    </select>
                </div>
            </div> -->
            <!--
            <div class="block-options">
                <div class="block-options-item">
                    <a href="http://localhost/admin/staff/add" class="btn btn-sm btn-secondary js-tooltip-enabled" data-toggle="tooltip" title="" data-original-title="Add">
                        <i class="fa fa-plus"></i>
                    </a>
                </div>
            </div> -->
        </div>
        <div class="list-content">
            <table class="table table-borderless table-vcenter reservationList item-list-pc">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 50px;">@sortablelink('id','予約番号')</th>
                        <th>@sortablelink('full_name','申込者')</th>
                        <th>@sortablelink('tel','電話番号')</th>
                        <th>@sortablelink('pick_up_datetime','来店日時')</th>

                        @if(!$isMobile)
                        <th>@sortablelink('email','メールアドレス')</th>
                        <th>@sortablelink('reservation_status','予約ステータス')</th>
                        @can('inHouseGeneral-higher')
                        <th>@sortablelink('payment_status','入金ステータス')</th>
                        <th>店舗</th>
                        <th>店舗電話番号</th>
                        @endcan
                        <th>@sortablelink('total','金額')</th>
                        <th>@sortablelink('persons','人数')</th>
                        <th>@sortablelink('created_at','申込日時')</th>
                        <th class="text-center" style="width: 100px;"></th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reservations as $reservation)
                    <tr @if($reservation->payment_status === 'UNPAID' || is_null($reservation->payment_status)) class="reservationListRow" @endif>
                        <td>
                            <a href="{{ route('admin.reservation.edit', ['id' => $reservation->id]) }}" data-toggle="tooltip" title="Edit">
                                {{ $reservation->app_cd.$reservation->id }}
                            </a>
                        </td>
                        <td>{{ $reservation->full_name }}</td>
                        <td>{{ $reservation->tel }}</td>
                        <td>{{ ($reservation->pick_up_datetime) ? (new \Carbon\Carbon($reservation->pick_up_datetime))->format('Y-m-d H:i') : '' }}</td>

                        @if(!$isMobile)
                        <td>{{ $reservation->email }}</td>
                        <td>{{ config('code.reservationStatus')[strtolower($reservation->reservation_status)]['value'] }}</td>
                        @can('inHouseGeneral-higher')
                        <td>{{ config('code.paymentStatus')[strtolower($reservation->payment_status)]['value'] }}</td>
                        <td>{{ $reservation->reservationStore->name }}</td>
                        <td>{{ str_replace('-', '', $reservation->reservationStore->tel) }}</td>
                        @endcan
                        <td>{{ number_format($reservation->total) }}円</td>
                        <td>{{ $reservation->persons }}</td>
                        <td>{{ $reservation->created_at }}</td>
                        <td class="text-center">
                            <div class="btn-group">
                                <a href="{{ route('admin.reservation.edit', ['id' => $reservation->id]) }}" class="btn btn-sm btn-secondary" data-toggle="tooltip" title="Edit">
                                    <i class="fa fa-pencil"></i>
                                </a>
                            </div>
                        </td>
                        @endif
                    </tr>
                    @endforeach
                </tbody>
            </table>











            <table class="table table-borderless table-vcenter reservationList item-list-sp">
                <tbody>
                    @foreach ($reservations as $reservation)
                    <tr @if($reservation->payment_status === 'UNPAID' || is_null($reservation->payment_status)) class="reservationListRow" @endif>
                        <td class="pt-4">
                            <table class="list-item">
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.reservation.edit', ['id' => $reservation->id]) }}" data-toggle="tooltip" title="Edit">
                                            <span style="font-weight:bold;">{{ $reservation->app_cd.$reservation->id }}</span>
                                        </a>
                                    </td>
                                    <td style="text-align:right !important;">
                                        <span>
                                            <small>
                                                {{ $reservation->created_at }}

                                            </small>
                                        </span>
                                    </td>

                                </tr>
                                <tr>
                                    <td>
                                        <span style="font-weight:bold;">{{ $reservation->full_name }}</span>
                                    </td>

                                    @if(!$isMobile)
                                    @can('inHouseGeneral-higher')
                                    <td rowspan="12" class="pencil-btn">
                                        @endcan
                                        @cannot('inHouseGeneral-higher')
                                    <td rowspan="9" class="pencil-btn">
                                        @endcan
                                        @else
                                    <td rowspan="3" class="pencil-btn">
                                        @endif
                                        <span class="text-center">
                                            <div class="btn-group">
                                                <a href="{{ route('admin.reservation.edit', ['id' => $reservation->id]) }}" class="btn btn-sm btn-secondary" data-toggle="tooltip" title="Edit">
                                                    &nbsp;<i class="fa fa-pencil"></i>&nbsp;
                                                </a>
                                            </div>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <span>{{ $reservation->tel }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <span>{{ ($reservation->pick_up_datetime) ? (new \Carbon\Carbon($reservation->pick_up_datetime))->format('Y-m-d H:i') : '' }}</span>
                                    </td>
                                </tr>
                                @if(!$isMobile)
                                <tr>
                                    <td colspan="2">
                                        <span>{{ $reservation->email }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <span>{{ config('code.reservationStatus')[strtolower($reservation->reservation_status)]['value'] }}</span>
                                    </td>
                                </tr>
                                @can('inHouseGeneral-higher')
                                <tr>
                                    <td colspan="2">
                                        <span>{{ config('code.paymentStatus')[strtolower($reservation->payment_status)]['value'] }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <span>{{ $reservation->reservationStore->name }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <span>{{ str_replace('-', '', $reservation->reservationStore->tel) }}</span>
                                    </td>
                                </tr>
                                @endcan
                                <tr>
                                    <td colspan="2">
                                        <span>{{ number_format($reservation->total) }}円</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <span>{{ $reservation->persons }}名</span>
                                    </td>
                                </tr>
                                <!-- <tr>
                                    <td colspan="2">
                                        <span>{{ $reservation->created_at }}</span>
                                    </td>
                                </tr> -->
                                <!-- <tr>
                                    <td>
                                        <span class="text-center">
                                            <div class="btn-group">
                                                <a href="{{ route('admin.reservation.edit', ['id' => $reservation->id]) }}" class="btn btn-sm btn-secondary" data-toggle="tooltip" title="Edit">
                                                    &nbsp;<i class="fa fa-pencil"></i>&nbsp;
                                                </a>
                                            </div>
                                        </span>
                                    </td>
                                </tr> -->
                                @endif
                            </table>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="block-content block-content-full block-content-sm bg-body-light font-size-md reservationListPager">
            {{ $reservations->appends(\Request::except('page'))->render() }}
        </div>
    </div>
    <!-- END Table -->

</div>
<!-- END Content -->

@include('admin.Layouts.js_files')

<script src="{{ asset('vendor/admin/assets/js/reservation.js').'?'.time() }}"></script>
@endsection

@include('admin.Layouts.footer')
