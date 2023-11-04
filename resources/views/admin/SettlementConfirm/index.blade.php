@extends('admin.Layouts.base')
@include('admin.Layouts.head')

@include('admin.Layouts.side_overlay')
@include('admin.Layouts.sidebar')
@include('admin.Layouts.page_header')

@section('content')
    <style>
        table.center {
            text-align: center;
        }

        .cel-red {
            background-color: red;
            color: white;
            padding: 5px 20px 5px 20px;
        }

        .cel-pink {
            background-color: hotpink;
            color: yellow;
            padding: 5px 20px 5px 20px;
        }

        .cel-blue {
            background-color: blue;
            color: white;
            padding: 5px 20px 5px 20px;
        }
    </style>
    <!-- Content -->
    <div class="content">
    @include('admin.Layouts.flash_message')
    <!-- Default Table Style -->
        <h2 class="content-heading">精算確認</h2>

        <div class="block">
            <div class="block-content block-content-full">
                <form action="{{url('admin/settlement_confirm')}}" method="get" class="form-inline">
                    <div class="row">
                        <table class="table table-borderless">
                            <th style="vertical-align: middle">精算管理会社</th>
                            <td>
                                <input class="form-control mr-sm-2" id="settlementCompanyName" name="settlementCompanyName" placeholder="精算管理会社名" value="{{old('settlementCompanyName', \Request::query('settlementCompanyName'))}}">
                            </td>
                            <th style="vertical-align: middle">精算月</th>
                            <td><select class="form-control mr-sm-2" name="monthSt">
                                    <option value="">-- 選択して下さい--</option>
                                    @foreach ($settlementYYYYmm as $yyyymm)
                                        <option value="{{$yyyymm}}" @if(\Request::query('monthSt') == $yyyymm) selected @endif>{{substr_replace($yyyymm, '/', 4, 0)}}</option>
                                    @endforeach
                                </select>
                                〜&nbsp;&nbsp;
                                <select class="form-control mr-sm-2" name="monthEd">
                                    <option value="">-- 選択して下さい--</option>
                                    @foreach ($settlementYYYYmm as $yyyymm)
                                        <option value="{{$yyyymm}}" @if(\Request::query('monthEd') == $yyyymm) selected @endif>{{substr_replace($yyyymm, '/', 4, 0)}}</option>
                                    @endforeach
                                </select>
                            </td>
                            <th></th>
                            <td>
                                <button type="submit" class="btn btn-alt-primary" value="search">検索する</button>
                            </td>
                        </table>
                    </div>
                </form>
            </div>
        </div>

        <!-- Table -->
        <div class="block">
            <div class="block-content">
                <table class="table table-borderless table-vcenter center">
                    <thead>
                        <tr>
                            <th>精算月</th>
                            <th>精算管理会社</th>
                            <th>対象期間</th>
                            <th>支払/請求種別</th>
                            <th>ダウンロード</th>
                            <th>ダウンロード日時</th>
                            <th><span class="cel-red">支払期限</span></th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($settlementDownloads as $settlementDownload)
                        <tr>
                            <td>{{substr_replace($settlementDownload->month, '/', 4, 0)}}</td>
                            <td>{{$settlementDownload->settlementCompany->name}}</td>
                            <td>{{$settlementDownload->start_term}}日〜{{$settlementDownload->end_term}}日</td>
                            <td>@if($settlementDownload->type == 'INVOICE')
                                    <span class="cel-pink">{{$settlementType[$settlementDownload->type]['label']}}</span>
                                @else
                                    <span class="cel-blue">{{$settlementType[$settlementDownload->type]['label']}}</span>
                                @endif
                            </td>
                            <td><a href="{{$settlementDownload->pdf_url}}" target="_blank">PDFダウンロード</a></td>
                            <td>{{$settlementDownload->download_at}}</td>
                            <td>{{date('Y-m-d', strtotime($settlementDownload->payment_deadline))}}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <!-- END Table -->

    </div>
    <!-- END Content -->

    @include('admin.Layouts.js_files')

@endsection

@include('admin.Layouts.footer')
