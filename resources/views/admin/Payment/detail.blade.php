@extends('admin.Layouts.base')
@include('admin.Layouts.head')

@include('admin.Layouts.side_overlay')
@include('admin.Layouts.sidebar')
@include('admin.Layouts.page_header')

@section('content')
<!-- Content -->
<div class="content" style="max-width: 1720px;">
@include('admin.Layouts.flash_message')
<!-- Default Table Style -->
    <h2 class="content-heading">入金詳細</h2>
    <input type="hidden" id="reservation_id" value="{{$reservation->id}}">
    <input type="hidden" id="pfPaid" value="{{$pfPaid}}">
    <input type="hidden" id="pfRefund" value="{{$pfRefund}}">
    <input type="hidden" id="pfRefunded" value="{{$pfRefunded}}">


    <!-- 予約情報 Table -->
    <div class="block">
        <div class="block-header block-header-default">
            <h3 class="block-title">予約情報</h3>
        </div>
        <div class="block-content">
            <table class="table table-borderless table-vcenter">
                <thead>
                <tr>
                    <th>予約番号</th>
                    <th>予約ステータス</th>
                    <th>入金ステータス</th>
                    @if($reservation->payment_status == config('code.paymentStatus.wait_payment.key'))
                    <th>入金期限</th>
                    @endif
                    <th>合計金額</th>
                    <th>事務手数料</th>
                    <th>申込日時</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td><a href="{{url('admin/reservation/edit/'.$reservation->id)}}">{{$reservation->app_cd}}{{$reservation->id}}</a></td>
                    <td>{{config('code.reservationStatus')[strtolower($reservation->reservation_status)]['value']}}</td>
                    <td>
                        @foreach ($paymentStatusSelect as $option)
                            @if ($defaultPaymentStatus['key'] == $option['key'])
                                {{ $option['value'] }}
                            @endif
                        @endforeach
                    </td>
                    @if($reservation->payment_status == config('code.paymentStatus.wait_payment.key'))
                    <td>{{ $reservation->payment_limit }}</td>
                    @endif
                    <td>{{number_format($reservation->total)}}</td>
                    <td>{{number_format($reservation->administrative_fee)}}</td>
                    <td>{{$reservation->created_at}}</td>
                </tr>
                </tbody>
            </table>
            {{-- <div class="form-group">
                <div class="text-right">
                    <button type="button" class="btn btn-alt-primary" id="saveReservation">予約情報保存</button>
                </div>
            </div> --}}
        </div>
    </div>
    <!-- END Table -->

    @if($isNewPayment === 1)
        <!-- 入金情報 -->
        <div class="row">
            <div class="col-md-2">
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">入金情報</h3>
                    </div>
                    <div class="form-group">
                        <div class="text-center">
                            <button type="button" class="btn btn-alt-primary" id="reviewPayment" onclick="reviewPayment('{{$reservation->id}}', this)">入金情報</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- END -->
    @else
        <!-- econクレジット入金情報 Table -->
        <div class="block">
            <div class="block-header block-header-default">
                <h3 class="block-title">econクレジット入金情報</h3>
            </div>
            <div class="block-content">
                <table class="table table-borderless table-vcenter">
                    <thead>
                    <tr>
                        <th>skyticket申込番号</th>
                        <th>econ注文番号</th>
                        <th>与信/計上</th>
                        <th>決済金額</th>
                        <th>決済処理結果</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($creditLogs as $creditLog)
                        <tr>
                            <td>{{$creditLog->cm_application_id}}</td>
                            <td>{{$creditLog->order_id}}</td>
                            <td>{{$creditLog->keijou_str}}</td>
                            <td>{{number_format($creditLog->price)}}</td>
                            <td>{{$creditLog->status_str}}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <!-- END Table -->
    @endif

    <div class="row">
        <!-- 入金明細 Table -->
        <div class="col-md-6">
            <div class="block">
                <div class="block-header block-header-default">
                    <h3 class="block-title">入金明細</h3>
                </div>
                <form action="{{url('admin/payment/detail/payment_detail_add').'/'.$reservation->id}}" method="post">
                    <div class="block-content">
                        <table class="table table-borderless table-vcenter">
                            <thead>
                            <tr>
                                <th>科目名(id)</th>
                                <th>単価</th>
                                <th>数量</th>
                                <th>合計</th>
                                <th>備考</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($paymentDetails as $paymentDetail)
                                <tr>
                                    <td>{{$paymentDetail->account_code_str}}({{$paymentDetail->target_id}})</td>
                                    <td>{{number_format($paymentDetail->price)}}</td>
                                    <td>{{$paymentDetail->count}}</td>
                                    <td>{{number_format($paymentDetail->sum_price)}}</td>
                                    <td>{{$paymentDetail->remarks}}</td>
                                </tr>
                            @endforeach
                            <tr>
                                <td>
                                    <select>
                                        <option value="">--</option>
                                        @foreach (config('const.payment.account_code') as $key => $option)
                                            <option value="{{$key}}">{{$option}}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="text" style="width: 100%" value="" maxlength="10"></td>
                                <td><input type="text" style="width: 100%" value="" maxlength="3"></td>
                                <td></td>
                                <td><input type="text" style="width: 100%" value="" maxlength="30"></td>
                            </tr>
                            <tr style="background-color: #FFCCCC;">
                                <td>合計金額</td>
                                <td></td>
                                <td></td>
                                <td>{{number_format($paymentDetails->sum('sum_price'))}}</td>
                                <td></td>
                            </tr>
                            </tbody>
                        </table>
                        <div class="form-group">
                            <div class="text-right">
                                <button type="button" class="btn btn-alt-primary" id="savePaymentDetail">入金明細保存</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <!-- END Table -->

        <!-- キャンセル料明細 Table -->
        <div class="col-md-6">
            <div class="block">
                <div class="block-header block-header-default">
                    <h3 class="block-title">キャンセル料明細</h3>
                </div>
                <form>
                    <div class="block-content">
                        <table class="table table-borderless table-vcenter">
                            <thead>
                            <tr>
                                <th>科目名(id)</th>
                                <th>単価</th>
                                <th>数量</th>
                                <th>合計</th>
                                <th>備考</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($cancelDetails as $cancelDetail)
                                <tr>
                                    <td>{{$cancelDetail->account_code_str}}({{$cancelDetail->target_id}})</td>
                                    <td>{{number_format($cancelDetail->price)}}</td>
                                    <td>{{$cancelDetail->count}}</td>
                                    <td>{{number_format($cancelDetail->sum_price)}}</td>
                                    <td>{{$cancelDetail->remarks}}</td>
                                </tr>
                            @endforeach
                            <tr>
                                <td>
                                    <select id="cancelDetailAccountCode">
                                        <option value="">--</option>
                                        @foreach (config('const.payment.account_code') as $key => $option)
                                            <option value="{{$key}}">{{$option}}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="text" style="width: 100%" value="" maxlength="10" id="cancelDetailPrice"></td>
                                <td><input type="text" style="width: 100%" value="" maxlength="3" id="cancelDetailCount"></td>
                                <td><span id="cancelPriceSum"></span></td>
                                <td><input type="text" style="width: 100%" value="" maxlength="30" id="cancelDetailRemarks"></td>
                            </tr>
                            <tr style="background-color: #FFCCCC;">
                                <td>合計金額</td>
                                <td></td>
                                <td></td>
                                <td><span id="cancelDetailPriceSum">{{number_format($cancelDetails->sum('sum_price'))}}</span></td>
                                <td><span id="cancelDetailSumRemarks"></span></td>
                            </tr>
                            </tbody>
                        </table>
                        <div class="form-group">
                            <div class="text-right">
                                <button type="button" class="btn btn-alt-primary" id="saveCancelDetail">キャンセル明細保存</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <!-- END Table -->
    </div>

    <!--  決済状況Table -->
    <div class="col-md-6">
        <div class="block">
            <div class="block-header block-header-default">
                <h3 class="block-title">決済状況(クレジットのみ)</h3>
            </div>
            <form>
                <div class="block-content">
                    <table class="table table-borderless table-vcenter">
                        <tr>
                            <th>入金済み</th>
                            <td class="text-right">{{number_format($payedPrice)}}</td>
                        </tr>
                        <tr style="border-bottom: 1px solid gray;">
                            <th> − キャンセル料</th>
                            <td class="text-right">{{number_format($cancelDetails->sum('sum_price'))}}</td>
                        </tr>
                        <tr>
                            <th>返金予定</th>
                            <td class="text-right">{{number_format($refundingPrice)}}</td>
                        </tr>
                        <tr style="border-bottom: 1px solid gray;">
                            <th>返金済</th>
                            <td class="text-right">{{number_format($refundedPrice)}}</td>
                        </tr>
                        <tr>
                            <th>残金</th>
                            <td class="text-right">{{number_format($remainingPrice)}}</td>
                        </tr>
                    </table>
                    <div class="form-group">
                        <div class="text-right">
                            <button type="button" class="btn btn-alt-large btn-alt-danger" id="execRefund">返金実行</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <!-- END Table -->
</div>
<div class="popup">
    <div class="popupContent">
        <div class="popupData" id='popupSkyticketApplicationNumber'></div>
        <div class="popupData" id='popupPaymentId'></div>
        <div class="popupData" id='popupCartId'></div>
        <div class="popupData" id='popupUserId'></div>
        <div class="popupData" id='popupProgressName'></div>
        <div class="popupData" id='popupPaymentMethod'></div>
        <div class="popupData" id='popupPaymentPrice'></div>
        <div class="popupData" id='popupPaidAt'></div>
        <div class="popupData" id='popupReceivedAt'></div>
        <button class="popupButton" id="popupClose">OK</button>
    </div>
</div>
<!-- END Content -->
@include('admin.Layouts.js_files')
<script src="{{ asset('vendor/admin/assets/js/paymentDetail.js').'?'.time() }}"></script>
@endsection
@include('admin.Layouts.footer')
