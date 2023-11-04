@extends('admin.Layouts.base')
@include('admin.Layouts.head')

@include('admin.Layouts.side_overlay')
@include('admin.Layouts.sidebar')
@include('admin.Layouts.page_header')

@section('css')
<link href="{{ asset('css/custom-select-box.css') }}" rel="stylesheet">
<style>
    /* pc 既存の表示への影響をしないよう */
    @media screen and (min-width: 961px) {

        table.item-list-pc,
        div.item-list-pc {
            display: block;
        }

        table.item-list-sp,
        div.item-list-sp {
            display: none;
        }

        .form-material>div .form-control {
            padding-left: 0;
            padding-right: 0;
            border-color: transparent;
            border-radius: 0;
            background-color: transparent;
            box-shadow: 0 1px 0 #d4dae3;
            transition: box-shadow .3s ease-out;
        }

        .modal-dialog {
            width: 500px !important;
        }

        .content-heading {
            border-bottom: 0;
        }

    }

    /* sp 表示設定 */
    @media screen and (max-width: 960px) {

        table.item-list-pc,
        div.item-list-pc {
            display: none;
        }

        table.item-list-sp,
        div.item-list-sp {
            display: block;
        }

        .block-content {
            padding: 20px 0px 1px !important;
        }

        main#main-container {
            min-height: initial !important;
            min-height: auto !important;
        }

        .checkbox-weekday {
            margin-top: 8px !important;
            margin-right: 24px !important;
        }

    }
