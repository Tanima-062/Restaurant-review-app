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
        <h2 class="content-heading">販売手数料編集</h2>
        <!-- Floating Labels -->
        <div class="block col-md-9">
            <div class="block-content">
                <form action="{{ route('admin.commissionRate.edit', ['settlementCompanyId' => $settlementCompanyId, 'id' => $commissionRate->id]) }}" method="post" class="js-validation-material">
                    @csrf
                    <input type="hidden" name="redirect_to" value="{{ old('redirect_to', url()->previous()) }}">
                    <input type="hidden" name="settlement_company_id" value="{{ $settlementCompanyId }}">
                    <input type="hidden" id="id" name="id" value="{{ $commissionRate->id }}">
                    <label for="appServiceCd" style="margin-bottom:10px">利用サービス<span class="badge bg-danger ml-2 text-white">必須</span></label>
                    <div class="form-group row" id="appServiceCd">
                        <div class="col-4">
                            <select class="form-control" id="app_cd" name="app_cd">
                                @foreach(config('code.appCd') as $key => $appCd)
                                    @php if(strlen($key) > 2) continue; @endphp
                                    <option value="{{ strtoupper($key) }}" @if(strtoupper($key) == old('app_cd', $commissionRate->app_cd)) selected @endif>{{ $appCd[strtoupper($key)] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <label for="condition_type" style="margin-bottom:10px">計上条件<span class="badge bg-danger ml-2 text-white">必須</span></label>
                    <div class="form-group row" id="condition_type">
                        <div class="col-4">
                            <select class="form-control" id="accounting_condition" name="accounting_condition">
                                @foreach(config('const.commissionRate.accounting_condition') as $key => $accountingCondition)
                                    <option value="{{ $key }}" @if($key == old('accounting_condition', $commissionRate->accounting_condition)) selected @endif>{{ $accountingCondition }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <label for="apply_term" style="margin-bottom:10px">適用期間<span class="badge bg-danger ml-2 text-white">必須</span></label>
                    <div class="form-group row" id="apply_term">
                        <div class="col-2">
                            <select class="form-control" id="apply_term_from_year" name="apply_term_from_year">
                                @for($i = 2021; $i <= 2099; $i++)
                                    <option value="{{$i}}" @if($i == old('apply_term_from_year', \Carbon\Carbon::parse($commissionRate->apply_term_from)->format("Y"))) selected @endif>{{$i}}</option>
                                @endfor
                            </select>
                        </div>
                        <span style="margin-top:5px">年</span>
                        <div class="col-2">
                            <select class="form-control" id="apply_term_from_month" name="apply_term_from_month">
                                @for($i = 1; $i <= 12; $i++)
                                    <option value="{{$i}}" @if($i == old('apply_term_from_month', \Carbon\Carbon::parse($commissionRate->apply_term_from)->format("m"))) selected @endif>{{sprintf('%02d', $i)}}</option>
                                @endfor
                            </select>
                        </div>
                        <span style="margin-top:5px">月 ～</span>
                        <div class="col-2">
                            <select class="form-control" id="apply_term_to_year" name="apply_term_to_year">
                                @for($i = 2021; $i <= 2099; $i++)
                                    <option value="{{$i}}" @if($i == old('apply_term_to_year', \Carbon\Carbon::parse($commissionRate->apply_term_to)->format("Y"))) selected @endif>{{$i}}</option>
                                @endfor
                            </select>
                        </div>
                        <span style="margin-top:5px">年</span>
                        <div class="col-2">
                            <select class="form-control" id="apply_term_to_month" name="apply_term_to_month">
                                @for($i = 1; $i <= 12; $i++)
                                    <option value="{{$i}}" @if($i == old('apply_term_to_month', \Carbon\Carbon::parse($commissionRate->apply_term_to)->format("m"))) selected @endif>{{sprintf('%02d', $i)}}</option>
                                @endfor
                            </select>
                        </div>
                        <span style="margin-top:5px">月</span>
                    </div>
                    <label for="rate" style="margin-bottom:10px">販売手数料<span class="badge bg-danger ml-2 text-white">必須</span></label>
                    <div class="form-group row" id="rate">
                        <div class="col-3">
                            <input type="text" class="form-control"  id="fee" name="fee" value="{{old('fee', $commissionRate->fee)}}" required>
                        </div>
                        <span id="fee_value" style="margin-top:5px">%</span>
                    </div>
                    <div class="form-group" id="only_seat">
                        <div class="form-material" style="padding-top: 35px">
                            <label for="only_seat" style="margin-bottom:10px;">席のみ<span class="badge bg-danger ml-2 text-white">必須</span></label>
                            @foreach($onlySeats as $key => $value)
                                <div class="custom-control custom-radio custom-control-inline">
                                    <input class="custom-control-input" type="radio"
                                           name="only_seat" id="only_seat{{ $key }}" value="{{ $key }}"
                                        {{ $key === (int)old('only_seat', $commissionRate->only_seat) ? 'checked' : '' }}
                                    >
                                    <label class="custom-control-label" for="only_seat{{ $key }}" >{{ $value }}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <div class="text-right">
                                <label class="css-control css-control css-control-primary css-switch">
                                    @php
                                        $check = $commissionRate->published ? true : false;
                                        if (!empty(old('redirect_to'))) {
                                            if (old('published') !== '1') {
                                                $check = false;
                                            } else {
                                                $check = true;
                                            }
                                        }
                                    @endphp
                                    <input type="checkbox" class="css-control-input" id="published" name="published" value="1" @if($check) checked @endif>
                                    <span class="css-control-indicator"></span> 公開する
                                </label>
                            </div>
                            <label for="published">公開/非公開</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="text-right">
                            <button type="submit" class="btn btn-alt-primary">更新</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <button type="button" class="btn btn-secondary" onclick="location.href='{{ old('redirect_to', route('admin.commissionRate', ['settlementCompanyId' => $settlementCompanyId])) }}'">戻る</button>

    </div>
    <!-- END Content -->
    @include('admin.Layouts.js_files')

    <script src="{{ asset('vendor/admin/assets/js/commissionRate.js').'?'.time() }}"></script>
@endsection

@include('admin.Layouts.footer')
