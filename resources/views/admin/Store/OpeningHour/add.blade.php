@extends('admin.Layouts.base')
@include('admin.Layouts.head')

@include('admin.Layouts.side_overlay')
@include('admin.Layouts.sidebar')
@include('admin.Layouts.page_header')

@section('css')
    <style>
        .form-material> div .form-control, #salesLunchTime .form-control, #salesDinnerTime .form-control {
            padding-left: 0;
            padding-right: 0;
            border-color: transparent;
            border-radius: 0;
            background-color: transparent;
            box-shadow: 0 1px 0 #d4dae3;
            transition: box-shadow .3s ease-out;
        }
        .modal-dialog { width: 500px!important; }
    </style>
@endsection

@section('content')
    <!-- Content -->
    <div class="content">
        <!-- Validation Message -->
        <span id="result"></span>

    <!-- Default Table Style -->
        <div class="block" style="background-color: #f0f2f5; margin-bottom: 0;">
            <div class=" col-md-9 block-header">
                <h2 class="content-heading" style="margin-bottom: 0; padding-top: 0;">{{ $store->name }} 営業時間設定</h2>
            </div>
        </div>
        <!-- Floating Labels -->
        <form id="add_opening_hour" method="post">
            @csrf
            <div class="block col-md-9">
                <div class="block-content" style="padding-bottom: 20px;">
                    <input type="hidden" name="store_id" value="{{ $store->id }}">
                    <input type="hidden" name="store_name" value="{{ $store->name }}">
                    <input type="hidden" name="redirect_to" value="{{ old('redirect_to', url()->previous()) }}">
                    <div>
                        <div class="form-group">
                            <div class="form-material col-6 col-md-4 pl-0">
                                <label for="opening_hour_cd">営業時間コード<span class="badge bg-danger ml-2 text-white">必須</span></label>
                                <select class="form-control" name="opening_hour_cd" style="margin-top: 18px;">
                                    <option value="">選択してください</option>
                                    @foreach($codes as $key => $value)
                                        <option value="{{ $key }}" {{ $key == old('opening_hour_cd') ? 'selected' : '' }}>
                                            {{ $value }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="form-material">
                                <label for="week">営業曜日<span class="badge bg-danger ml-2 text-white">必須</span></label>
                                @foreach($weeks as $key => $value)
                                    <div class="checkbox-weekday custom-control custom-checkbox custom-control-inline">
                                        <input type="hidden" name="week[{{ $loop->index }}]" value="0">
                                        <input class="custom-control-input" type="checkbox"
                                               name="week[{{ $loop->index }}]" value="{{ $value }}" id="week[{{ $loop->index }}]"
                                            {{is_array(old("week")) && isset(old("week")[$key]) && in_array("1", old("week"), true) ? 'checked="checked"' : ''}}
                                        >
                                        <label class="custom-control-label" for="week[{{ $loop->index }}]">{{ $key }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <label for="start_at" class="mb-0">営業開始時間<span class="badge bg-danger ml-2 text-white">必須</span></label>
                        <div class="form-group row" id="salesLunchTime">
                            <div class="col-4 col-md-2 narrow">
                                <input type="text" class="form-control start_at" name="start_at"
                                       value="{{ old('start_at') }}" autocomplete="off"
                                >
                            </div>
                        </div>
                        <label for="end_at" class="mb-0">営業終了時間<span class="badge bg-danger ml-2 text-white">必須</span></label>
                        <div class="form-group row" id="salesLunchTime">
                            <div class="col-4 col-md-2 narrow">
                                <input type="text" class="form-control end_at" name="end_at"
                                       value="{{ old('end_at')  }}" autocomplete="off"
                                >
                            </div>
                        </div>
                        <label for="last_order_time" class="mb-0">ラストオーダー時間<span class="badge bg-danger ml-2 text-white">必須</span></label>
                        <div class="form-group row" id="salesLunchTime">
                            <div class="col-4 col-md-2 narrow">
                                <input type="text" class="form-control last_order_time" name="last_order_time"
                                       value="{{ old('last_order_time')  }}" autocomplete="off"
                                >
                            </div>
                        </div>
                        <div class="text-danger">※ ラストオーダー時間は、営業開始時間と営業終了時間の間で設定してください</div>
                        <div class="text-danger">※ 24時で登録されたい方は、「23:59」を選択してください</div>
                    </div>
                </div>
            </div>

            <div class="col-md-9 d-flex justify-content-between">
                <div>
                    <button type="button" class="btn btn-secondary" onclick="location.href='{{ route('admin.store.open.edit', ['id' => $store->id]) }}'">戻る</button>
                </div>
                <div style="padding-right: 20px;">
                    <button type="submit" class="btn btn-alt-primary" id="save" value="save">保存</button>
                </div>
            </div>
        </form>
    </div>
    <!-- END Content -->

    @include('admin.Layouts.js_files')

@endsection

@include('admin.Layouts.footer')

@section('js')
    <script src="{{ asset('vendor/admin/assets/js/storeOpeningHour.js').'?'.time() }}"></script>
@endsection

