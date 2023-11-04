@extends('admin.Layouts.base')
@include('admin.Layouts.head')

@include('admin.Layouts.side_overlay')
@include('admin.Layouts.sidebar')
@include('admin.Layouts.page_header')

@section('css')
<style>
    @media screen and (min-width: 961px) {
        table.item-list-pc,
        div.item-list-pc {
            display: block;
        }
        div.item-list-pc-main {
            display: flex;
        }

        table.item-list-sp,
        div.item-list-sp {
            display: none;
        }

        .board {
            width: 185px
        }

        .narrow {
            width: 140px
        }

        .wide {
            width: 210px
        }

        .button-left {
            float: left;
            margin-bottom: 10px;
        }

        .button-right {
            float: right;
            margin-bottom: 10px;
        }

        .flex-row {
            display: flex;
            flex-wrap: wrap;
        }

        .mid-left {
            margin-top: 5px;
            margin-left: 5px
        }

        .mid-right {
            margin-top: 5px;
            margin-right: 5px;
        }

        .pager ul {
            position: relative;
            left: 50%;
            float: left;
        }

        .pager ul li {
            position: relative;
            left: -50%;
            float: left;
        }

        .pager ul li span,
        .pager ul li a {
            display: block;
            font-size: 16px;
            color: inherit;
            padding: 0.1em 0.3em;
            border-radius: 2px;
        }

        .pager ul li a:hover {
            background-color: #6db3ed;
        }

        .pager ul li a.disabled:hover {
            background-color: inherit;
        }

        a.page-active {
            color: #ffffff !important;
            background-color: #0066cc;
            pointer-events: none;
        }

        .right-line {
            border-right-color: rgb(228, 231, 237);
            border-right-style: solid;
            border-right-width: 1px;
        }

        .bottom-line {
            border-bottom-color: rgb(228, 231, 237);
            border-bottom-style: solid;
            border-bottom-width: 1px;
        }

        .no-top-line {
            border-top: none !important;
        }
    }

    /* sp 表示設定 */
    @media screen and (max-width: 960px) {

        table.item-list-pc,
        div.item-list-pc {
            display: none;
        }
        div.item-list-pc-main {
            display: none;
        }
        table.item-list-sp,
        div.item-list-sp {
            display: block;
        }

        .block-header {
            padding: 14px 14px !important;
        }

        .block-content {
            padding: 14px 14px 14px !important;
        }

        .table td,
        .table th {
            border-top: none !important;
            padding-top: 10px;
            padding-right: 10px;
            padding-bottom: 10px;
            padding-left: 0px !important;
        }

        .form-material {
            padding-top: 26px !important;
        }
        .btn {
            transition: none !important;
        }
    }
