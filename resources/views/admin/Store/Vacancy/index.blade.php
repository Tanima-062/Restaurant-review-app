@extends('admin.Layouts.base')
@include('admin.Layouts.head')

@include('admin.Layouts.side_overlay')
@include('admin.Layouts.sidebar')
@include('admin.Layouts.page_header')

@section('css')
{{-- Page JS Plugins CSS --}}
<link rel="stylesheet" id="css-main" href="{{ asset('vendor/codebase/assets/js/plugins/fullcalendar/fullcalendar.min.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/codebase/assets/css/codebase.min.css') }}">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" />
<!--<link href="{{ asset('css/store/vacancy/index.css') }}" rel="stylesheet" type="text/css">-->
<style>
    .fc-sun {
        color: red;
    }

    /* 日曜日 */
    .fc-sat {
        color: blue;
    }

    /* 土曜日 */
    .calendarParam {
        margin-bottom: 48px !important;
        ;
    }

    .cautionDesc {
        color: red;
        margin-top: 24px;
        margin-bottom: 24px !important;
        ;
    }

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
<!-- Page Content -->

<div class="content item-list-pc" id="storeStock">
    @include('admin.Layouts.flash_message')

    <input type="hidden" class="store_id" data-url="{{ $store->id }}">
    <!-- Validation Message -->
    <span id="result"></span>

    <div class="block" style="background-color: #f0f2f5; margin-bottom: 0;">
        <div class="col-md-9 block-header">
            <h2 class="content-heading" style="margin-bottom: 0; padding-top: 0;">{{ $store->name }} 空席設定</h2>
        </div>
    </div>

    <!-- For more info and examples you can check out https://fullcalendar.io/ -->
    <div class="block calendarParam">
        <div class="block-content">

            <div class="row">
                <form action="{{ route('admin.store.vacancy.editAllForm',['id'=>$store->id]) }}" method="get" id="editAllForm">
                    @csrf
                    <div class="col-12">
                        <label for="apply_term" style="margin-bottom:10px"> 適用期間<span class="badge bg-danger ml-2 text-white">必須</span></label>
                        <label for="provided_day_of_week" style="margin-bottom:10px;margin-left: 280px;">営業曜日<span class="badge bg-danger ml-2 text-white">必須</span></label>
                        <div style="margin-left: 1px;" class="form-group row col-12" id="date">
                            <div class="col-2">
                                <input type="text" class="form-control" id="start" name="start" value="{{old('start')}}" autocomplete="off" required>
                            </div>
                            <span style="margin-top:5px">～</span>
                            <div class="col-2">
                                <input type="text" class="form-control" id="end" name="end" value="{{old('end')}}" autocomplete="off" required>
                            </div>
                            <div class="form-group col-6">

                                @foreach($weeks as $key => $value)

                                <div class="custom-control custom-checkbox custom-control-inline">
                                    <input type="hidden" name="week[{{ $loop->index }}]" value="0">
                                    <input class="custom-control-input" type="checkbox" name="week[{{ $loop->index }}]" value="{{ $value }}" id="week[{{ $loop->index }}]" {{ old('week.'.$loop->index, '') === '1' ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="week[{{ $loop->index }}]">{{ $key }}</label>
                                </div>
                                @endforeach
                            </div>
                            <div class="col-1">
                                <button type="submit" class="btn btn-alt-primary">一括登録へ進む</button>
                            </div>
                            <?php
                            /*<div class="col-2">
                                        <button type="submit" id="copy" class="btn btn-alt-primary">1週目をコピー</button>
                                    </div>*/
                            ?>
                        </div>

                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
<div class="content item-list-sp" id="storeStock">
    @include('admin.Layouts.flash_message')

    <input type="hidden" class="store_id" data-url="{{ $store->id }}">
    <!-- Validation Message -->
    <span id="result"></span>

    <div class="block" style="background-color: #f0f2f5; margin-bottom: 0;">
        <div class="col-md-9 block-header">
            <h2 class="content-heading" style="margin-bottom: 0; padding-top: 0;">{{ $store->name }} 空席設定</h2>
        </div>
    </div>

    <!-- For more info and examples you can check out https://fullcalendar.io/ -->
    <div class="block calendarParam">
        <div class="block-content">

            <form action="{{ route('admin.store.vacancy.editAllForm',['id'=>$store->id]) }}" method="get" id="editAllForm">
                @csrf
                <div class="row">
                    <div class="col-12">
                        <label for="apply_term" style="margin-bottom:10px"> 適用期間<span class="badge bg-danger ml-2 text-white">必須</span></label>
                    </div>
                </div>
                <div class="row">
                    <div style="margin-left: 1px;" class="form-group row text-left" id="date">
                        <div class="col-5">
                            <input type="text" class="form-control" id="start_sp" style="padding:8px 2px !important;" name="start" value="{{old('start')}}" autocomplete="off" required>
                        </div>
                        <span style="margin-top:5px">～</span>
                        <div class="col-5">
                            <input type="text" class="form-control" id="end" style="padding:8px 2px !important;" name="end" value="{{old('end')}}" autocomplete="off" required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <label for="provided_day_of_week" style="margin-bottom:10px;">営業曜日<span class="badge bg-danger ml-2 text-white">必須</span></label>
                        <div class="form-group col-12">
                            @foreach($weeks as $key => $value)
                            <div class="checkbox-weekday custom-control custom-checkbox custom-control-inline">
                                <input type="hidden" name="week[{{ $loop->index }}]" value="0">
                                <input class="custom-control-input" type="checkbox" name="week[{{ $loop->index }}]" value="{{ $value }}" id="week[{{ $loop->index }}]" {{ old('week.'.$loop->index, '') === '1' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="week[{{ $loop->index }}]">{{ $key }}</label>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="row">

                    <div class="col-12 text-right pb-5">
                        <button type="submit" class="btn btn-alt-primary">一括登録へ進む</button>
                    </div>
                    <?php
                    /*<div class="col-2">
                                        <button type="submit" id="copy" class="btn btn-alt-primary">1週目をコピー</button>
                                    </div>*/
                    ?>
                </div>

        </div>
        </form>
    </div>

</div>
<div class="content">
    <div class="block">
        <div class="block-content">
            <div class="cautionDesc">
                ※一括更新は反映されるまで数分かかることがあります。
            </div>
            <div class="row items-push">
                <div class="col-xl-9">
                    <!-- Calendar Container -->
                    <div class="js-calendar">

                    </div>
                </div>


                <!-- Start jump -->
                <div class="col-xl-9">
                    <div class="input-group col-xl-3 float-right pr-0 jump-block">
                        <input type="text" class="js-add-event form-control" name="jump" value="" placeholder="指定日に移動" id="jump">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-secondary jump-button" data-toggle="tooltip" title="jump">
                                <i class="si si-action-redo"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <!-- End jump -->
            </div>
        </div>
    </div>
</div>
<!-- END Calendar -->
<div class="col-md-9">
    <div>
        <button type="button" class="btn btn-secondary" onclick="location.href='{{ session('storeVacancyRedirectTo', route('admin.store')) }}'">戻る</button>
    </div>
</div>
</div>
<!--<div id="js-loader" class="loader"></div>-->
<!-- END Page Content -->
@endsection

@section('js')

<script src="{{ asset('vendor/codebase/assets/js/plugins/jquery-ui/jquery-ui.min.js') }}"></script>

<script src="{{ asset('vendor/codebase/assets/js/plugins/moment/moment.min.js') }}"></script>
<script src="{{ asset('vendor/codebase/assets/js/plugins/fullcalendar/fullcalendar.min.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.min.js"></script>
<script src="{{ asset('vendor/codebase/assets/js/plugins/fullcalendar/locale/ja.js') }}"></script>

{{-- 在庫カレンダーのjs処理  --}}
<script src="{{ asset('vendor/admin/assets/js/storeStock.js').'?'.time() }}"></script>

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

@include('admin.Layouts.footer')
