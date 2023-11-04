@extends('admin.Layouts.base')
@include('admin.Layouts.head')

@include('admin.Layouts.side_overlay')
@include('admin.Layouts.sidebar')
@include('admin.Layouts.page_header')

@section('content')
    <!-- Content -->
    <div class="content">
    @include('admin.Layouts.flash_message')
    <!-- Default Table Style -->
        <h2 class="content-heading">精算会社一覧</h2>

        <div class="block">
            <div class="block-content block-content-full">
                <form action="{{ route('admin.settlementCompany') }}" method="get" class="form-inline">
                    <input class="form-control mr-sm-2" name="id" placeholder="ID" value="{{old('id', \Request::query('id'))}}">
                    <input class="form-control mr-sm-2" name="name" placeholder="名前"  value="{{old('name', \Request::query('name'))}}">
                    <input class="form-control mr-sm-2" name="tel" placeholder="電話番号"  value="{{old('tel', \Request::query('tel'))}}">
                    <input class="form-control mr-sm-2" name="postal_code" placeholder="郵便番号"  value="{{old('postal_code', \Request::query('postal_code'))}}">
                    <button type="submit" class="btn btn-alt-primary" value="search">検索する</button>
                </form>
            </div>
        </div>

        <!-- Table -->
        <div class="block">
            <div class="block-header block-header-default">
                <h3 class="block-title">総件数 : {{ $settlementCompanies->total() }}件</h3>
                <div class="block-options">
                    <div class="block-options-item">
                        <a href="{{ route('admin.settlementCompany.add') }}" class="btn btn-sm btn-secondary" data-toggle="tooltip" title="Add">
                            <i class="fa fa-plus"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="block-content">
                <table class="table table-borderless table-vcenter">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 50px;">@sortablelink('id','#')</th>
                            <th>@sortablelink('name','名前')</th>
                            <th>@sortablelink('tel','電話番号')</th>
                            <th>@sortablelink('postal_code','郵便番号')</th>
                            <th>@sortablelink('address','住所')</th>
                            <th>@sortablelink('payment_cycle','支払いサイクル')</th>
                            <th>@sortablelink('result_base_amount','成果基準額')</th>
                            <th>@sortablelink('tax_calculation','消費税計算')</th>
                            <th class="text-center" style="width: 100px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($settlementCompanies as $settlementCompany)
                        <tr @if(!$settlementCompany->published)class="table-dark" @endif>
                            <th class="text-center" scope="row">{{ $settlementCompany->id }}</th>
                            <td>{{ $settlementCompany->name }}</td>
                            <td>{{ $settlementCompany->tel }}</td>
                            <td>{{ $settlementCompany->postal_code }}</td>
                            <td>{{ $settlementCompany->address }}</td>
                            <td>{{ $cycle[$settlementCompany->payment_cycle] }}</td>
                            <td>{{ $baseAmount[$settlementCompany->result_base_amount] }}</td>
                            <td>{{ $tax[$settlementCompany->tax_calculation] }}</td>
                            @can('outHouseGeneral-onlySelf', [$settlementCompany->staff_id])
                            <td class="text-center">
                                <div class="btn-group">
                                    <a href="{{ route('admin.commissionRate', ['settlementCompanyId' => $settlementCompany->id]).'?page='.$page }}" class="btn btn-sm btn-secondary" data-toggle="tooltip">
                                        販売手数料
                                    </a>
                                    <a href="{{ route('admin.settlementCompany.edit', ['id' => $settlementCompany->id]) }}" class="btn btn-sm btn-secondary" data-toggle="tooltip" title="Edit">
                                        <i class="fa fa-pencil"></i>
                                    </a>
                                </div>
                            </td>
                            @endcan
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="block-content block-content-full block-content-sm bg-body-light font-size-md">
                {{ $settlementCompanies->appends(\Request::except('page'))->render() }}
            </div>
        </div>
        <!-- END Table -->

    </div>
    <!-- END Content -->

    @include('admin.Layouts.js_files')

@endsection

@include('admin.Layouts.footer')
