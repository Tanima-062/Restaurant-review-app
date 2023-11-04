@extends('admin.Layouts.base')
@include('admin.Layouts.head')

@include('admin.Layouts.side_overlay')
@include('admin.Layouts.sidebar')
@include('admin.Layouts.page_header')

@section('css')
<style>
    /* pc 既存の表示への影響をしないよう */
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
    .box-input, .add_form-group {box-shadow: 0 1px 0 #d4dae3;}

    /* sp 表示設定 */
    @media screen and (max-width: 960px) {
        .checkbox-weekday {
            margin-top: 8px !important;
            margin-right: 24px !important;
        }
    }
</style>
@endsection

@section('content')
    <!-- Content -->
    <div class="content">
    @include('admin.Layouts.flash_message')

    <!-- Default Table Style -->
        <div class="block" style="background-color: #f0f2f5; margin-bottom: 0;">
            <div class=" col-md-9 block-header">
                <h2 class="content-heading" style="margin-bottom: 0; padding-top: 0;">{{ $store->name }} 営業時間設定</h2>
            </div>
        </div>
        <!-- Floating Labels -->
        <form id="opening_hour_form" action="{{ route('admin.store.open.edit', ['id' => $store->id]) }}" method="post">
            @csrf
            <div class="block col-md-9">
                <div class="block-content" id="dynamicForm">
                        <input type="hidden" name="store_id" value="{{ $store->id }}">
                        <input type="hidden" name="store_name" value="{{ $store->name }}">
                        <input type="hidden" name="redirect_to" value="{{ old('redirect_to', url()->previous()) }}">
                        @if(!empty($storeOpeningHourExists))
                            <div>
                                @foreach($storeOpeningHours as $storeKey => $storeOpeningHour)
                                <div class="box-input mt-3">
                                    <input type="hidden" name="store[{{ $loop->index }}][opening_hour_id]" value="{{ $storeOpeningHour['id'] }}">
                                    <div class="form-group">
                                        <div class="form-material col-6 col-md-4 pl-0">
                                            <label for="opening_hour_cd">営業時間コード{{ $loop->iteration }}</label>
                                            <select class="form-control" name="store[{{ $loop->index }}][opening_hour_cd]">
                                                <option value="">選択してください</option>
                                                @foreach($codes as $key => $value)
                                                    <option value="{{ $key }}" {{ $key === old('store.'.$storeKey.'.opening_hour_cd', $storeOpeningHour['opening_hour_cd']) ? 'selected' : '' }}>
                                                        {{ $value }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="form-material">
                                            <label for="provided_day_of_week">営業曜日</label>
                                            @foreach($weeks as $key => $value)
                                                @php $hasWeek = str_split($storeOpeningHour->week, 1); @endphp
                                                <div class="checkbox-weekday custom-control custom-checkbox custom-control-inline">
                                                    <input type="hidden" name="store[{{ $storeKey }}][week][{{ $loop->index }}]" value="0">
                                                    <input class="custom-control-input" type="checkbox"
                                                           name="store[{{ $storeKey }}][week][{{ $loop->index }}]" value="{{ $value }}" id="store[{{ $storeKey }}][week][{{ $loop->index }}]"
                                                            {{ old('store.'.$storeKey.'.week.'.$loop->index, $hasWeek[$loop->index]) === '1' ? 'checked' : '' }}
                                                    >
                                                    <label class="custom-control-label" for="store[{{ $storeKey }}][week][{{ $loop->index }}]">{{ $key }}</label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    <label for="start_at" class="mb-0">営業開始時間</label>
                                    <div class="form-group row" id="salesLunchTime">
                                        <div class="col-4 col-md-2 narrow">
                                            <input type="text" class="form-control start_at" name="store[{{ $loop->index }}][start_at]"
                                                   value="{{ old('store.'.$loop->index.'.start_at', $storeOpeningHour->start_at ? \Carbon\Carbon::parse($storeOpeningHour->start_at)->format("H:i") : '' )  }}" autocomplete="off"
                                            >
                                        </div>
                                    </div>
                                    <label for="end_at" class="mb-0">営業終了時間</label>
                                    <div class="form-group row" id="salesLunchTime">
                                        <div class="col-4 col-md-2 narrow">
                                            <input type="text" class="form-control end_at" name="store[{{ $loop->index }}][end_at]"
                                                   value="{{ old('store.'.$loop->index.'.end_at', $storeOpeningHour->end_at ? \Carbon\Carbon::parse($storeOpeningHour->end_at)->format("H:i") : '' )  }}" autocomplete="off"
                                            >
                                        </div>
                                    </div>
                                    <label for="last_order_time" class="mb-0">ラストオーダー時間</label>
                                    <div class="form-group row" id="salesLunchTime">
                                        <div class="col-4 col-md-2 narrow">
                                            <input type="text" class="form-control last_order_time" name="store[{{ $loop->index }}][last_order_time]"
                                                   value="{{ old('store.'.$loop->index.'.last_order_time', $storeOpeningHour->last_order_time ? \Carbon\Carbon::parse($storeOpeningHour->last_order_time)->format("H:i") : '' )  }}" autocomplete="off"
                                            >
                                        </div>
                                    </div>
                                    <div class="text-danger">※ ラストオーダー時間は、営業開始時間と営業終了時間の間で設定してください</div>
                                    <div class="text-danger">※ 24時を選択されたい方は、「23:59」で登録してください</div>
                                    <div class="text-right" style="padding-bottom: 15px;">
                                        <button type="button" class="btn btn-sm btn-secondary btn-danger delete-confirm"
                                                data-id="{{ $storeOpeningHour['id'] }}" data-store_id="{{ $store->id }}"
                                                data-image_cd="{{ $storeOpeningHour['opening_hour_cd'] }}" data-toggle="tooltip" title="Delete">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>

                                    @php $i = ($loop->count) - 1; @endphp
                                </div>
                                @endforeach
                            </div>
                        @else
                            <div data-nodata=1>
                                <div class="form-group">
                                    営業時間設定の登録がありません。
                                </div>
                                @php $i = 0; @endphp
                            </div>
                        @endif

                        <!-- Start addForm -->
                        <div id="storeOpenHour_form" class="mb-3">
                        @php
                        /* バリデーションエラー後の入力内容を取得し表示する */
                        $addInputCount = 0;
                        @endphp
                        @if (!empty(old('store') ))
                            @foreach (old('store') as $addIndex => $store)
                                @if (old('store.'.$addIndex.'.opening_hour_id') === null)
                                <div class="add_form-group mt-3">
                                    <input type="hidden" name="store[{{$addIndex}}][opening_hour_id]" value="">
                                    <div class="form-group">
                                        <div class="form-material col-7 col-md-4 pl-0">
                                            <label for="opening_hour_cd">営業時間コード（追加）<span class="badge bg-danger ml-2 text-white">必須</span></label>
                                            <select class="form-control" name="store[{{$addIndex}}][opening_hour_cd]" style="margin-top: 18px;">
                                            <option value="">選択してください</option>
                                            @foreach($codes as $key => $value)
                                            <option value="{{ $key }}" {{ $key === old('store.'.$addIndex.'.opening_hour_cd') ? 'selected' : '' }}>{{ $value }}</option>
                                            @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="form-material">
                                            <label for="week">営業曜日<span class="badge bg-danger ml-2 text-white">必須</span></label>
                                            @foreach($weeks as $key => $value)
                                            <div class="checkbox-weekday custom-control custom-checkbox custom-control-inline">
                                                <input type="hidden" name="store[{{$addIndex}}][week][{{ $loop->index }}]" value="0">
                                                <input class="custom-control-input" type="checkbox"
                                                name="store[{{$addIndex}}][week][{{ $loop->index }}]" value="{{ $value }}" id="store[{{$addIndex}}][week][{{ $loop->index }}]"
                                                {{ old('store.'.$addIndex.'.week.'.$loop->index) === '1' ? 'checked' : '' }} >
                                                <label class="custom-control-label" for="store[{{$addIndex}}][week][{{ $loop->index }}]">{{ $key }}</label>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    <label for="start_at" class="mb-0">営業開始時間<span class="badge bg-danger ml-2 text-white">必須</span></label>
                                    <div class="form-group row" id="salesLunchTime">
                                        <div class="col-4 col-md-2 narrow">
                                            <input type="text" class="form-control start_at" name="store[{{$addIndex}}][start_at]"
                                            value="{{ old('store.'.$addIndex.'.start_at')  }}" autocomplete="off">
                                        </div>
                                    </div>
                                    <label for="end_at" class="mb-0">営業終了時間<span class="badge bg-danger ml-2 text-white">必須</span></label>
                                    <div class="form-group row" id="salesLunchTime">
                                        <div class="col-4 col-md-2 narrow">
                                            <input type="text" class="form-control end_at" name="store[{{$addIndex}}][end_at]"
                                            value="{{ old('store.'.$addIndex.'.end_at')  }}" autocomplete="off">
                                        </div>
                                    </div>

                                    <label for="last_order_time" class="mb-0">ラストオーダー時間<span class="badge bg-danger ml-2 text-white">必須</span></label>
                                    <div class="form-group row" id="salesLunchTime">
                                        <div class="col-4 col-md-2 narrow">
                                            <input type="text" class="form-control last_order_time" name="store[{{$addIndex}}][last_order_time]"
                                            value="{{ old('store.'.$addIndex.'.last_order_time')  }}" autocomplete="off">
                                        </div>
                                    </div>
                                    <div class="text-danger">※ ラストオーダー時間は、営業開始時間と営業終了時間の間で設定してください</div>
                                    <div class="text-danger">※ 24時を選択されたい方は、「23:59」で登録してください</div>
                                    @if (!($loop->count == 1 && empty($storeOpeningHourExists)))
                                        <div class="text-right" style="padding-bottom: 15px;">
                                        <button type="button" class="btn btn-sm btn-secondary remove-form" data-toggle="tooltip" title="Erase">
                                        <i class="fa fa-minus"></i></button>
                                        </div>
                                    @endif
                                </div>
                                @endif
                                @php
                                $addInputCount++;
                                @endphp
                            @endforeach
                        @endif
                        </div>
                        <!-- End Form -->

                        <div class="form-group">
                            <div class="text-right">
                                <button type="button" id="add" class="btn btn-sm btn-secondary" data-toggle="tooltip" title="Add">
                                    <i class="fa fa-plus"></i>
                                </button>
                            </div>
                        </div>
                </div>
            </div>

            <div class="col-md-9 d-flex justify-content-between">
                <div>
                    <button type="button" class="btn btn-secondary" onclick="location.href='{{ session('storeOpeningHourRedirectTo', route('admin.store')) }}'">戻る</button>
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
    <script>
        $(document).ready(function() {
            let count = {{ (!(empty($storeOpeningHourExists)) || $addInputCount > 0 )? count($storeOpeningHours) + $addInputCount: 0}};
            @if (empty($storeOpeningHourExists) && $addInputCount <= 0)
                $('button[value=save]').hide();
            @else
                $('[data-nodata=1]').hide()
            @endif

            function dynamic_field(number) {
                html =  '<div class="add_form-group mt-3">';
                html += '<input type="hidden" name="store['+number+'][opening_hour_id]" value="">';
                html += '<div class="form-group">';
                html += '<div class="form-material col-7 col-md-4 pl-0">';
                html += '<label for="opening_hour_cd">営業時間コード（追加）<span class="badge bg-danger ml-2 text-white">必須</span></label>';
                html += '<select class="form-control" name="store['+number+'][opening_hour_cd]" style="margin-top: 18px;">';
                html += '<option value="">選択してください</option>';
                html += '@foreach($codes as $key => $value)';
                html += '<option value="{{ $key }}">{{ $value }}</option>';
                html += '@endforeach';
                html += '</select>';
                html += '</div></div>';
                html += '<div class="form-group">';
                html += '<div class="form-material">';
                html += '<label for="week">営業曜日<span class="badge bg-danger ml-2 text-white">必須</span></label>';
                html += '@foreach($weeks as $key => $value)';
                html += '<div class="checkbox-weekday custom-control custom-checkbox custom-control-inline">';
                html += '<input type="hidden" name="store['+number+'][week][{{ $loop->index }}]" value="0">';
                html += '<input class="custom-control-input" type="checkbox"';
                html += '   name="store['+number+'][week][{{ $loop->index }}]" value="{{ $value }}" id="store['+number+'][week][{{ $loop->index }}]">';
                html += '<label class="custom-control-label" for="store['+number+'][week][{{ $loop->index }}]">{{ $key }}</label>';
                html += '</div>';
                html += '@endforeach';
                html += '</div></div>';
                html += '<label for="start_at" class="mb-0">営業開始時間<span class="badge bg-danger ml-2 text-white">必須</span></label>';
                html += '<div class="form-group row" id="salesLunchTime">';
                html += '<div class="col-4 col-md-2 narrow">';
                html += '<input type="text" class="form-control start_at" name="store['+number+'][start_at]" value="" autocomplete="off">';
                html += '</div></div>';
                html += '<label for="end_at" class="mb-0">営業終了時間<span class="badge bg-danger ml-2 text-white">必須</span></label>';
                html += '<div class="form-group row" id="salesLunchTime">';
                html += '<div class="col-4 col-md-2 narrow">';
                html += '<input type="text" class="form-control end_at" name="store['+number+'][end_at]" value="" autocomplete="off">';
                html += '</div></div>';

                html += '<label for="last_order_time" class="mb-0">ラストオーダー時間<span class="badge bg-danger ml-2 text-white">必須</span></label>';
                html += '<div class="form-group row" id="salesLunchTime">';
                html += '<div class="col-4 col-md-2 narrow">';
                html += '<input type="text" class="form-control last_order_time" name="store['+number+'][last_order_time]" value="" autocomplete="off">';
                html += '</div></div>';
                html += '<div class="text-danger">※ ラストオーダー時間は、営業開始時間と営業終了時間の間で設定してください</div>';
                html += '<div class="text-danger">※ 24時を選択されたい方は、「23:59」で登録してください</div>';
                if (number > 1) {
                    html += '<div class="text-right" style="padding-bottom: 15px;">';
                    html += '<button type="button" class="btn btn-sm btn-secondary remove-form" data-toggle="tooltip" title="Erase">';
                    html += '<i class="fa fa-minus"></i></button>';
                    html += '</div>';
                }
                html += '</div>';

                if (number > 1) {
                    $('#storeOpenHour_form').append(html);
                } else {
                    $('#storeOpenHour_form').html(html);
                    $('[data-nodata=1]').hide();
                    $('button[value=save]').show();
                }
            }

            $(document).on('click', '#add', function() {
                count++;
                dynamic_field(count);
            });

            $(document).on('click', '.remove-form', function() {
                $(this).parents('.add_form-group').remove();
            });
        });
    </script>
@endsection
