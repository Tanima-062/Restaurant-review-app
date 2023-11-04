<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>@if($clientInvoice->settlementAmount > 0)支払通知書 @else 請求書 @endif</title>
<style type="text/css">
    body {
        width : 900px;
    }

    h1 {
        text-align: center;
    }

    h2 {
        margin-bottom : 10px;
    }

    table {
        border-collapse: collapse;
        border: solid 2px black;
    }

    th, td {
        border: solid 1px black;
        text-align: center;
    }

    th {
        background-color: silver;
        font-weight: normal;
    }

    .day-of-issue {
        text-align: right;
    }

    .image-right {
        float: right;
        vertical-align: top;
    }

    .pay-box {
        width: 50%;
    }

    .long-table {
        width: 100%;
    }

    .bolder {
        font-weight: bolder;
    }

    .table-price {
        text-align: right;
        padding: 0px 2px 0px 0px;
    }

    .sub-table-title {
        text-align: left;
        background-color: #EEEEEE;
        padding: 0px 0px 0px 2px;
        font-weight: bolder;
    }

    .info {
        text-align: right;
        padding: 0px 5px 0px 0px;
    }

    .remark {
        width: 100%;
        height: 150px;
    }

    .subscript {
        width: 15%;
    }

    .account {
        text-align: left;
        padding: 0px 0px 0px 2px;
    }

    .ori-title {
        width: 40%;
        border-right:none;
        text-align: left;
        padding: 0px 0px 0px 5px;
    }

    .ori-sub-title {
        background-color: white;
        font-weight: bolder;
    }

    .sub-store {
        width: 50%;
    }

    .sub-app {
        width: 20%;
    }

    .sub-amount {
        width: 30%;
    }

    .agg {
        border-top: 2px solid;
    }
