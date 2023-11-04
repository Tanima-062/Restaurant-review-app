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
            <form action="{{url('admin/newpayment')}}" method="get" class="form-inline">
                <input class="form-control mr-sm-2 js-datepicker" name="date_from" id="date_from" autocomplete="off" data-week-start="1" data-autoclose="true" data-today-highlight="true" data-date-format="yyyy/mm/dd" placeholder="年/月/日" value="{{\Request::query('date_from')}}">
                <div class="from-to">-</div>
                <input class="form-control mr-sm-2 js-datepicker" name="date_to" id="date_to" autocomplete="off" data-week-start="1" data-autoclose="true" data-today-highlight="true" data-date-format="yyyy/mm/dd" placeholder="年/月/日" value="{{\Request::query('date_to')}}">
                <select class="form-control mr-sm-2" name="serviceCd">
                    @foreach(config('const.newPayment.apiServiceCd') as $key => $val)
                    <option value="{{$val['value']}}" @if(\Request::query('serviceCd') == $val['value']) selected @endif>{{$val['name']}}</option>
                    @endforeach
                </select>
                <input class="form-control mr-sm-2" name="id" placeholder="skyticket申込番号"  value="{{\Request::query('id')}}">
                <input class="form-control mr-sm-2" name="cart_id" placeholder="カート番号"  value="{{\Request::query('cart_id')}}">
                <input class="form-control mr-sm-2" name="order_code" placeholder="注文番号"  value="{{\Request::query('order_code')}}">
                <select class="form-control mr-sm-2" name="progress">
                    <option value="">決済処理結果</option>
                    @foreach(config('code.skyticketPayment.progress') as $status => $progressInfo)
                    <option value="{{$progressInfo['progress']}}" @if(\Request::query('progress') == $progressInfo['progress']) selected @endif>{{$progressInfo['progressName']}}</option>
                    @endforeach
                </select>
                <!-- 負荷が大きいので1日単位でしか検索させない -->
                <button type="submit" class="btn btn-alt-primary action" name="action" value="search">検索する</button>
                <button type="submit" class="btn btn-alt-primary action" name="action" value="csv">CSV出力</button>
            </form>
        </div>
    </div>

    <!-- Table -->
    <div class="block">
        <div class="block-header block-header-default">
            <h3 class="block-title">総件数 : {{$total}}件</h3>
        </div>
        <div class="block-content">
            <table class="table table-borderless table-vcenter">
                <thead>
                    <tr>
                        <th class="payment-title" colspan="7">入金情報</th>
                        <th class="payment-title" colspan="7">グルメ予約情報</th>
                    </tr>
                    <tr>
                        <th class="payment-title">skyticket申込番号</th>
                        <th class="payment-title">カート番号</th>
                        <th class="payment-title">注文番号</th>
                        <th class="payment-title">決済開始日</th>
                        <th class="payment-title">決済金額</th>
                        <th class="payment-title">決済処理結果</th>
                        <th class="payment-title">アクション</th>
                        <th class="payment-title">予約番号</th>
                        <th class="payment-title">予約ステータス</th>
                        <th class="payment-title">入金ステータス</th>
                        <th class="payment-title">会社名</th>
                        <th class="payment-title">申込日時</th>
                        <th class="payment-title">キャンセル申込日時</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($payments as $payment)
                    <tr>
                        <td>
                        @foreach($payment['cm_application_ids'] as $v)
                            {{$v[0]}}
                        @endforeach
                        </td>
                        <td>{{$payment['cart_id']}}</td>
                        <td>{{$payment['order_code']}}</td>
                        <td>{{$payment['created_at']}}</td>
                        <td>{{$payment['price']}}</td>
                        <td>{{$payment['progress_name']}}</td>

                        <td>
                            @php ($orderCode = $payment['order_code'])
                            @php ($reservationId = $payment['reservation_id'])
                            <button @if($payment['progress'] != config('code.skyticketPayment.progress.AUTHORIZED.progress')) disabled @endif type="button" class="btn btn-alt-danger action" id="acton" onclick="yoshinCancel('{{$orderCode}}', '{{$reservationId}}',this)">取消</button>
                            <button @if($payment['progress'] != config('code.skyticketPayment.progress.AUTHORIZED.progress')) disabled @endif type="button" class="btn btn-alt-danger action" id="acton" onclick="cardCapture('{{$orderCode}}', '{{$reservationId}}', this)">計上</button>
                        </td>
                        <td><a href="newpayment/detail/{{$payment['reservation_id']}}">{{$payment['reservation_number']}}</a></td>
                        <td>{{$payment['reservation_status']}}</td>
                        <td>{{$payment['payment_status']}}</td>
                        <td>{{$payment['name']}}</td>
                        <td>{{$payment['created_at']}}</td>
                        <td>{{$payment['cancel_datetime']}}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="block-content block-content-full block-content-sm bg-body-light font-size-md">
        {{ $paginator->links() }}
        </div>
    </div>
    <!-- END Table -->
</div>
<!-- END Content -->
@include('admin.Layouts.js_files')
<script>
    $.datetimepicker.setLocale('ja');
    $(function () {
        $('#date_from').datetimepicker({
            format: 'Y-m-d',
            timepicker: false,
            lang: 'ja',
        });
        $('#date_to').datetimepicker({
            format: 'Y-m-d',
            timepicker: false,
            lang: 'ja',
        });
    });

</script>
@endsection
<script src="{{ asset('vendor/admin/assets/js/newpayment.js').'?'.time() }}"></script>
@include('admin.Layouts.footer')