</style>
@endsection
@section('content')
@include('admin.Layouts.flash_message')
<!-- Content -->
<div class="content pb-3 item-list-pc">
    <!-- Validation Message -->
    <span id="result"></span>
    <!-- Default Table Style -->
    <div class="d-flex col-md-9 justify-content-between">
        <h2 class="content-heading">{{ $store->name }} キャンセル料編集</h2>
    </div>
    <!-- Floating Labels -->
    <form method="post" action="{{ route('admin.store.cancelFee.edit', ['cancel_fee_id' => $cancelFee->id]) }}">
        @csrf
        <div class="block col-md-9">
            <div class="block-content" style="padding-bottom: 20px;">
                <input type="hidden" name="store_id" value="{{ $store->id }}">
                <input type="hidden" name="store_name" value="{{ $store->name }}">
                <input type="hidden" name="store_code" value="{{ $store->code }}">
                <input type="hidden" name="redirect_to" value="{{ old('redirect_to', url()->previous()) }}">
                <!-- Start Form -->
                <div>
                    <div class="form-group">
                        <div class="form-material col-6 col-md-4 pl-0">
                            <label for="app_cd">利用サービス<span class="badge bg-danger ml-2 text-white">必須</span></label>
                            <select class="form-control" name="app_cd">
                                @foreach($storeCancelFeeConst['app_cd'] as $key => $value)
                                <option value="{{ $key }}" @if($key==old('app_cd', $cancelFee->app_cd)) selected @endif>
                                    {{ $value }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <label for="apply_term" style="margin-bottom:10px"> 適用期間<span class="badge bg-danger ml-2 text-white">必須</span></label>
                    <div class="form-group row" id="date">
                        <div class="col-3">
                            <input type="text" class="form-control" id="apply_term_from" name="apply_term_from" value="{{old('apply_term_from', $cancelFee->apply_term_from->format('Y/m/d'))}}" autocomplete="off" required>
                        </div>
                        <span style="margin-top:5px">～</span>
                        <div class="col-3">
                            <input type="text" class="form-control" id="apply_term_to" name="apply_term_to" value="{{old('apply_term_to', $cancelFee->apply_term_to->format('Y/m/d'))}}" autocomplete="off" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material col-6 col-md-4 pl-0">
                            <label for="visit">来店前/来店後<span class="badge bg-danger ml-2 text-white">必須</span></label>
                            <select class="form-control" name="visit" id="visit">
                                @foreach($storeCancelFeeConst['visit'] as $key => $value)
                                <option value="{{ $key }}" @if($key==old('visit', $cancelFee->visit)) selected @endif>
                                    {{ $value }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="visit-delete" @if ($cancelFee->visit === config('code.cancelPolicy.visit.after')) style="display: none;" @endif>
                        <div class="form-group">
                            <div class="form-material col-6 col-md-4 pl-0">
                                <label for="cancel_limit_unit">期限単位<span class="badge bg-danger ml-2 text-white">必須</span></label>
                                <select class="form-control" name="cancel_limit_unit" id="cancel_limit_unit">
                                    @foreach($storeCancelFeeConst['cancel_limit_unit'] as $key => $value)
                                    <option value="{{ $key }}" @if($key==old('cancel_limit_unit', $cancelFee->cancel_limit_unit)) selected @endif>
                                        {{ $value }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="visit-delete" @if ($cancelFee->visit === config('code.cancelPolicy.visit.after')) style="display: none;" @endif>
                        <label for="cancel_limit" style="margin-bottom:10px">期限<span class="badge bg-danger ml-2 text-white">必須</span></label>
                        <div class="form-group row" id="cancel_limit">
                            <div class="col-3">
                                <input type="text" class="form-control" id="cancel_limit" name="cancel_limit" value="{{old('cancel_limit', $cancelFee->cancel_limit)}}" required>
                            </div>
                            <span id="cancel_limit_value" style="margin-top:5px">日</span>
                        </div>
                    </div>
                    <div class="visit-delete">
                        @if ($cancelFee->visit !== config('code.cancelPolicy.visit.after'))
                        <div class="form-group">
                            <div class="form-material col-6 col-md-4 pl-0">
                                <label for="cancel_fee_unit">計上単位<span class="badge bg-danger ml-2 text-white">必須</span></label>
                                <select class="form-control" name="cancel_fee_unit" id="cancel_fee_unit">
                                    <option value="">選択してください</option>
                                    @foreach($storeCancelFeeConst['cancel_fee_unit'] as $key => $value)
                                    <option value="{{ $key }}" @if($key==old('cancel_fee_unit', $cancelFee->cancel_fee_unit)) selected @endif>
                                        {{ $value }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        @endif
                    </div>
                    <label for="cancel_fee" style="margin-bottom:10px">キャンセル料<span class="badge bg-danger ml-2 text-white">必須</span></label>
                    <div class="form-group row" id="cancel_fee">
                        <div class="col-3">
                            <input type="text" class="form-control" id="cancel_fee_input" name="cancel_fee" value="{{old('cancel_fee', $cancelFee->cancel_fee)}}" required @if ($cancelFee->visit === config('code.cancelPolicy.visit.after')) readonly @endif>
                        </div>
                        <span id="cancel_fee_value" style="margin-top:5px">%</span>
                    </div>
                    <div class="form-group row">
                        <div class="col-3" id="fraction_unit">
                            <label for="fraction_unit" style="margin-bottom:10px">端数処理<span class="badge bg-danger ml-2 text-white">必須</span></label>
                            <select class="form-control" id="fraction_unit" name="fraction_unit">
                                @foreach($storeCancelFeeConst['fraction_unit'] as $key => $value)
                                <option value="{{ $key }}" @if($key==old('fraction_unit', $cancelFee->fraction_unit)) selected @endif>
                                    {{ $value }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <span class="col-1" style="margin-top:35px">単位</span>
                        <div class="form-material col-6 col-md-4 pl-0">
                            <label for="fraction_round">端数処理(round)<span class="badge bg-danger ml-2 text-white">必須</span></label>
                            <select class="form-control" name="fraction_round">
                                @foreach($storeCancelFeeConst['fraction_round'] as $key => $value)
                                <option value="{{ $key }}" @if($key==old('fraction_round', $cancelFee->fraction_round)) selected @endif>
                                    {{ $value }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <label for="cancel_fee_max" style="margin-bottom:10px">最高額</label>
                    <div class="form-group row" id="cancel_fee_max">
                        <div class="col-3">
                            <input type="text" class="form-control" id="cancel_fee_max" name="cancel_fee_max" value="{{old('cancel_fee_max', $cancelFee->cancel_fee_max)}}">
                        </div>
                        <span style="margin-top:5px">円</span>
                    </div>
                    <label for="cancel_fee_min" style="margin-bottom:10px">最低額</label>
                    <div class="form-group row" id="cancel_fee_min">
                        <div class="col-3">
                            <input type="text" class="form-control" id="cancel_fee_min" name="cancel_fee_min" value="{{old('cancel_fee_min', $cancelFee->cancel_fee_min)}}">
                        </div>
                        <span style="margin-top:5px">円</span>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <div class="text-right">
                                <label class="css-control css-control css-control-primary css-switch">
                                    @php
                                    $check = $cancelFee->published ? true : false;
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
                </div>
            </div>
        </div>

        <div class="col-md-9 d-flex justify-content-between">
            <div>
                <button type="button" class="btn btn-secondary" onclick="location.href='{{ route('admin.store.cancelFee',['id' => $store->id]) }}'">戻る</button>
            </div>
            <div style="padding-right: 20px;">
                <button type="submit" class="btn btn-alt-primary" value="add" id="save">更新</button>
            </div>
        </div>
    </form>
</div>
<div class="content pb-3 item-list-sp">
    <!-- Validation Message -->
    <span id="result"></span>
    <!-- Default Table Style -->
    <div class="d-flex col-md-9 justify-content-between">
        <h2 class="content-heading">{{ $store->name }} キャンセル料編集</h2>
    </div>
    <!-- Floating Labels -->
    <form method="post" action="{{ route('admin.store.cancelFee.edit', ['cancel_fee_id' => $cancelFee->id]) }}">
        @csrf
        <div class="block col-md-9">
            <div class="block-content" style="padding-bottom: 20px;">
                <input type="hidden" name="store_id" value="{{ $store->id }}">
                <input type="hidden" name="store_name" value="{{ $store->name }}">
                <input type="hidden" name="store_code" value="{{ $store->code }}">
                <input type="hidden" name="redirect_to" value="{{ old('redirect_to', url()->previous()) }}">
                <!-- Start Form -->
                <div>
                    <div class="form-group">
                        <div class="form-material col-6 col-md-6 pl-0">
                            <label for="app_cd">利用サービス<span class="badge bg-danger ml-2 text-white">必須</span></label>
                            <select class="form-control" name="app_cd">
                                @foreach($storeCancelFeeConst['app_cd'] as $key => $value)
                                <option value="{{ $key }}" @if($key==old('app_cd', $cancelFee->app_cd)) selected @endif>
                                    {{ $value }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <label for="apply_term" style="margin-bottom:10px"> 適用期間<span class="badge bg-danger ml-2 text-white">必須</span></label>
                    <div class="form-group row" id="date">
                        <div class="col-5">
                            <input type="text" class="form-control" style="text-align:left;padding-left:4px;padding-right:4px;" id="apply_term_from_sp" name="apply_term_from" value="{{old('apply_term_from', $cancelFee->apply_term_from->format('Y/m/d'))}}" autocomplete="off" required>
                        </div>
                        <span style="margin-top:5px">～</span>
                        <div class="col-5">
                            <input type="text" class="form-control" style="text-align:left;padding-left:4px;padding-right:4px;" id="apply_term_to_sp" name="apply_term_to" value="{{old('apply_term_to', $cancelFee->apply_term_to->format('Y/m/d'))}}" autocomplete="off" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material col-6 col-md-6 pl-0">
                            <label for="visit">来店前/来店後<span class="badge bg-danger ml-2 text-white">必須</span></label>
                            <select class="form-control" name="visit" id="visit">
                                @foreach($storeCancelFeeConst['visit'] as $key => $value)
                                <option value="{{ $key }}" @if($key==old('visit', $cancelFee->visit)) selected @endif>
                                    {{ $value }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="visit-delete" @if ($cancelFee->visit === config('code.cancelPolicy.visit.after')) style="display: none;" @endif>
                        <div class="form-group">
                            <div class="form-material col-6 col-md-4 pl-0">
                                <label for="cancel_limit_unit">期限単位<span class="badge bg-danger ml-2 text-white">必須</span></label>
                                <select class="form-control" name="cancel_limit_unit" id="cancel_limit_unit">
                                    @foreach($storeCancelFeeConst['cancel_limit_unit'] as $key => $value)
                                    <option value="{{ $key }}" @if($key==old('cancel_limit_unit', $cancelFee->cancel_limit_unit)) selected @endif>
                                        {{ $value }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="visit-delete" @if ($cancelFee->visit === config('code.cancelPolicy.visit.after')) style="display: none;" @endif>
                        <label for="cancel_limit" style="margin-bottom:10px">期限<span class="badge bg-danger ml-2 text-white">必須</span></label>
                        <div class="form-group row" id="cancel_limit">
                            <div class="col-5">
                                <input type="text" class="form-control" id="cancel_limit" name="cancel_limit" value="{{old('cancel_limit', $cancelFee->cancel_limit)}}" required>
                            </div>
                            <span id="cancel_limit_value" style="margin-top:5px">日</span>
                        </div>
                    </div>
                    <div class="visit-delete">
                        @if ($cancelFee->visit !== config('code.cancelPolicy.visit.after'))
                        <div class="form-group">
                            <div class="form-material col-6 col-md-4 pl-0">
                                <label for="cancel_fee_unit">計上単位<span class="badge bg-danger ml-2 text-white">必須</span></label>
                                <select class="form-control" name="cancel_fee_unit" id="cancel_fee_unit">
                                    <option value="">選択してください</option>
                                    @foreach($storeCancelFeeConst['cancel_fee_unit'] as $key => $value)
                                    <option value="{{ $key }}" @if($key==old('cancel_fee_unit', $cancelFee->cancel_fee_unit)) selected @endif>
                                        {{ $value }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        @endif
                    </div>
                    <label for="cancel_fee" style="margin-bottom:10px">キャンセル料<span class="badge bg-danger ml-2 text-white">必須</span></label>
                    <div class="form-group row" id="cancel_fee">
                        <div class="col-5">
                            <input type="text" class="form-control" id="cancel_fee_input" name="cancel_fee" value="{{old('cancel_fee', $cancelFee->cancel_fee)}}" required @if ($cancelFee->visit === config('code.cancelPolicy.visit.after')) readonly @endif>
                        </div>
                        <span id="cancel_fee_value" style="margin-top:5px">%</span>
                    </div>
                    <label for="fraction_unit" style="margin-bottom:10px">端数処理<span class="badge bg-danger ml-2 text-white">必須</span></label>
                    <div class="form-group row">
                        <div class="col-5 col-md-4 pt-0" id="fraction_unit">
                            <select class="form-control" id="fraction_unit" name="fraction_unit">
                                @foreach($storeCancelFeeConst['fraction_unit'] as $key => $value)
                                <option value="{{ $key }}" @if($key==old('fraction_unit', $cancelFee->fraction_unit)) selected @endif>
                                    {{ $value }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <label for="fraction_round" class="col-2 col-md-2 col-form-label pl-0" style="font-weight:normal;">単位</label>
                    </div>
                    <label for="fraction_round">端数処理(round)<span class="badge bg-danger ml-2 text-white">必須</span></label>
                    <div class="form-group row">
                        <div class="form-material col-6 col-md-4 pt-0">
                            <select class="form-control" name="fraction_round" id="fraction_round">
                                @foreach($storeCancelFeeConst['fraction_round'] as $key => $value)
                                <option value="{{ $key }}" @if($key==old('fraction_round', $cancelFee->fraction_round)) selected @endif>
                                    {{ $value }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <label for="cancel_fee_max" style="margin-bottom:10px">最高額</label>
                    <div class="form-group row" id="cancel_fee_max">
                        <div class="col-5">
                            <input type="text" class="form-control" id="cancel_fee_max" name="cancel_fee_max" value="{{old('cancel_fee_max', $cancelFee->cancel_fee_max)}}">
                        </div>
                        <span style="margin-top:5px">円</span>
                    </div>
                    <label for="cancel_fee_min" style="margin-bottom:10px">最低額</label>
                    <div class="form-group row" id="cancel_fee_min">
                        <div class="col-5">
                            <input type="text" class="form-control" id="cancel_fee_min" name="cancel_fee_min" value="{{old('cancel_fee_min', $cancelFee->cancel_fee_min)}}">
                        </div>
                        <span style="margin-top:5px">円</span>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <div class="text-right">
                                <label class="css-control css-control css-control-primary css-switch">
                                    @php
                                    $check = $cancelFee->published ? true : false;
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
                </div>
            </div>
        </div>

        <div class="col-md-9 d-flex justify-content-between">
            <div>
                <button type="button" class="btn btn-secondary" onclick="location.href='{{ route('admin.store.cancelFee',['id' => $store->id]) }}'">戻る</button>
            </div>
            <div style="padding-right: 20px;">
                <button type="submit" class="btn btn-alt-primary" value="add" id="save">更新</button>
            </div>
        </div>
    </form>
</div>
<!-- END Content -->
@include('admin.Layouts.js_files')
<script src="{{ asset('vendor/admin/assets/js/cancelFee.js').'?'.time() }}"></script>

@endsection

@include('admin.Layouts.footer')

@section('js')
<script src="{{ asset('vendor/admin/assets/js/cancelFeeVisit.js').'?'.time() }}"></script>
<script>

$(document).ready(function() {
        update_ids();
    });

    /* ID被りを排除 */
    function update_ids() {
        if ($('div.item-list-pc').css('display') == 'none') {
            $('div.item-list-pc').find('*').each(function(i, e) {
                if ($(this).attr('id')) {
                    // console.log($(this).attr('id'));
                    $(this).attr('id', $(this).attr('id').split('_--_')[0] + '_--_' + i);
                }
            });
            $('div.item-list-sp').find('*').each(function(i, e) {
                if ($(this).attr('id')) {
                    // console.log($(this).attr('id'));
                    $(this).attr('id', $(this).attr('id').split('_--_')[0]);
                }
            });
        } else {
            $('div.item-list-pc').find('*').each(function(i, e) {
                if ($(this).attr('id')) {
                    // console.log($(this).attr('id'));
                    $(this).attr('id', $(this).attr('id').split('_--_')[0]);
                }
            });
            $('div.item-list-sp').find('*').each(function(i, e) {
                if ($(this).attr('id')) {
                    // console.log($(this).attr('id'));
                    $(this).attr('id', $(this).attr('id').split('_--_')[0] + '_--_' + i);
                }
            });
        }
    }
    $(window).resize(function() {
        update_ids();
    });
</script>
@endsection
