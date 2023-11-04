@extends('admin.Layouts.base')
@include('admin.Layouts.head')

@include('admin.Layouts.side_overlay')
@include('admin.Layouts.sidebar')
@include('admin.Layouts.page_header')

@section('css')
<style>
    /* pc 既存の表示への影響をしないよう */
    @media screen and (min-width: 961px) {

        .form-material>div .form-control,
        #salesLunchTime .form-control,
        #salesDinnerTime .form-control {
            padding-left: 0;
            padding-right: 0;
            border-color: transparent;
            border-radius: 0;
            background-color: transparent;
            box-shadow: 0 1px 0 #d4dae3;
            transition: box-shadow .3s ease-out;
        }

        .hide {
            display: none;
        }

        /*
        .block-content {
            padding: 20px 0px 1px;
        } */
    }

    /* sp 表示設定 */
    @media screen and (max-width: 960px) {
        .content {
            padding-left: 0 !important;
            padding-right: 0 !important;
        }

        .content-heading {
            margin-left: 16px;
        }

        .block-content {
            padding: 20px 0px 1px;
        }

        .form-material>.form-control.sales_lunch_time_from,
        .form-material>.form-control.sales_lunch_time_to,
        .form-material>.form-control.sales_dinner_time_from,
        .form-material>.form-control.sales_dinner_time_to {
            margin-top: -26px !important;
        }
        .checkbox-weekday {
            margin-top: 8px !important;
            margin-right: 16px !important;
        }
    }
</style>
@endsection