</style>
</head>
<body>
<br>
    <h1>@if($clientInvoice->settlementAmount > 0)支払通知書 @else 請求書 @endif</h1>
    <div class="day-of-issue">発行日: {{date('Y/m/d')}}</div>
    <h2>{{$clientInvoice->settlementCompany->name}} &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;御中</h2>
    <div class="image-right"><img src="data:image/jpeg;base64,{{base64_encode(@file_get_contents(public_path('vendor/admin/assets/images/adventure.png')))}}" alt="adventure" title="logo"></div>
    <br><br><br><div>
        @if($clientInvoice->settlementAmount > 0)
            下記の通り、お支払い申し上げます。
        @else
            下記の通り、ご請求申し上げます。
        @endif
    </div><br>
    <table class="pay-box">
        <thead>
            <tr>
                <th>@if($clientInvoice->settlementAmount > 0)支払金額 @else ご請求金額 @endif<br>(消費税込)</th>
                <th class="bolder">&yen;{{number_format(abs($clientInvoice->settlementAmount))}}</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>@if($clientInvoice->settlementAmount > 0)支払期限日 @else お支払い期限 @endif</td>
                <td>{{date('Y/m/d', strtotime($clientInvoice->settlementDownload->payment_deadline))}}</td>
            </tr>
        </tbody>
    </table>
    <div>{{$clientInvoice->deferredDesc()}}</div>
    <br>

    <b>・</b>精算
    <table class="long-table">
        <thead>
        <tr>
            <th colspan="2">品目</th>
            <th>金額</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td class="subscript">A</td>
            <td>支払合計</td>
            <td class="table-price">&yen;{{number_format($clientInvoice->payAmount)}}</td>
        </tr>
        <tr>
            <td>B</td>
            <td>請求合計</td>
            <td class="table-price">&yen;{{number_format($clientInvoice->invoiceAmount)}}</td>
        </tr>
        <tr>
            <td>C</td>
            <td>前期繰越</td>
            <td class="table-price">&yen;{{number_format($clientInvoice->deferredPrice)}}</td>
        </tr>
        <tr class="agg">
            <td colspan="2">精算額 [A-B+C]</td>
            <td class="table-price">&yen;{{number_format($clientInvoice->settlementAmount)}}</td>
        </tr>
        </tbody>
    </table>
    <table>
        <tbody>

        </tbody>
    </table>
    <div class="info">※精算額がマイナスの場合はご請求、プラスの場合はお支払いになります。</div>
    <br>

    <b>・</b>A : 支払内訳
    <table class="long-table">
        <thead>
            <tr>
                <th colspan="2">品目</th>
                <th>金額</th>
            </tr>
        </thead>
        <tbody>
            <tr class="sub-table-title">
                <td colspan="3" class="sub-table-title">アドベンチャー支払分</td>
            </tr>
            <tr>
                <td class="subscript">D</td>
                <td>{{$clientInvoice->formatYm()}} 成約</td>
                <td class="table-price">&yen;{{number_format($clientInvoice->aggregateEnsureAmount)}}</td>
            </tr>
            <tr>
                <td>E</td>
                <td>{{$clientInvoice->formatYm()}} キャンセル料</td>
                <td class="table-price">&yen;{{number_format($clientInvoice->aggregateCancelFeeAmount)}}</td>
            </tr>
            <tr>
                <td>F</td>
                <td>{{$clientInvoice->formatYm()}} 手数料 [(D+E)×手数料率]</td>
                <td class="table-price">&yen;{{number_format($clientInvoice->commissionAmount)}}</td>
            </tr>
            <tr>
                <td>G</td>
                <td>{{$clientInvoice->formatYm()}} 消費税 [F×{{$clientInvoice->tax}}%]</td>
                <td class="table-price">&yen;{{number_format($clientInvoice->commissionTax)}}</td>
            </tr>
            <tr class="agg">
                <td colspan="2">支払合計 [(D+E)-(F+G)]</td>
                <td class="table-price">&yen;{{number_format($clientInvoice->payAmount)}}</td>
            </tr>
        </tbody>
    </table>
    <br>

    <b>・</b>B : 請求内訳
    <table class="long-table">
        <thead>
        <tr>
            <th colspan="2">品目</th>
            <th>金額</th>
        </tr>
        </thead>
        <tbody>
        <tr class="sub-table-title">
            <td colspan="3" class="sub-table-title">アドベンチャー請求分 (貴社お支払い)</td>
        </tr>
        <tr>
            <td class="subscript">H</td>
            <td>席のみ予約 [予約人数({{$clientInvoice->onlySeatNumber}}人)×180円]</td>
            <td class="table-price">&yen;{{number_format($clientInvoice->onlySeatAmount)}}</td>
        </tr>
        <tr>
            <td>I</td>
            <td>電話予約</td>
            <td class="table-price">&yen;{{number_format($clientInvoice->telAmount)}}</td>
        </tr>
        <tr>
            <td>J</td>
            <td>消費税 [F×{{$clientInvoice->tax}}%]</td>
            <td class="table-price">&yen;{{number_format($clientInvoice->otherCommissionTax)}}</td>
        </tr>
        <tr class="agg">
            <td colspan="2">請求合計 [G+H+J]</td>
            <td class="table-price">&yen;{{number_format($clientInvoice->invoiceAmount)}}</td>
        </tr>
        </tbody>
    </table>
    <br>

    <div>
        <b>・</b>備考情報<br>
        <table class="remark">
            <thead>
                <tr>
                    <th>&nbsp;</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>&nbsp;</td>
                </tr>
            </tbody>
        </table>
    </div>
    <br>

    <div>
        @if($clientInvoice->settlementAmount > 0)
        ・お振込み先<br>
        銀行・支店名： {{$clientInvoice->settlementCompany->bank_name}} {{$clientInvoice->settlementCompany->branch_name}}<br>
        口座番号：    {{$clientInvoice->accountTypeStr()}} {{$clientInvoice->settlementCompany->account_number}}<br>
        口座名義：    {{$clientInvoice->settlementCompany->account_name_kana}}<br>
        @else
        <b>・</b>振込先情報<br>
        <table class="long-table">
            <thead>
            <tr>
                <th class="account">振込先口座</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td class="account"><br>三菱UFJ銀行　渋谷中央支店<br>普通　7817285　カ）アドベンチャー<br><br></td>
            </tr>
            </tbody>
        </table>
        ※振込手数料は貴社でご負担願います
        @endif
    </div>
    <br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>

    <table class="long-table">
        <thead>
            <tr>
                <th colspan="3" class="ori-title">D : {{$clientInvoice->formatYm()}} 成約 明細</th>
            </tr>
            <tr>
                <th class="ori-sub-title sub-store">店舗名</th>
                <th class="ori-sub-title sub-app">利用サービス</th>
                <th class="ori-sub-title sub-amount">金額</th>
            </tr>
        </thead>
        <tbody>
            @foreach($clientInvoice->ensureDetails as $ensureDetail)
            <tr>
                <td>{{$ensureDetail['storeName']}}</td>
                <td>{{$ensureDetail['appCd']}}</td>
                <td class="table-price">&yen;{{number_format($ensureDetail['amount'])}}</td>
            </tr>
            @endforeach
            <tr class="agg">
                <td colspan="2" class="info">成約合計</td>
                <td class="table-price">&yen;{{number_format(array_sum(array_column($clientInvoice->ensureDetails, 'amount')))}}</td>
            </tr>
        </tbody>
    </table>
    <div class="info">※席のみ予約の場合は０円の表記になります。</div><br>

    <table class="long-table">
        <thead>
        <tr>
            <th colspan="3" class="ori-title">E : {{$clientInvoice->formatYm()}} キャンセル料 明細</th>
        </tr>
        <tr>
            <th class="ori-sub-title sub-store">店舗名</th>
            <th class="ori-sub-title sub-app">利用サービス</th>
            <th class="ori-sub-title sub-amount">金額</th>
        </tr>
        </thead>
        <tbody>
        @foreach($clientInvoice->cancelDetails as $cancelDetail)
            <tr>
                <td>{{$cancelDetail['storeName']}}</td>
                <td>{{$cancelDetail['appCd']}}</td>
                <td class="table-price">&yen;{{number_format($cancelDetail['amount'])}}</td>
            </tr>
        @endforeach
            <tr class="agg">
                <td colspan="2" class="info">キャンセル料合計</td>
                <td class="table-price">&yen;{{number_format(array_sum(array_column($clientInvoice->cancelDetails, 'amount')))}}</td>
            </tr>
        </tbody>
    </table><br>

    <table class="long-table">
        <thead>
        <tr>
            <th colspan="3" class="ori-title">G : {{$clientInvoice->formatYm()}} 席のみ予約手数料 明細</th>
        </tr>
        <tr>
            <th class="ori-sub-title sub-store">店舗名</th>
            <th class="ori-sub-title sub-app">人数</th>
            <th class="ori-sub-title sub-amount">金額</th>
        </tr>
        </thead>
        <tbody>
        @foreach($clientInvoice->onlySeatDetails as $onlySeatDetail)
            <tr>
                <td>{{$onlySeatDetail['storeName']}}</td>
                <td>{{$onlySeatDetail['number']}}</td>
                <td class="table-price">&yen;{{number_format($onlySeatDetail['amount'])}}</td>
            </tr>
        @endforeach
            <tr class="agg">
                <td colspan="2" class="info">席のみ予約手数料</td>
                <td class="table-price">&yen;{{number_format(array_sum(array_column($clientInvoice->onlySeatDetails, 'amount')))}}</td>
            </tr>
        </tbody>
    </table><br>

    <table class="long-table">
        <thead>
        <tr>
            <th colspan="3" class="ori-title">H : {{$clientInvoice->formatYm()}} 電話予約手数料 明細</th>
        </tr>
        <tr>
            <th class="ori-sub-title sub-store">店舗名</th>
            <th class="ori-sub-title sub-app">件数</th>
            <th class="ori-sub-title sub-amount">金額</th>
        </tr>
        </thead>
        <tbody>
        @foreach($clientInvoice->callDetails as $callDetails)
            <tr>
                <td>{{$callDetails['storeName']}}</td>
                <td>{{$callDetails['count']}}</td>
                <td class="table-price">&yen;{{$callDetails['amount']}}</td>
            </tr>
        @endforeach
            <tr class="agg">
                <td colspan="2" class="info">電話予約手数料</td>
                <td class="table-price">&yen;{{number_format($clientInvoice->telAmount)}}</td>
            </tr>
        </tbody>
    </table>
<br>
<br>
<br>
<br>
<br>
</body>
</html>
