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
<style>
    .fc-sun {
        color: red;
    }

    /* 日曜日 */
    .fc-sat {
        color: blue;
    }

    /* 土曜日 */
    .stock-contents {
        display: none
    }

    /* 更新用フォームを非表示 */
    .add-stock-contents {
        display: none
    }

    /* 追加用フォームを非表示 */
    @media screen and (max-width: 960px) {
        .fc-content-sp {
            width: 100%;
            word-wrap: normal;
            font-size: .75rem;
            text-align: center;
            white-space: normal !important;
            line-height: .9rem;
        }
    }

    .d-xl-block {
        display: block !important;
    }
</style>
@endsection

@section('content')
<!-- Page Content -->
<div class="content" id="menuStock">
    @include('admin.Layouts.flash_message')
    <input type="hidden" class="menu_id" data-url="{{ $menu->id }}">
    <!-- Validation Message -->
    <span id="result"></span>

    <div class="block" style="background-color: #f0f2f5; margin-bottom: 0;">
        <div class="col-md-9 block-header">
            <h2 class="content-heading" style="margin-bottom: 0; padding-top: 0;">{{ $menu->name }} 在庫設定</h2>
        </div>
    </div>

    <!-- For more info and examples you can check out https://fullcalendar.io/ -->
    <div class="block">
        <div class="block-content">
            <div class="row items-push">
                <div class="col-xl-9">
                    <!-- Calendar Container -->
                    <div class="js-calendar">

                    </div>
                </div>
                <div class="col-md-3 d-none d-xl-block">
                    <!-- Start Bulk Update Form -->
                    <div id="bulk-update-form">
                        <form class="js-form-add-event mb-30" action="" method="post">
                            @csrf
                            <div class="input-group">
                                <input type="hidden" name="menu_name" value="{{ $menu->name }}">
                                <input type="text" class="js-add-event form-control" name="stock_number_all" value="{{ old('stock_number_all') }}" placeholder="まとめて更新/月">
                                <div class="input-group-append">
                                    <button type="submit" class="btn btn-secondary" data-toggle="tooltip" title="update" id="bulk-update">
                                        <i class="fa fa-plus-circle"></i>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <!-- End Bulk Update Form -->

                    <!-- Update Form -->
                    <form class="stock-form" method="post" id="update-form">
                        @csrf
                        <div class="block-content stock-contents">
                            <div class="form-group">
                                <div class="form-material">
                                    <input type="hidden" id="stock_id" name="stock_id">
                                    <input type="hidden" id="stock_date" name="stock_date">
                                    <div class="d-flex justify-content-between">
                                        <div><label for="option_contents">日付</label></div>
                                        <button type="button" class="close close-event" style="margin-bottom: 5px">
                                            <span aria-hidden="true">×</span>
                                        </button>
                                    </div>
                                    <div id="display_update_date"></div>
                                    <div class="pt-3"><label for="option_contents">在庫数</label></div>
                                    <input type="text" class="form-control" id="stock_number" name="stock_number" value="">
                                </div>
                            </div>
                            <div class="d-flex justify-content-end">
                                <div style="margin-right: 20px;">
                                    <button type="button" class="btn btn-secondary event-delete" data-toggle="tooltip" title="Delete">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                                <div>
                                    <button type="submit" id="stock_btn" class="btn btn-alt-primary"></button>
                                </div>
                            </div>
                        </div>
                    </form>
                    <!-- End Update Form -->

                    <!-- Start Add Form -->
                    <form class="add-stock-form" method="post" id="add-form">
                        @csrf
                        <div class="block-content add-stock-contents">
                            <div class="form-group">
                                <div class="form-material">
                                    <input type="hidden" id="add_stock_id" name="add_stock_id">
                                    <input type="hidden" id="add_stock_date" name="add_stock_date">
                                    <div class="d-flex justify-content-between">
                                        <div><label for="option_contents">日付</label></div>
                                        <button type="button" class="close close-event" style="margin-bottom: 5px">
                                            <span aria-hidden="true">×</span>
                                        </button>
                                    </div>
                                    <div id="display_add_date"></div>
                                    <div class="pt-3"><label for="option_contents">在庫数</label></div>
                                    <input type="text" class="form-control" id="add_stock_number" name="add_stock_number">
                                </div>
                            </div>
                            <div class="d-flex justify-content-end">
                                <div>
                                    <button type="submit" id="add_stock_btn" class="btn btn-alt-primary"></button>
                                </div>
                            </div>
                        </div>
                    </form>
                    <!-- End Add Form -->
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
    <!-- END Calendar -->
    <button type="button" class="btn btn-secondary mb-3" onclick="location.href='{{ session('menuStockRedirectTo', route('admin.menu')) }}'">戻る</button>
</div>
<!-- END Page Content -->
@endsection

@section('js')
{{-- <script src="{{ asset('vendor/codebase/assets/js/codebase.js') }}"></script>--}}
{{-- <script src="{{ asset('vendor/codebase/assets/js/plugins/codebase.core.min.js') }}"></script>--}}
{{-- <script src="{{ asset('vendor/codebase/assets/js/plugins/codebase.app.min.js') }}"></script>--}}
<script src="{{ asset('vendor/codebase/assets/js/plugins/jquery-ui/jquery-ui.min.js') }}"></script>
<script src="{{ asset('vendor/codebase/assets/js/plugins/moment/moment.min.js') }}"></script>
<script src="{{ asset('vendor/codebase/assets/js/plugins/fullcalendar/fullcalendar.min.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
{{-- <script src="{{ asset('vendor/codebase/assets/js/pages/be_comp_calendar.min.js') }}"></script>--}}
<script src="{{ asset('vendor/codebase/assets/js/plugins/fullcalendar/locale/ja.js') }}"></script>

{{-- 在庫カレンダーのjs処理  --}}
<script src="{{ asset('vendor/admin/assets/js/menuStock.js').'?'.time() }}"></script>

@endsection

@include('admin.Layouts.footer')