@section('content')
<!-- Content -->
<div class="content">
    @include('admin.Layouts.flash_message')

    <!-- Default Table Style -->
    <h2 class="content-heading">メニュー編集</h2>
    <!-- Floating Labels -->
    <div class="block col-md-9">
        <div class="block-content">
            <form action="{{ route('admin.menu.edit', ['id' => $menu->id]) }}" method="post">
                @csrf
                <input type="hidden" name="redirect_to" value="{{ old('redirect_to', url()->previous()) }}">
                <div class="form-group">
                    <div class="form-material">
                        <input type="text" class="form-control" id="menu_name" name="menu_name" value="{{ old('menu_name', $menu->name) }}" required>
                        <label for="menu_name">メニュー名<span class="badge bg-danger ml-2 text-white">必須</span></label>
                    </div>
                </div>
                @can('inAndOutHouseGeneral-only')
                <div class="form-group">
                    <div class="form-material">
                        <label for="store_name">店舗名<span class="badge bg-danger ml-2 text-white">必須</span></label>
                        <select class="form-control" name="store_name">
                            <option value="">選択してください</option>
                            @foreach($stores as $store)
                            <option value="{{ $store->id }}" @if (old('store_name')) {{ old('store_name') == $store->id ? 'selected' : '' }} @else {{ $menu->store_id == $store->id ? 'selected' : '' }} @endif>{{ $store->id }}.{{ $store->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                @endcan
                <div class="form-group">
                    <div class="form-material">
                        <label for="app_cd">利用サービス<span class="badge bg-danger ml-2 text-white">必須</span></label>
                        <select class="form-control app_cd" id="app_cd" name="app_cd">
                            <option value="">選択してください</option>
                            @foreach($appCd as $code => $content)
                            @php if(strlen($code) > 2) continue; @endphp
                            <option value="{{ strtoupper($code) }}" {{ old('app_cd', $menu->app_cd) == strtoupper($code) ? 'selected' : '' }}>{{ $content[strtoupper($code)] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div id="number_of_orders_same_time" class="hide">
                    <label for="number_of_orders_same_time">同時間帯注文組数<span class="badge bg-danger ml-2 text-white">必須</span></label>
                    <div class="form-group row">
                        <div class="col-5 col-md-3">
                            <input type="text" class="form-control" name="number_of_orders_same_time" value="{{ old('number_of_orders_same_time', $menu->number_of_orders_same_time) }}">
                        </div>
                        <span style="margin-top:5px">組</span>
                    </div>
                </div>
                <div id="number_of_course" class="hide">
                    <label for="number_of_course">コース品数<span class="badge bg-danger ml-2 text-white">必須</span></label>
                    <div class="form-group row">
                        <div class="col-3 col-md-2">
                            <input type="text" class="form-control" name="number_of_course" value="{{ old('number_of_course', $menu->number_of_course) }}">
                        </div>
                        <span style="margin-top:5px">品</span>
                    </div>
                </div>
                <div class="form-group" id="free_drinks">
                    <div class="form-material">
                        <label for="free_drinks">飲み放題（有/無）<span class="badge bg-danger ml-2 text-white">必須</span></label>
                        @foreach($freeDrinks as $key => $value)
                        <div class="custom-control custom-radio custom-control-inline">
                            <input class="custom-control-input" type="radio" name="free_drinks" id="free_drinks{{ $key }}" value="{{ $key }}" {{ $key == old('free_drinks', $menu->free_drinks) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="free_drinks{{ $key }}">{{ $value }}</label>
                        </div>
                        @endforeach
                    </div>
                </div>
                <div id="provided_time" class="hide">
                    <label for="provided_time">
                        提供時間<span class="badge bg-danger ml-2 text-white">必須</span>
                        <small class="d-block text-secondary">&#x203B; 何分のプランか。席のみであれば何分利用できるか。</small>
                    </label>
                    <div class="form-group row">
                        <div class="col-3 col-md-2">
                            <input type="text" class="form-control" name="provided_time" value="{{ old('provided_time', $menu->provided_time) }}">
                        </div>
                        <span style="margin-top:5px">分</span>
                    </div>
                </div>
                <div id="available_number_of_lower_limit" class="hide">
                    <label for="available_number_of_lower_limit">利用可能下限人数</label>
                    <div class="form-group row">
                        <div class="col-3 col-md-2">
                            <input type="text" class="form-control" name="available_number_of_lower_limit" value="{{ old('available_number_of_lower_limit', $menu->available_number_of_lower_limit) }}">
                        </div>
                        <span style="margin-top:5px">人</span>
                    </div>
                </div>
                <div id="available_number_of_upper_limit" class="hide">
                    <label for="available_number_of_upper_limit">利用可能上限人数</label>
                    <div class="form-group row">
                        <div class="col-3 col-md-2">
                            <input type="text" class="form-control" name="available_number_of_upper_limit" value="{{ old('available_number_of_upper_limit', $menu->available_number_of_upper_limit) }}">
                        </div>
                        <span style="margin-top:5px">人</span>
                    </div>
                </div>
                <div class="form-group">
                    <div class="form-material">
                        <label for="menu_description">メニュー説明</label>
                        <textarea class="form-control" name="menu_description" rows="5">{{ old('menu_description', $menu->description) }}</textarea>
                    </div>
                </div>
                <div class="form-group" id="content_plan">
                    <div class="form-material">
                        <label for="plan">プラン内容</label>
                        <textarea class="form-control" name="plan" rows="2">{{ old('plan', $menu->plan) }}</textarea>
                    </div>
                </div>
                <div class="form-group" id="content_menu_notes">
                    <div class="form-material">
                        <label for="menu_notes">注意事項</label>
                        <textarea class="form-control" name="menu_notes" rows="3">{{ old('menu_notes', $menu->notes) }}</textarea>
                    </div>
                </div>
                <label for="sales_lunch_time" class="mb-0">販売時間ランチ</label>
                <div class="form-group row justify-content-evenly" id="salesLunchTime">
                    <div class="col form-material">
                        <input type="text" class="form-control sales_lunch_time_from" name="sales_lunch_start_time" value="{{ old('sales_lunch_start_time', $menu->sales_lunch_start_time ? \Carbon\Carbon::parse($menu->sales_lunch_start_time)->format("H:i") : '' )  }}" autocomplete="off">
                    </div>
                    <div class="col form-material text-center pt-2">
                        <span>～</span>
                    </div>
                    <div class="col form-material">
                        <input type="text" class="form-control sales_lunch_time_to" name="sales_lunch_end_time" value="{{ old('sales_lunch_end_time', $menu->sales_lunch_end_time ? \Carbon\Carbon::parse($menu->sales_lunch_end_time)->format("H:i") : '' ) }}" autocomplete="off">
                    </div>

                </div>
                <label for="sales_dinner_time" class="mb-0">販売時間ディナー</label>
                <div class="form-group row justify-content-evenly" id="salesDinnerTime">
                    <div class="col form-material">

                        <input type="text" class="form-control sales_dinner_time_from" name="sales_dinner_start_time" value="{{ old('sales_dinner_start_time', $menu->sales_dinner_start_time ? \Carbon\Carbon::parse($menu->sales_dinner_start_time)->format("H:i") : '' ) }}" autocomplete="off">
                    </div>
                    <div class="col form-material text-center pt-2">
                        <span>～</span>
                    </div>
                    <div class="col form-material">

                        <input type="text" class="form-control sales_dinner_time_to" name="sales_dinner_end_time" value="{{ old('sales_dinner_end_time', $menu->sales_dinner_end_time ? \Carbon\Carbon::parse($menu->sales_dinner_end_time)->format("H:i") : '' ) }}" autocomplete="off">
                    </div>
                </div>
                <div class="form-group" id="provided_day_of_week">
                    <div class="form-material">
                        <label for="provided_day_of_week">提供可能日</label>
                        @foreach($providedDayOfWeeks as $providedDayOfWeek => $value)
                        @php $has_provided_day = str_split($menu->provided_day_of_week, 1); @endphp
                        <div class="custom-control custom-checkbox custom-control-inline checkbox-weekday">
                            <input type="hidden" name="provided_day_of_week[{{ $loop->index }}]" value="0">
                            <input class="custom-control-input" type="checkbox" name="provided_day_of_week[{{ $loop->index }}]" id="provided_day_of_week{{ $loop->index }}" value="{{ $value }}" {{ old('provided_day_of_week.'.$loop->index, $has_provided_day[$loop->index]) === '1' ? 'checked' : '' }}>
                            <label class="custom-control-label" for="provided_day_of_week{{ $loop->index }}">{{ $providedDayOfWeek }}</label>
                        </div>
                        @endforeach
                    </div>

                </div>
                <div id="lower_orders_time" class="hide">
                    <label for="lower_orders_time_hour">
                        最低注文時間<small class="d-block text-secondary">&#x203B; 何時間何分前まで予約が可能か。</small>
                    </label>
                    <div class="form-group row">
                        <div class="col-3 col-md-2">
                            <input type="text" class="form-control" name="lower_orders_time_hour" id="lower_orders_time_hour" value="{{ old('lower_orders_time_hour', $menu->lower_orders_time_hour) }}">
                        </div>
                        <span style="margin-top:5px">時間</span>
                        <div class="col-3 col-md-2">
                            <input type="text" class="form-control" name="lower_orders_time_minute" id="lower_orders_time_minute" value="{{ old('lower_orders_time_minute', $menu->lower_orders_time_minute) }}">
                        </div>
                        <span style="margin-top:5px">分</span>
                    </div>
                </div>
                <div class="form-group">
                    <div class="form-material">
                        <label for="remarks">備考</label>
                        <textarea class="form-control" name="remarks" rows="5">{{ old('remarks', $menu->remarks) }}</textarea>
                    </div>
                </div>
                @if (Gate::check('inHouseGeneral-higher'))
                    <div class="form-group">
                        <div class="form-material">
                            <div class="text-right">
                                <label class="css-control css-control css-control-primary css-switch">
                                    @php
                                        $check = $menu->buffet_lp_published ? true : false;
                                        if (! empty(old('redirect_to'))) {
                                            if (old('buffet_lp_published') !== '1') {
                                                $check = false;
                                            } else {
                                                $check = true;
                                            }
                                        }
                                    @endphp
                                    <input type="checkbox" class="css-control-input" id="buffet_lp_published" name="buffet_lp_published" value="1" @if($check) checked @endif>
                                    <span class="css-control-indicator"></span> 表示する
                                </label>
                            </div>
                            <label for="buffet_lp_published">特集LP表示設定</label>
                        </div>
                    </div>
                    @else
                    <input type="hidden" name="buffet_lp_published" value="{{$menu->buffet_lp_published}}">
                    @endif
                    <div class="form-group">
                        <div class="form-material">
                            <div class="text-right">
                                <label class="css-control css-control css-control-primary css-switch">
                                    @php
                                        $check = $menu->published ? true : false;
                                        if (! empty(old('redirect_to'))) {
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
                        <button type="submit" class="btn btn-alt-primary" value="update">更新</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

<button type="button" class="btn btn-secondary" onclick="location.href='{{ route('admin.menu') }}'">戻る</button>

</div>
<!-- END Content -->
{{-- @include('admin.Layouts.js_files')--}}

{{-- <script src="{{ asset('vendor/codebase/assets/js/plugins/jquery-validation/jquery.validate.min.js') }}"></script>--}}
{{-- <script src="{{ asset('vendor/codebase/assets/js/pages/be_forms_validation.js') }}"></script>--}}
@endsection

@section('js')
<script src="{{ asset('vendor/admin/assets/js/menu.js').'?'.time() }}"></script>
@endsection

@include('admin.Layouts.footer')
