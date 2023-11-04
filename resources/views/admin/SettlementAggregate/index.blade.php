@extends('admin.Layouts.base')
@include('admin.Layouts.head')

@include('admin.Layouts.side_overlay')
@include('admin.Layouts.sidebar')
@include('admin.Layouts.page_header')

@section('content')
    <style>
        .scroll-table table {
            display: block;
            overflow-x: scroll;
            white-space: nowrap;
            -webkit-overflow-scrolling: touch;
        }
    </style>
    <!-- Content -->
    <div class="content" style="max-width:1720px">
    @include('admin.Layouts.flash_message')
    <!-- Default Table Style -->
        <h2 class="content-heading">精算額集計</h2>

        <div class="block  col-md-10">
            <div class="block-content block-content-full">
                <form action="{{url('admin/settlement_aggregate')}}" method="get">
                    <div class="row">
                        <table class="table table-borderless">
                            <thead>
                                <tr>
                                    <th>精算ターム</th>
                                    <th>計上期間</th>
                                    <th>精算管理会社</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <div class="custom-control custom-checkbox">
                                            <input class="custom-control-input" type="checkbox" name="monthOne" id="monthOne" value="1" @if(\Request::query('monthOne') == 1) checked @endif>
                                            <label class="custom-control-label" for="monthOne">月1回(1日~15日)</label>
                                        </div><br>
                                        <div class="custom-control custom-checkbox">
                                            <input class="custom-control-input" type="checkbox" name="monthTwo" id="monthTwo" value="2" @if(\Request::query('monthTwo') == 2) checked @endif>
                                            <label class="custom-control-label" for="monthTwo">月2回(16日~末日)</label>
                                        </div>
                                    </td>
                                    <td>
                                        <select class="form-control mr-sm-2" name="termYear" style="width: 70%">
                                            @foreach (range(2021, date('Y')) as $year)
                                                <option value="{{$year}}" @if(\Request::query('termYear') == $year) selected @endif>{{$year}}年</option>
                                            @endforeach
                                        </select><br>
                                        <select class="form-control mr-sm-2" name="termMonth" style="width: 70%">
                                            @foreach (range(1, 12) as $month)
                                                <option value="{{$month}}" @if(\Request::query('termMonth') == $month) selected @endif>{{$month}}月</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control mr-sm-2" name="settlementCompanyId">
                                            <option value="0">全て</option>
                                            @foreach ($settlementCompanies as $settlementCompany)
                                                <option value="{{$settlementCompany->id}}" @if(\Request::query('settlementCompanyId') == $settlementCompany->id) selected @endif>{{$settlementCompany->name}}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td style="vertical-align: bottom; text-align : center">
                                        <button type="submit" class="btn btn-alt-primary" value="search">検索する</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>
        </div>

        <!-- 全体集計 -->
        @if(\Auth::user()->settlement_company_id == 0)
        <div>
            <div class="block-header block-header-default">
                <h3 class="block-title">全体集計</h3>
            </div>
            <div class="table-responsive" style="background-color: white">
                <table class="table table-vcenter table-bordered scroll-table" style="margin: 15px">
                    <thead>
                        <tr>
                            <th class="text-nowrap">精算対象期間</th>
                            <th class="text-nowrap">成約件数<br>(事前決済)</th>
                            <th class="text-nowrap">成約金額<br>(事前決済)</th>
                            <th class="text-nowrap">成約件数<br>(現地決済)</th>
                            <th class="text-nowrap">成約人数<br>(事前決済)</th>
                            <th class="text-nowrap">ｷｬﾝｾﾙ件数</th>
                            <th class="text-nowrap">ｷｬﾝｾﾙ料(ﾒｲﾝ)</th>
                            <th class="text-nowrap">ｷｬﾝｾﾙ料(ｵﾌﾟｼｮﾝ)</th>
                            <th class="text-nowrap">ｷｬﾝｾﾙ料合計</th>
                            <th class="text-nowrap">販売手数料 - 税抜</th>
                            <th class="text-nowrap">販売手数料 - 消費税</th>
                            <th class="text-nowrap">販売手数料 - 税込</th>
                            <th class="text-nowrap">販売手数料 - 税抜<br>(現地決済)</th>
                            <th class="text-nowrap">販売手数料 - 消費税<br>(現地決済)</th>
                            <th class="text-nowrap">販売手数料 - 税込<br>(現地決済)</th>
                            <th class="text-nowrap">ｷｬﾝｾﾙ料手数料 - 税抜</th>
                            <th class="text-nowrap">ｷｬﾝｾﾙ料手数料 - 消費税</th>
                            <th class="text-nowrap">ｷｬﾝｾﾙ料手数料 - 税込</th>
                            <th class="text-nowrap">電話通知手数 - 税抜</th>
                            <th class="text-nowrap">電話通知手数 - 消費税</th>
                            <th class="text-nowrap">電話通知手数 - 税込</th>
                            <th class="text-nowrap">営業利益</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($allAggregate as $agg)
                        <tr>
                            <td>{{$agg['term']}}</td>
                            <td>{{number_format($agg['close_num'])}}</td>
                            <td>{{number_format($agg['total'])}}</td>
                            <td>{{number_format($agg['local_num'])}}</td>
                            <td>{{number_format($agg['local_people'])}}</td>
                            <td>{{number_format($agg['cancel_num'])}}</td>
                            <td>{{number_format($agg['cancel_detail_price_main'])}}</td>
                            <td>{{number_format($agg['cancel_detail_price_option'])}}</td>
                            <td>{{number_format($agg['cancel_detail_price_sum'])}}</td>
                            <td>{{number_format($agg['commission_rate_fixed_fee_no_tax'])}}</td>
                            <td>{{number_format($agg['commission_fixed_tax'])}}</td>
                            <td>{{number_format($agg['commission_rate_fixed_fee_tax'])}}</td>
                            <td>{{number_format($agg['commission_rate_flat_fee_no_tax'])}}</td>
                            <td>{{number_format($agg['commission_flat_tax'])}}</td>
                            <td>{{number_format($agg['commission_rate_flat_fee_tax'])}}</td>
                            <td>{{number_format($agg['cancel_rate_fixed_fee_no_tax'])}}</td>
                            <td>{{number_format($agg['cancel_fixed_tax'])}}</td>
                            <td>{{number_format($agg['cancel_rate_fixed_fee_tax'])}}</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td>{{number_format($agg['commission_rate_fixed_fee_tax']+$agg['commission_rate_flat_fee_tax']+$agg['cancel_rate_fixed_fee_tax'])}}</td><!-- TODO:電話手数料を足す必要あり -->
                        </tr>
                        @endforeach
                        <tr>
                            <td>合計</td>
                            <td>{{number_format($allAggregate->sum('close_num'))}}</td>
                            <td>{{number_format($allAggregate->sum('total'))}}</td>
                            <td>{{number_format($allAggregate->sum('local_num'))}}</td>
                            <td>{{number_format($allAggregate->sum('local_people'))}}</td>
                            <td>{{number_format($allAggregate->sum('cancel_num'))}}</td>
                            <td>{{number_format($allAggregate->sum('cancel_detail_price_main'))}}</td>
                            <td>{{number_format($allAggregate->sum('cancel_detail_price_option'))}}</td>
                            <td>{{number_format($allAggregate->sum('cancel_detail_price_sum'))}}</td>
                            <td>{{number_format($allAggregate->sum('commission_rate_fixed_fee_no_tax'))}}</td>
                            <td>{{number_format($allAggregate->sum('commission_fixed_tax'))}}</td>
                            <td>{{number_format($allAggregate->sum('commission_rate_fixed_fee_tax'))}}</td>
                            <td>{{number_format($allAggregate->sum('commission_rate_flat_fee_no_tax'))}}</td>
                            <td>{{number_format($allAggregate->sum('commission_flat_tax'))}}</td>
                            <td>{{number_format($allAggregate->sum('commission_rate_flat_fee_tax'))}}</td>
                            <td>{{number_format($allAggregate->sum('cancel_rate_fixed_fee_no_tax'))}}</td>
                            <td>{{number_format($allAggregate->sum('cancel_fixed_tax'))}}</td>
                            <td>{{number_format($allAggregate->sum('cancel_rate_fixed_fee_tax'))}}</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td>{{number_format($allAggregate->sum('commission_rate_fixed_fee_tax')+$allAggregate->sum('commission_rate_flat_fee_tax')+$allAggregate->sum('cancel_rate_fixed_fee_tax'))}}</td><!-- TODO:電話手数料を足す必要あり -->
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <br><br>
    @endif
        <!-- END 全体集計 -->

        <!-- 精算管理会社別 -->
        <div>
            <div class="block-header block-header-default">
                <h3 class="block-title">精算管理会社別集計</h3>
            </div>
            <div class="table-responsive" style="background-color: white">
                <table class="table table-vcenter table-bordered scroll-table" style="margin: 15px">
                    <thead>
                    <tr>
                        <th class="text-nowrap">経理用管理ｺｰﾄﾞ</th>
                        <th class="text-nowrap">精算管理会社名</th>
                        <th class="text-nowrap">店舗</th>
                        <th class="text-nowrap">サービス名</th>
                        <th class="text-nowrap">対象期間</th>
                        <th class="text-nowrap">成約件数<br>(事前決済)</th>
                        <th class="text-nowrap">成約金額<br>(事前決済)</th>
                        <th class="text-nowrap">成約件数<br>(現地決済)</th>
                        <th class="text-nowrap">成約人数<br>(現地決済)</th>
                        <th class="text-nowrap">ｷｬﾝｾﾙ件数</th>
                        <th class="text-nowrap">ｷｬﾝｾﾙ料(ﾒｲﾝ)</th>
                        <th class="text-nowrap">ｷｬﾝｾﾙ料(ｵﾌﾟｼｮﾝ)</th>
                        <th class="text-nowrap">ｷｬﾝｾﾙ料合計<br>(全体)</th>
                        <th class="text-nowrap">販売手数料 - 料率<br>(事前決済)</th>
                        <th class="text-nowrap">販売手数料 - 税抜<br>(事前決済)</th>
                        <th class="text-nowrap">販売手数料 - 消費税<br>(事前決済)</th>
                        <th class="text-nowrap">販売手数料 - 税込<br>(事前決済)</th>
                        <th class="text-nowrap">販売手数料 - 税抜<br>(現地決済)</th>
                        <th class="text-nowrap">販売手数料 - 消費税<br>(現地決済)</th>
                        <th class="text-nowrap">販売手数料 - 税込<br>(現地決済)</th>
                        <th class="text-nowrap">ｷｬﾝｾﾙ料手数料 - 料率</th>
                        <th class="text-nowrap">ｷｬﾝｾﾙ料手数料 - 税抜</th>
                        <th class="text-nowrap">ｷｬﾝｾﾙ料手数料 - 消費税</th>
                        <th class="text-nowrap">ｷｬﾝｾﾙ料手数料 - 税込</th>
                        <th class="text-nowrap">電話通知手数料 - 定額</th>
                        <th class="text-nowrap">電話通知手数料 - 税抜</th>
                        <th class="text-nowrap">電話通知手数料 - 消費税</th>
                        <th class="text-nowrap">電話通知手数料 - 税込</th>
                        <th class="text-nowrap">当期精算</th>
                        <th class="text-nowrap">前期繰越</th>
                        <th class="text-nowrap">精算金額</th>
                        <th class="text-nowrap">精算種別</th>
                    </tr>
                    </thead>
                    <tbody>
                        @foreach($aggregate as $key => $agg)
                        <tr @if(strpos($key, '.S') !== false)style="font-weight: bold" @endif>
                         <td></td>
                         <td class="text-nowrap">@if($agg['v_settle']){{$agg['settlement_company_name']}}@endif</td>
                         <td class="text-nowrap">@if($agg['v_shop']){{$agg['shop_name']}}@endif</td>
                         <td class="text-nowrap">@if($agg['v_app_cd']){{$agg['app_cd']}}@endif</td>
                         <td class="text-nowrap">@if($agg['v_term']){{$agg['term']}}@endif</td>
                         <td>{{$agg['close_num']}}</td>
                         <td>{{number_format($agg['total'])}}</td>
                         <td>{{$agg['local_num']}}</td>
                         <td>{{$agg['local_people']}}</td>
                         <td>{{$agg['cancel_num']}}</td>
                         <td>{{number_format($agg['cancel_detail_price_main'])}}</td>
                         <td>{{number_format($agg['cancel_detail_price_option'])}}</td>
                         <td>{{number_format($agg['cancel_detail_price_sum'])}}</td>
                         <td>@if($agg['commission_rate_fixed']) {{$agg['commission_rate_fixed']}}% @endif</td>
                         <td>{{number_format($agg['commission_rate_fixed_fee_no_tax'])}}</td>
                         <td>{{number_format($agg['commission_fixed_tax'])}}</td>
                         <td>{{number_format($agg['commission_rate_fixed_fee_tax'])}}</td>
                         <td>{{number_format($agg['commission_rate_flat_fee_no_tax'])}}</td>
                         <td>{{number_format($agg['commission_flat_tax'])}}</td>
                         <td>{{number_format($agg['commission_rate_flat_fee_tax'])}}</td>
                         <td>@if($agg['cancel_rate']) {{$agg['cancel_rate']}}% @endif</td>
                         <td>{{number_format($agg['cancel_rate_fixed_fee_no_tax'])}}</td>
                         <td>{{number_format($agg['cancel_fixed_tax'])}}</td>
                         <td>{{number_format($agg['cancel_rate_fixed_fee_tax'])}}</td>
                         <td></td>
                         <td></td>
                         <td></td>
                         <td></td>
                         <td>{{number_format($agg['current_settlement_price'])}}</td>
                         <td>{{number_format($agg['carry_forward_price'])}}</td>
                         <td>{{number_format($agg['settlement_price'])}}</td>
                         <td>@if(!empty($agg['settlement_type'])) {{$agg['settlement_type']}} @endif</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <!-- END 全体集計 -->
        <div class="block-content block-content-full block-content-sm bg-body-light font-size-md">
            {{ $partSettlementCompanies->appends(\Request::except('page'))->render() }}
        </div>
    </div>
    <!-- END Content -->

    @include('admin.Layouts.js_files')

@endsection

@include('admin.Layouts.footer')
