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
    <h2 class="content-heading">入金一覧</h2>
    <div class="block">
        <div class="block-content block-content-full">
            <form action="{{url('admin/payment')}}" method="get" class="form-inline">
                <select class="form-control mr-sm-2" name="payment_method">
                    <option value="{{config('const.payment.payment_method.credit')}}" @if(\Request::query('payment_method') == config('const.payment.payment_method.credit')) selected @endif>クレジット</option>
                </select>
                <input class="form-control mr-sm-2" name="cm_application_id" placeholder="skyticket申込番号"  value="{{\Request::query('cm_application_id')}}">
                <input class="form-control mr-sm-2" name="order_id" placeholder="econ注文番号"  value="{{\Request::query('order_id')}}">
                <select class="form-control mr-sm-2" name="log_status">
                    <option value="">決済処理結果(全て)</option>
                    @foreach(config('const.payment.log_status') as $logStatus => $desc)
                    <option value="{{$logStatus}}" @if(\Request::query('log_status') == $logStatus) selected @endif>{{$desc}}</option>
                    @endforeach
                </select>
                <input class="form-control mr-sm-2" name="reservation_id" placeholder="予約番号"  value="{{\Request::query('reservation_id')}}">
                <!-- 負荷が大きいので1日単位でしか検索させない -->
                <input class="form-control mr-sm-2 js-datepicker" name="date_filter" id="date_filter" autocomplete="off" data-week-start="1" data-autoclose="true" data-today-highlight="true" data-date-format="yyyy/mm/dd" placeholder="年/月/日" value="{{\Request::query('date_filter')}}">
                <button type="submit" class="btn btn-alt-primary" value="search">検索する</button>
            </form>
        </div>
    </div>

    <!-- Table -->
    <div class="block">
        <div class="block-header block-header-default">
            <h3 class="block-title">総件数 : {{$payments->count()}}件</h3>
        </div>
        <div class="block-content">
            <table class="table table-borderless table-vcenter">
                <thead>
                    <tr>
                        <th>@sortablelink('cm_application_id','skyticket申込番号')</th>
                        <th>econ注文番号</th>
                        <th>与信/計上</th>
                        <th>決済金額</th>
                        <th>決済処理結果</th>
                        <th>アクション</th>
                        <th>予約番号</th>
                        <th>@sortablelink('create_dt', '申込日時')</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($payments as $payment)
                    <tr>
                        <td>{{$payment->cm_application_id}}</td>
                        <td>{{$payment->order_id}}</td>
                        <td>{{$payment->keijou_str}}</td>
                        <td>{{number_format($payment->price)}}</td>
                        <td>{{$payment->status_str}}</td>
                        <td>@if(\Request::query('payment_method') == config('const.payment.payment_method.credit') && $payment->is_yoshin_cancel)
                            <button type="button" class="btn btn-alt-danger" id="acton" onclick="yoshinCancel('{{$payment->order_id}}', this)">取消</button>
                            @elseif(\Request::query('payment_method') == config('const.payment.payment_method.credit') && $payment->can_card_capture)
                            <button type="button" class="btn btn-alt-danger" id="acton" onclick="cardCapture('{{$payment->order_id}}', this)">計上</button>
                            @endif
                        </td>
                        <td><a href="{{url('admin/payment/detail/index/'.$payment->CmThApplicationDetail->application_id)}}">@if ($payment->CmThApplicationDetail->application_id > 0){{$payment->CmThApplicationDetail->reservation->app_cd}}{{$payment->CmThApplicationDetail->application_id}}@endif</a></td>
                        <td>{{$payment->create_dt}}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="block-content block-content-full block-content-sm bg-body-light font-size-md">
            @if($payments->count()) {{ $payments->appends(\Request::except('page'))->render() }} @endif
        </div>
    </div>
    <!-- END Table -->
</div>
<!-- END Content -->
@include('admin.Layouts.js_files')
<script>
    $.datetimepicker.setLocale('ja');
    $(function () {
        $('#date_filter').datetimepicker({
            format: 'Y-m-d',
            timepicker: false,
            lang: 'ja',
        });
    });
</script>
@endsection
<script src="{{ asset('vendor/admin/assets/js/payment.js').'?'.time() }}"></script>
@include('admin.Layouts.footer')
