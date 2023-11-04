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
        <h2 class="content-heading">販売手数料設定</h2>

        @csrf
        <!-- Table -->
        <div class="block">
            <div class="block-header block-header-default">
                <h3 class="block-title">総件数 : {{ $commissionRates->count() }}件</h3>
                <div class="block-options">
                    <div class="block-options-item">
                        <a href="{{ route('admin.commissionRate.add', ['settlementCompanyId' => $settlementCompanyId]) }}" class="btn btn-sm btn-secondary" data-toggle="tooltip" title="Add">
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
                            <th>@sortablelink('accounting_condition','計上条件')</th>
                            <th>@sortablelink('apply_term_from','適用開始期間')</th>
                            <th>@sortablelink('apply_term_to','適用終了期間')</th>
                            <th>@sortablelink('fee','販売手数料(%/円)')</th>
                            <th>@sortablelink('only_seat','席のみ')</th>
                            <th class="text-center" style="width: 100px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($commissionRates as $commissionRate)
                        <tr @if(!$commissionRate->published)class="table-dark" @endif>
                            <td>{{ config('code.appCd.'.strtolower($commissionRate->app_cd))[$commissionRate->app_cd] }}</td>
                            <td>{{ $config['accounting_condition'][$commissionRate->accounting_condition] }}</td>
                            <td>{{ $commissionRate->apply_term_from }}</td>
                            <td>{{ $commissionRate->apply_term_to }}</td>
                            <td>{{ $commissionRate->fee }}</td>
                            <td>{{ $config['only_seat'][$commissionRate->only_seat] }}</td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <a href="{{ route('admin.commissionRate.edit', ['settlementCompanyId' => $settlementCompanyId, 'id' => $commissionRate->id]) }}" class="btn btn-sm btn-secondary" data-toggle="tooltip" title="Edit">
                                        <i class="fa fa-pencil"></i>
                                    </a>
                                    <a href="#" class="btn btn-sm btn-secondary" data-toggle="tooltip" title="Delete" id="{{$commissionRate->id}}">
                                        <i class="fa fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <!-- END Table -->
        <button type="button" class="btn btn-secondary" onclick="location.href='{{ old('redirect_to', route('admin.settlementCompany').'?page='.$page) }}'">戻る</button>

    </div>
    <!-- END Content -->

    @include('admin.Layouts.js_files')
    <script src="{{ asset('vendor/admin/assets/js/commissionRate.js').'?'.time() }}"></script>

@endsection

@include('admin.Layouts.footer')