</style>
@endsection
@section('js')
<script>
    $(document).ready(function() {
        /* ID被りを排除 */
        function update_ids() {
            if ($('div.item-list-pc').css('display') == 'none') {
                $('div.item-list-pc').find('*').each(function(i, e) {
                    if ($(this).attr('id') && $(this).attr('id')) {
                        // console.log($(this).attr('id'));
                        $(this).attr('id', $(this).attr('id').split('_--_')[0] + '_--_' + i);
                    }
                });
                $('div.item-list-pc-main').find('*').each(function(i, e) {
                    if ($(this).attr('id') && $(this).attr('id')) {
                        // console.log($(this).attr('id'));
                        $(this).attr('id', $(this).attr('id').split('_--_')[0] + '_--_' + i);
                    }
                });
                $('div.item-list-sp').find('*').each(function(i, e) {
                    if ($(this).attr('id') && $(this).attr('id').split('_--_')[0]) {
                        // console.log($(this).attr('id'));
                        $(this).attr('id', $(this).attr('id').split('_--_')[0]);
                    }
                });
            } else {
                $('div.item-list-pc').find('*').each(function(i, e) {
                    if ($(this).attr('id') && $(this).attr('id').split('_--_')[0]) {
                        // console.log($(this).attr('id'));
                        $(this).attr('id', $(this).attr('id').split('_--_')[0]);
                    }
                });
                $('div.item-list-pc-main').find('*').each(function(i, e) {
                    if ($(this).attr('id') && $(this).attr('id').split('_--_')[0]) {
                        // console.log($(this).attr('id'));
                        $(this).attr('id', $(this).attr('id').split('_--_')[0]);
                    }
                });
                $('div.item-list-sp').find('*').each(function(i, e) {
                    if ($(this).attr('id') && $(this).attr('id')) {
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
            console.log('PCMAIN!!!!!!!!');
            $('div.item-list-pc-main').find('*').each(function(i, e) {
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
        if ($('div.item-list-sp').css('display') == 'block' &&
                navigator.userAgent.match(/(iPhone|iPad|iPod|Android)/i)) {
            console.log('iPhone|iPad|iPod!!!' + $('#sendReservationMail').attr('id'));
            $('button#sendReservationMail').css('border-color', '#eeeeee');
            $('button#sendReservationMail').css('background-color', '#eeeeee');
        } else {
            $('button#sendReservationMail').css('border-color', '#EFEFEF');
            $('button#sendReservationMail').css('background-color', '#EFEFEF');
        }
    });
</script>
@endsection

@section('content')

<!-- Content -->
<div class="content">
    @include('admin.Layouts.flash_message')

    <!-- Default Table Style -->
    <h2 class="content-heading"><span class="paymentStatus">予約詳細</span>
        @if($isMobile && $reservation->payment_status === 'UNPAID')
        未払い
        @elseif($reservation->payment_status === 'AUTH')
        支払済(与信)
        @elseif($reservation->payment_status === 'CANCEL')
        キャンセル
        @elseif($reservation->payment_status === 'PAYED')
        支払済(計上)
        @elseif($reservation->payment_status === 'WAIT_REFUND')
        返金待ち
        @elseif($reservation->payment_status === 'REFUNDED')
        返金済
        @endif

    </h2>

    <input type="hidden" id="reservation_id" name="reservation_id" value="{{$reservation->id}}">
    <input type="hidden" id="csrf_token" name="csrf_token" value="{{csrf_token()}}">

    @can('inHouseGeneral-higher')
    <div class="block item-list-pc">
        <div class="block-header block-header-default">
            <h3 class="block-title">対応履歴</h3>
        </div>
        <div class="block-content" style="overflow:hidden">
            <form action="{{url('admin/reservation/edit').'/'.$reservation->id}}" method="post" onsubmit="event.returnvalue = false; return false;">
                <table class="table table-bordered table-vcenter">
                    <thead>
                        <tr class="table-secondary">
                            <th class="board">担当者</th>
                            <th class="board">更新日時</th>
                            <th class="board">対応種別</th>
                            <th>対応内容</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($messages as $message)
                        <tr>
                            <td>@if(isset($message->staff)) {{$message->staff->name}} @else システム管理者 @endif</td>
                            <td>{{$message->created_at}}</td>
                            <td>{{$messageType[$message->message_type]}}</td>
                            <td>{!!nl2br(e($message->message))!!}</td>
                        </tr>
                        @endforeach
                        <tr>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td>
                                <textarea class="form-control mr-sm-2" id="messageBoardMessage" name="messageBoardMessage" rows="2"></textarea>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="button-right">
                    <button type="submit" id="saveMessageBoard" class="btn btn-alt-primary">対応内容入力</button>
                </div>
            </form>
        </div>
    </div>
    <div class="block item-list-sp">
        <div class="block-header block-header-default">
            <h3 class="block-title">対応履歴</h3>
        </div>
        <div class="block-content" style="overflow:hidden">
            <form action="{{url('admin/reservation/edit').'/'.$reservation->id}}" method="post" onsubmit="event.returnvalue = false; return false;">
                <table class="table table-bordered table-vcenter">
                    <thead>
                        <tr class="table-secondary">
                            <th class="board">担当者</th>
                            <th class="board">更新日時</th>
                            <th class="board">対応種別</th>
                            <th>対応内容</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($messages as $message)
                        <tr>
                            <td>@if(isset($message->staff)) {{$message->staff->name}} @else システム管理者 @endif</td>
                            <td>{{$message->created_at}}</td>
                            <td>{{$messageType[$message->message_type]}}</td>
                            <td>{!!nl2br(e($message->message))!!}</td>
                        </tr>
                        @endforeach
                        <tr>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td>
                                <textarea class="form-control mr-sm-2" id="messageBoardMessage" name="messageBoardMessage" rows="2"></textarea>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="text-right">
                    <button type="submit" id="saveMessageBoard" class="btn btn-alt-primary">対応内容入力</button>
                </div>
            </form>
        </div>
    </div>
    @endcan

    <div class="row item-list-pc-main">
        <div class="col-md-5">
            <div class="block">
                <div class="block-header block-header-default">
                    <h3 class="block-title">予約情報</h3>
                </div>
                <div class="block-content" style="overflow:hidden">
                    <form action="{{url('admin/reservations/edit').'/'.$reservation->id}}" method="post" onsubmit="event.returnvalue = false; return false;">
                        <table class="table table-bordered table-vcenter">
                            <tr>
                                <th class="table-secondary narrow">予約番号</th>
                                <td style="font-weight: bold;">{{$reservation->reservation_no}}</td>
                            </tr>
                            <tr>
                                <th class="table-secondary narrow">予約ステータス</th>
                                <td>
                                    @if($reservation->reservation_status == config('code.reservationStatus.cancel.key'))
                                    {{config('code.reservationStatus.cancel.value')}}
                                    @elseif($reservation->app_cd == key(config('code.appCd.rs')))
                                    @foreach($reservationStatus as $code => $name)
                                    @if($reservation->reservation_status == $name['key'])
                                    {{$name['value']}}
                                    @endif
                                    @endforeach
                                    @else
                                    <div class="form-group">
                                        <select class="form-control validation-select" id="reservation_status" name="reservation_status" data-default="{{$reservation->reservation_status}}" data-changed="false" style="position: relative;top: 8px;width: 150px">
                                            @foreach($reservationStatus as $code => $name)
                                            <option value="{{$name['key']}}" @if($reservation->reservation_status == $name['key']) selected @endif>{{$name['value']}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th class="table-secondary">成約</th>
                                <td>{{$reservation->is_close_str}}
                                </td>
                            </tr>
                            @can('inHouseGeneral-higher')
                            <tr>
                                <th class="table-secondary narrow">入金ステータス</th>
                                <td><a href="@if($newPayment){{url('admin/newpayment/detail/'.$reservation->id)}}@else{{url('admin/payment/detail/index/'.$reservation->id)}}@endif" target="_blank" style="text-decoration:underline;">{{$paymentStatus[strtolower($reservation->payment_status)]['value']}}</a></td>
                            </tr>
                            @endcan
                            <tr>
                                <th class="table-secondary narrow">合計金額</th>
                                <td>{{number_format($reservation->total)}}円@if(!is_null($adminChangeInfo) && $reservation->total != $adminChangeInfo['total'])<span>　→　{{number_format($adminChangeInfo['total'])}}円(予定)</span>@endif</td>
                            </tr>
                            <tr>
                                <th class="table-secondary narrow">予約個数/人数</th>
                                @if ($reservation->app_cd === key(config('code.appCd.to')))
                                <td>{{$reservation->reservationMenus->count()}} 個 / {{$reservation->persons}} 人</td>
                                @else
                                <td>
                                    <div class="flex-row">
                                        <span style="margin-top:5px">{{$reservation->reservationMenus->count()}} 個 / </span>@if(!is_null($adminChangeInfo))<span style="margin-top:5px">&nbsp;{{$reservation->persons}}&nbsp;</span>@else<input class="form-control col-3" id="persons" name="persons" value={{$reservation->persons}}>@endif<span style="margin-top:5px">人</span>@if(!is_null($adminChangeInfo) && $reservation->persons != $adminChangeInfo['persons'])<span style="margin-top:5px">　→　{{$adminChangeInfo['persons']}} 人(予定)</span>@endif
                                    </div>
                                </td>
                                @endif
                            </tr>
                            <tr>
                                <th class="table-secondary narrow">申込日時</th>
                                <td>{{$reservation->created_at}}</td>
                            </tr>
                            <tr>
                                <th class="table-secondary narrow">来店日時</th>
                                <td>@if(!is_null($adminChangeInfo))
                                    <span>{{$reservation->pick_up_datetime}}</span><br>
                                    @php
                                    $oldPickUpDatetime = Carbon\Carbon::parse($reservation->pick_up_datetime);
                                    $newPickUpDatetime = Carbon\Carbon::parse($adminChangeInfo['pick_up_datetime']);
                                    @endphp
                                    @if(strtotime($oldPickUpDatetime->format('Y-m-d H:i:s')) != strtotime($newPickUpDatetime->format('Y-m-d H:i:s')))
                                    <span style="margin-left: 70px">↓</span><br>
                                    <span style="">{{$newPickUpDatetime->format('Y-m-d H:i:s')}}(予定)</span>
                                    @endif
                                    @else
                                    <input class="form-control mr-sm-2" id="pick_up_datetime" name="pick_up_datetime" placeholder="来店日時" autocomplete="off" value="{{$reservation->pick_up_datetime}}">
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th class="table-secondary narrow">要望</th>
                                <td>{{$reservation->request}}</td>
                            </tr>
                            @if($reservation->cancel_datetime)
                            <tr>
                                <th class="table-secondary narrow">キャンセル日時</th>
                                <td>{{$reservation->cancel_datetime}}</td>
                            </tr>
                            @endif
                        </table>
                        @if($reservation->app_cd == key(config('code.appCd.rs')))
                        <div class="button-right">
                            @if(!$reservation->cancel_datetime && $cancelLimit >= Carbon\Carbon::now())
                            <button type="submit" id="reservationCancelForUser" class="btn btn-alt-danger">お客様都合キャンセル</button>
                            <button type="submit" id="reservationCancelForAdmin" class="btn btn-alt-danger">お店都合キャンセル</button>
                            @endif
                            @if(!is_null($adminChangeInfo))
                            <button type="submit" id="clearAdminChangeInfo" class="btn btn-alt-primary">クリア</button>
                            @elseif(!$reservation->cancel_datetime && $changeLimit >= Carbon\Carbon::now())
                            <button type="submit" id="updateReservationInfo" class="btn btn-alt-primary">変更</button>
                            @endif
                        </div>
                        @else
                        @if($reservation->pick_up_datetime >= Carbon\Carbon::now())
                        <div class="button-right">
                            @if(!$reservation->cancel_datetime)
                            <button type="submit" id="reservationCancel" class="btn btn-alt-danger">キャンセル</button>
                            @endif
                            <button type="submit" id="updateReservationInfo" class="btn btn-alt-primary">変更</button>
                        </div>
                        @endif
                        @endif
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-7">
            <div class="block">
                <div class="block-header block-header-default">
                    <h3 class="block-title">申込者情報</h3>
                </div>
                <div class="block-content" style="overflow:hidden">
                    <form action="{{url('admin/reservations/edit').'/'.$reservation->id}}" method="post" onsubmit="event.returnvalue = false; return false;">
                        <table class="table table-bordered table-vcenter reserver">
                            <tr>
                                <th class="table-secondary narrow">名前</th>
                                <td>
                                    <div id="name_show" class="flex-row">
                                        @if($isMobile)
                                        <textarea class="form-control mr-sm-2 col-3 validation-name reserver reserverLastName" id="last_name" name="last_name" data-default="" data-changed="">{{$reservation->last_name}}</textarea>
                                        <textarea class="form-control mr-sm-2 col-3 validation-name reserver reserverFirstName" id="first_name" name="first_name" data-default="" data-changed="">{{$reservation->first_name}}</textarea>
                                        @else
                                        <input class="form-control mr-sm-2 col-3 validation-name reserver" id="last_name" name="last_name" value="{{$reservation->last_name}}" data-default="" data-changed="">
                                        <input class="form-control mr-sm-2 col-3 validation-name reserver" id="first_name" name="first_name" value="{{$reservation->first_name}}" data-default="" data-changed="">
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th class="table-secondary narrow">電話番号</th>
                                <td>
                                    <div id="tel_show" class="flex-row">
                                        <input class="form-control col-7 reserver" id="tel" name="tel" value="{{$reservation->tel}}" data-default="" data-changed="">
                                        <span class="col-4 reserver">(ハイフンなし)</span>
                                    </div>
                                    <span id="tel_text"></span>
                                </td>
                            </tr>
                            <tr>
                                <th class="table-secondary narrow">メールアドレス</th>
                                <td>
                                    <div id="email_show">
                                        @if($isMobile)
                                        <textarea class="form-control mr-sm-2 col-7 reserver reserverEmail" id="email" name="email" data-default="" data-changed="">{{$reservation->email}}</textarea>
                                        @else
                                        <input class="form-control mr-sm-2 col-7 reserver" id="email" name="email" value="{{$reservation->email}}" data-default="" data-changed="">
                                        @endif
                                    </div>
                                    <span id="email_text"></span>
                                </td>
                            </tr>
                        </table>
                        <div class="button-right">
                            <button type="submit" id="sendReservationMail" class="btn" style="font-weight: normal">予約完了メール再送</button>
                            <button type="submit" id="updateDelegateInfo" class="btn btn-alt-primary">変更</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="row item-list-sp">
        <div class="col-12">
            <div class="block">
                <div class="block-header block-header-default">
                    <h3 class="block-title">予約情報</h3>
                </div>
                <div class="block-content" style="overflow:hidden">
                    <form action="{{url('admin/reservations/edit').'/'.$reservation->id}}" method="post" onsubmit="event.returnvalue = false; return false;">
                        <table class="table table-vcenter">
                            <tr>
                                <td colspan="2" style="font-weight: bold;" class="form-material">
                                    <label for="inputEmail4" class="form-label">予約番号</label>
                                    <input type="text" class="form-control" name="menu[0][price]" value="{{$reservation->reservation_no}}">
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" style="font-weight: bold;" class="form-material">
                                    <label for="inputEmail4" class="form-label">予約ステータス</label>
                                    @if($reservation->reservation_status == config('code.reservationStatus.cancel.key'))
                                    <input type="text" class="form-control" name="menu[0][price]" value="{{config('code.reservationStatus.cancel.value')}}">
                                    @elseif($reservation->app_cd == key(config('code.appCd.rs')))
                                    @foreach($reservationStatus as $code => $name)
                                    @if($reservation->reservation_status == $name['key'])
                                    <input type="text" class="form-control" name="menu[0][price]" value="{{$name['value']}}">
                                    @endif
                                    @endforeach
                                    @else
                                    <div class="form-group">
                                        <select class="form-control validation-select" id="reservation_status" name="reservation_status" data-default="{{$reservation->reservation_status}}" data-changed="false" style="position: relative;top: 8px;width: 150px">
                                            @foreach($reservationStatus as $code => $name)
                                            <option value="{{$name['key']}}" @if($reservation->reservation_status == $name['key']) selected @endif>{{$name['value']}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" style="font-weight: bold;" class="form-material">
                                    <label for="inputEmail4" class="form-label">成約</label>
                                    <input type="text" class="form-control" name="menu[0][price]" value="{{$reservation->is_close_str}}">
                                </td>
                            </tr>
                            @can('inHouseGeneral-higher')
                            <tr>
                                <td colspan="2" style="font-weight: bold;" class="form-material">
                                    <label for="inputEmail4" class="form-label">入金ステータス</label>
                                    <a href="@if($newPayment){{url('admin/newpayment/detail/'.$reservation->id)}}@else{{url('admin/payment/detail/index/'.$reservation->id)}}@endif" target="_blank" style="text-decoration:underline;">{{$paymentStatus[strtolower($reservation->payment_status)]['value']}}</a>
                                </td>
                            </tr>
                            @endcan
                            <tr>
                                <td colspan="2" style="font-weight: bold;" class="form-material">
                                    <label for="inputEmail4" class="form-label">合計金額</label>
                                    <input type="text" class="form-control" name="menu[0][price]" value="{{number_format($reservation->total)}}円">
                                    @if(!is_null($adminChangeInfo) && $reservation->total != $adminChangeInfo['total'])
                                    <span>　→　{{number_format($adminChangeInfo['total'])}}円(予定)</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" style="font-weight: bold;" class="form-material">
                                    <label for="inputEmail4" class="form-label">予約個数/人数</label>
                                    <style>
                                        .dtable {
                                            display: table;
                                            /* ブロックレベル要素をtableと同じように表示にする */
                                        }

                                        .dtable_c {
                                            display: table-cell;
                                            /* ブロックレベル要素をtd(th)と同じように表示にする */
                                            border: none !important;
                                        }
                                    </style>
                                    <div class="dtable">
                                    @if ($reservation->app_cd === key(config('code.appCd.to')))
                                    <div class="dtable_c" style="width: 25%;">
                                        <input type="text" class="form-control" name="menu[0][price]" value="{{$reservation->reservationMenus->count()}} 個 / {{$reservation->persons}} 人">
                                    </div>
                                    @else
                                    <div class="dtable_c" style="width: 25%;">
                                        <span style="margin-top:5px;display: table-cell;">{{$reservation->reservationMenus->count()}} 個 / </span>
                                    </div>
                                    <div class="dtable_c" style="width: 25%;">
                                    @if(!is_null($adminChangeInfo))
                                        <span style="margin-top:5px;display: table-cell;">&nbsp;{{$reservation->persons}}&nbsp;</span>
                                        @else
                                        <input class="form-control text-right" id="persons" name="persons" value={{$reservation->persons}}>
                                        @endif
                                    </div>
                                    <div class="dtable_c text-left">
                                        <span style="margin-top:5px;display: table-cell;">&nbsp;人</span>
                                    </div>
                                    @if(!is_null($adminChangeInfo) && $reservation->persons != $adminChangeInfo['persons'])
                                    <div class="dtable_c text-left">
                                        <span style="margin-top:5px;display: table-cell;">　→　{{$adminChangeInfo['persons']}} 人(予定)</span>
                                    </div>
                                    @endif
                                    @endif
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" style="font-weight: bold;" class="form-material">
                                    <label for="inputEmail4" class="form-label">申込日時</label>
                                    <input type="text" class="form-control" name="menu[0][price]" value="{{$reservation->created_at}}">
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" style="font-weight: bold;" class="form-material">
                                    <label for="inputEmail4" class="form-label">来店日時</label>
                                    @if(!is_null($adminChangeInfo))
                                    <span>{{$reservation->pick_up_datetime}}</span><br>
                                    @php
                                    $oldPickUpDatetime = Carbon\Carbon::parse($reservation->pick_up_datetime);
                                    $newPickUpDatetime = Carbon\Carbon::parse($adminChangeInfo['pick_up_datetime']);
                                    @endphp
                                    @if(strtotime($oldPickUpDatetime->format('Y-m-d H:i:s')) != strtotime($newPickUpDatetime->format('Y-m-d H:i:s')))
                                    <span style="margin-left: 70px">↓</span><br>
                                    <span style="">{{$newPickUpDatetime->format('Y-m-d H:i:s')}}(予定)</span>
                                    @endif
                                    @else
                                    <input class="form-control mr-sm-2" id="pick_up_datetime" name="pick_up_datetime" placeholder="来店日時" autocomplete="off" value="{{$reservation->pick_up_datetime}}">
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" style="font-weight: bold;" class="form-material">
                                    <label for="inputEmail4" class="form-label">要望</label>
                                    <span>{{$reservation->request}}</span><br>
                                </td>
                            </tr>
                            @if($reservation->cancel_datetime)
                            <tr>
                                <td colspan="2" style="font-weight: bold;" class="form-material">
                                    <label for="inputEmail4" class="form-label">キャンセル日時</label>
                                    <span>{{$reservation->cancel_datetime}}</span><br>
                                </td>
                            </tr>
                            @endif
                        </table>
                        @if($reservation->app_cd == key(config('code.appCd.rs')))
                        <div class="text-right">
                            @if(!$reservation->cancel_datetime && $cancelLimit >= Carbon\Carbon::now())
                            <button type="submit" id="reservationCancelForUser" class="btn btn-alt-danger mb-5">お客様都合キャンセル</button>
                            <button type="submit" id="reservationCancelForAdmin" class="btn btn-alt-danger mb-5">お店都合キャンセル</button>
                            @endif
                            @if(!is_null($adminChangeInfo))
                            <button type="submit" id="clearAdminChangeInfo" class="btn btn-alt-primary mb-5">クリア</button>
                            @elseif(!$reservation->cancel_datetime && $changeLimit >= Carbon\Carbon::now())
                            <button type="submit" id="updateReservationInfo" class="btn btn-alt-primary mb-5">変更</button>
                            @endif
                        </div>
                        @else
                        @if($reservation->pick_up_datetime >= Carbon\Carbon::now())
                        <div class="text-right">
                            @if(!$reservation->cancel_datetime)
                            <button type="submit" id="reservationCancel" class="btn btn-alt-danger mb-5">キャンセル</button>
                            @endif
                            <button type="submit" id="updateReservationInfo" class="btn btn-alt-primary mb-5">変更</button>
                        </div>
                        @endif
                        @endif
                    </form>
                </div>
            </div>
            <div class="block">
                <div class="block-header block-header-default">
                    <h3 class="block-title">申込者情報</h3>
                </div>
                <div class="block-content" style="overflow:hidden">
                    <form action="{{url('admin/reservations/edit').'/'.$reservation->id}}" method="post" onsubmit="event.returnvalue = false; return false;">
                        <table class="table table-vcenter reserver">
                            <tr>
                                <td colspan="2" style="font-weight: bold;" class="form-material">
                                    <label for="inputEmail4" class="form-label">名前2</label>
                                    <div id="name_show" class="flex-row">
                                        <input class="form-control mr-sm-2 col-3 mt-5 mb-5 validation-name reserver" id="last_name" name="last_name" value="{{$reservation->last_name}}" data-default="" data-changed="">
                                        <input class="form-control mr-sm-2 col-3 validation-name reserver" id="first_name" name="first_name" value="{{$reservation->first_name}}" data-default="" data-changed="">
                                        <!--
                                        @if($isMobile)
                                        <textarea class="form-control mr-sm-2 col-3 mt-5 mb-5 validation-name reserver reserverLastName" id="last_name" name="last_name" data-default="" data-changed="">{{$reservation->last_name}}</textarea>
                                        <textarea class="form-control mr-sm-2 col-3 validation-name reserver reserverFirstName" id="first_name" name="first_name" data-default="" data-changed="">{{$reservation->first_name}}</textarea>
                                        @else
                                        <input class="form-control mr-sm-2 col-3 mt-5 mb-5 validation-name reserver" id="last_name" name="last_name" value="{{$reservation->last_name}}" data-default="" data-changed="">
                                        <input class="form-control mr-sm-2 col-3 validation-name reserver" id="first_name" name="first_name" value="{{$reservation->first_name}}" data-default="" data-changed="">
                                        @endif -->
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" style="font-weight: bold;" class="form-material">
                                    <label for="inputEmail4" class="form-label">電話番号</label>
                                    <div id="tel_show" class="flex-row mt-5">
                                        <input class="form-control col-7 reserver" id="tel" name="tel" value="{{$reservation->tel}}" data-default="" data-changed="">
                                        <!-- <span class="col-4 reserver">(ハイフンなし)</span> -->
                                    </div>
                                    <span id="tel_text"></span>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" style="font-weight: bold;" class="form-material">
                                    <label for="inputEmail4" class="form-label">メールアドレス</label>
                                    <div id="email_show" class="mt-5">
                                        <input class="form-control mr-sm-2 col-7 reserver" id="email" name="email" value="{{$reservation->email}}" data-default="" data-changed="">
                                        <!--
                                        @if($isMobile)
                                        <textarea class="form-control mr-sm-2 col-7 reserver reserverEmail" id="email" name="email" data-default="" data-changed="">{{$reservation->email}}</textarea>
                                        @else
                                        <input class="form-control mr-sm-2 col-7 reserver" id="email" name="email" value="{{$reservation->email}}" data-default="" data-changed="">
                                        @endif -->
                                    </div>
                                    <span id="email_text"></span>
                                </td>
                            </tr>
                        </table>
                        <div class="text-right mt-5">
                            <button type="submit" id="sendReservationMail" class="btn" style="font-weight: normal">予約完了メール再送</button>
                            <button type="submit" id="updateDelegateInfo" class="btn btn-alt-primary">変更</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="block item-list-pc">
        <div class="block-header block-header-default">
            <h3 class="block-title">予約メニュー</h3>
        </div>
        <div class="block-content">
            <table class="table table-bordered table-vcenter">
                <thead>
                    <tr>
                        <th class="table-secondary col-ms-1">id</th>
                        <th class="table-secondary col-ms-2">品目</th>
                        <th class="table-secondary col-ms-6">名前 (単価)</th>
                        <th class="table-secondary col-ms-1">個数</th>
                        <th class="table-secondary col-ms-2">小計</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reservation->reservationMenus as $i => $reservationMenu)
                    <tr>
                        <td>{{$reservationMenu->menu_id}}</td>
                        <td>メニュー {{$i+1}}</td>
                        <td>{{$reservationMenu->name}} ({{number_format($reservationMenu->unit_price)}}円)</td>
                        <td>@if($reservation->app_cd === 'RS'){{ $reservation->persons }}@else{{($reservationMenu->count) ?: 0}}@endif</td>
                        <td>{{number_format($reservationMenu->price)}} 円</td>
                    </tr>
                    @foreach($reservationMenu->reservationOptions as $j => $reservationOption)
                    <tr>
                        <td>{{$reservationOption->id}}</td>
                        <td>&nbsp;&nbsp;{{(config('const.menuOptions.option_cd.'.$reservationOption->option_cd))}} {{sprintf("%d-%d", $i+1, $j+1)}}</td>
                        <td>{{$reservationOption->keyword}} - {{$reservationOption->contents}} ({{number_format($reservationOption->unit_price)}}円)</td>
                        <td>{{ $reservationOption->count }}</td>
                        <td>{{number_format($reservationOption->price)}} 円</td>
                    </tr>
                    @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="block item-list-sp">
        <div class="block-header block-header-default">
            <h3 class="block-title">予約メニュー</h3>
        </div>
        <div class="block-content">
            <table class="table table-vcenter">
                <tbody>
                    @foreach($reservation->reservationMenus as $i => $reservationMenu)
                    <tr>
                        <td style="font-weight: bold;" class="form-material">
                            <label for="inputEmail4" class="form-label">ID</label>
                            <input class="form-control mr-sm-2" value="{{$reservationMenu->menu_id}}">
                        </td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;" class="form-material">
                            <label for="inputEmail4" class="form-label">品目</label>
                            <input class="form-control mr-sm-2" value="メニュー {{$i+1}}">

                        </td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;" class="form-material">
                            <label for="inputEmail4" class="form-label">名前 (単価)</label>
                            <input class="form-control mr-sm-2" value="{{$reservationMenu->name}} ({{number_format($reservationMenu->unit_price)}}円)">


                        </td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;" class="form-material">
                            <label for="inputEmail4" class="form-label">個数</label>
                            <input class="form-control mr-sm-2" value="@if($reservation->app_cd === 'RS'){{ $reservation->persons }}@else{{($reservationMenu->count) ?: 0}}@endif">
                        </td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;" class="form-material">
                            <label for="inputEmail4" class="form-label">小計</label>
                            <input class="form-control mr-sm-2" value="{{number_format($reservationMenu->price)}} 円">
                        </td>
                    </tr>

                    @foreach($reservationMenu->reservationOptions as $j => $reservationOption)
                    <tr>
                        <td style="font-weight: bold;" class="form-material">
                            <label for="inputEmail4" class="form-label">id</label>
                            <input class="form-control mr-sm-2" value="{{$reservationOption->id}}">
                        </td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;" class="form-material">
                            <label for="inputEmail4" class="form-label">品目</label>
                            <input class="form-control mr-sm-2" value="  {{(config('const.menuOptions.option_cd.'.$reservationOption->option_cd))}} {{sprintf('%d-%d', $i+1, $j+1)}}">
                        </td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;" class="form-material">
                            <label for="inputEmail4" class="form-label">名前 (単価)</label>
                            <input class="form-control mr-sm-2" value="{{$reservationOption->keyword}} - {{$reservationOption->contents}} ({{number_format($reservationOption->unit_price)}}円)">
                        </td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;" class="form-material">
                            <label for="inputEmail4" class="form-label">個数</label>
                            <input class="form-control mr-sm-2" value="{{ $reservationOption->count }}">
                        </td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;" class="form-material">
                            <label for="inputEmail4" class="form-label">小計</label>
                            <input class="form-control mr-sm-2" value="{{number_format($reservationOption->price)}} 円">
                        </td>
                    </tr>
                    @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @can('inHouseGeneral-higher')
    <div class="row item-list-pc">
        <div class="col-md-5">
            <div class="block">
                <div class="block-header block-header-default">
                    <h3 class="block-title">ユーザー情報</h3>
                </div>
                <div class="block-content">
                    <table class="table table-bordered table-vcenter">
                        <tr>
                            <th class="table-secondary narrow">Skyticket申込番号</th>
                            <td>{{$skyReserveNo}}</td>
                        </tr>
                        <tr>
                            <th class="table-secondary">会員</th>
                            <td>@if($user) @if($user->member_status) 会員（ユーザーID：{{$user->user_id}}） @else 非会員 @endif @else 不明 @endif</td>
                        </tr>
                        <tr>
                            <th class="table-secondary narrow">流入タイプ</th>
                            <td>@if($isRepeater) リピーター @else 新規 @endif</td>
                        </tr>
                        <tr>
                            <th class="table-secondary narrow">デバイス</th>
                            <td>{{$reservation->device}}</td>
                        </tr>
                        <tr>
                            <th class="table-secondary narrow">広告コード</th>
                            <td>{{$reservation->ad_cd}}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-7">
            <div class="block">
                <div class="block-header block-header-default">
                    <h3 class="block-title">店舗情報</h3>
                </div>
                <div class="block-content">
                    <table class="table table-bordered table-vcenter">
                        <tr>
                            <th class="table-secondary narrow">精算会社</th>
                            <td>{{$reservation->reservationStore->store->settlementCompany->name}}</td>
                        </tr>
                        <tr>
                            <th class="table-secondary">店舗</th>
                            <td>{{$reservation->reservationStore->name}}</td>
                        </tr>
                        <tr>
                            <th class="table-secondary narrow">住所</th>
                            <td>{{$reservation->reservationStore->address}}</td>
                        </tr>
                        <tr>
                            <th class="table-secondary narrow">電話番号</th>
                            <td>{{$reservation->reservationStore->tel}}</td>
                        </tr>
                        <tr>
                            <th class="table-secondary narrow">営業時間</th>
                            <td>
                                @foreach($reservation->reservationStore->store->openingHours as $openingHours)
                                {{$openingHours->start_at}} - {{$openingHours->end_at}}<br>
                                @endforeach
                            </td>
                        </tr>
                        <tr>
                            <th class="table-secondary narrow">交通手段</th>
                            <td>{{$reservation->reservationStore->store->access}}</td>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="row item-list-sp">
        <div class="col-12">
            <div class="block">
                <div class="block-header block-header-default">
                    <h3 class="block-title">ユーザー情報</h3>
                </div>
                <div class="block-content">
                    <table class="table table-vcenter">
                        <tr>
                            <td colspan="2" style="font-weight: bold;" class="form-material">
                                <label for="inputEmail4" class="form-label">Skyticket申込番号</label>
                                <input type="text" class="form-control" name="menu[0][price]" value="{{$skyReserveNo}}">
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="font-weight: bold;" class="form-material">
                                <label for="inputEmail4" class="form-label">会員</label>
                                <input type="text" class="form-control" value="@if($user) @if($user->member_status) 会員（ユーザーID：{{$user->user_id}}） @else 非会員 @endif @else 不明 @endif">
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="font-weight: bold;" class="form-material">
                                <label for="inputEmail4" class="form-label">流入タイプ</label>
                                <input type="text" class="form-control" value="@if($isRepeater) リピーター @else 新規 @endif">
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="font-weight: bold;" class="form-material">
                                <label for="inputEmail4" class="form-label">デバイス</label>
                                <input type="text" class="form-control" value="{{$reservation->device}}">
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="font-weight: bold;" class="form-material">
                                <label for="inputEmail4" class="form-label">広告コード</label>
                                <input type="text" class="form-control" value="{{$reservation->ad_cd}}">
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="block">
                <div class="block-header block-header-default">
                    <h3 class="block-title">店舗情報</h3>
                </div>
                <div class="block-content">
                    <table class="table table-vcenter">
                        <tr>
                            <td colspan="2" style="font-weight: bold;" class="form-material">
                                <label for="inputEmail4" class="form-label">精算会社</label>
                                <input type="text" class="form-control" value="{{$reservation->reservationStore->store->settlementCompany->name}}">
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="font-weight: bold;" class="form-material">
                                <label for="inputEmail4" class="form-label">店舗</label>
                                <input type="text" class="form-control" value="{{$reservation->reservationStore->name}}">
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="font-weight: bold;" class="form-material">
                                <label for="inputEmail4" class="form-label">住所</label>
                                <input type="text" class="form-control" value="{{$reservation->reservationStore->address}}">
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="font-weight: bold;" class="form-material">
                                <label for="inputEmail4" class="form-label">電話番号</label>
                                <input type="text" class="form-control" value="{{$reservation->reservationStore->tel}}">
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="font-weight: bold;" class="form-material">
                                <label for="inputEmail4" class="form-label">営業時間</label>
                                @foreach($reservation->reservationStore->store->openingHours as $openingHours)
                                <input type="text" class="form-control" value="{{$openingHours->start_at}} - {{$openingHours->end_at}}">
                                <br>
                                @endforeach
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="font-weight: bold;" class="form-material">
                                <label for="inputEmail4" class="form-label">交通手段</label>
                                <input type="text" class="form-control" value="{{$reservation->reservationStore->store->access}}">
                            </td>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endcan
    <!-- END Content -->
    @include('admin.Layouts.js_files')

    <script src="{{ asset('vendor/admin/assets/js/reservationEdit.js').'?'.time() }}"></script>
    @endsection

    @include('admin.Layouts.footer')
