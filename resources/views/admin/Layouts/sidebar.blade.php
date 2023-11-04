@section('admin_sidebar')
    <!-- Sidebar -->
    <!--
        Helper classes

        Adding .sidebar-mini-hide to an element will make it invisible (opacity: 0) when the sidebar is in mini mode
        Adding .sidebar-mini-show to an element will make it visible (opacity: 1) when the sidebar is in mini mode
            If you would like to disable the transition, just add the .sidebar-mini-notrans along with one of the previous 2 classes

        Adding .sidebar-mini-hidden to an element will hide it when the sidebar is in mini mode
        Adding .sidebar-mini-visible to an element will show it only when the sidebar is in mini mode
            - use .sidebar-mini-visible-b if you would like to be a block when visible (display: block)
    -->
    <nav id="sidebar" style="overflow-y: auto;">
        <!-- Sidebar Scroll Container -->
        <div id="sidebar-scroll">
            <!-- Sidebar Content -->
            <div class="sidebar-content">
                <!-- Side Header -->
                <div class="content-header content-header-fullrow px-15">
                    <!-- Mini Mode -->
                    <div class="content-header-section sidebar-mini-visible-b">
                        <!-- Logo -->
                        <span class="content-header-item font-w700 font-size-xl float-left animated fadeIn">
                                    <span class="text-dual-primary-dark">c</span><span class="text-primary">b</span>
                                </span>
                        <!-- END Logo -->
                    </div>
                    <!-- END Mini Mode -->

                    <!-- Normal Mode -->
                    <div class="content-header-section text-center align-parent sidebar-mini-hidden">
                        <!-- Close Sidebar, Visible only on mobile screens -->
                        <!-- Layout API, functionality initialized in Codebase() -> uiApiLayout() -->
                        <button type="button" class="btn btn-circle btn-dual-secondary d-lg-none align-v-r" data-toggle="layout" data-action="sidebar_close">
                            <i class="fa fa-times text-danger"></i>
                        </button>
                        <!-- END Close Sidebar -->

                        <!-- Logo -->
                        <div class="content-header-item">
                            <a class="link-effect font-w700" href="{{url('admin/')}}">
                                <!--<i class="si si-fire text-primary"></i>-->
                                <span class="font-size-xl text-dual-primary-dark">レストラン</span><span class="font-size-xl text-primary">管理画面</span>
                            </a>
                        </div>
                        <!-- END Logo -->
                    </div>
                    <!-- END Normal Mode -->
                </div>
                <!-- END Side Header -->

                <!-- Side User -->
                <div class="content-side content-side-full content-side-user px-10 align-parent" style="height: auto;">
                    <!-- Visible only in mini mode -->
                    <div class="sidebar-mini-visible-b align-v animated fadeIn">
                        <img class="img-avatar img-avatar32" src="{{ asset('vendor/codebase/assets/media/avatars/avatar15.jpg') }}" alt="">
                    </div>
                    <!-- END Visible only in mini mode -->

                    <!-- Visible only in normal mode -->
                    <div class="sidebar-mini-hidden-b text-center">
                        <a class="img-link" href="javascript:void(0)">
                            <img class="img-avatar" src="{{ asset('vendor/codebase/assets/media/avatars/avatar15.jpg') }}" alt="">
                        </a>
                        <ul class="list-inline mt-10">
                            <li class="list-inline-item d-block">
                                <a class="text-dual-primary-dark font-size-xs font-w600 text-uppercase" href="javascript:void(0)" style="overflow-wrap: break-word;">{{ Auth::user()->name }}さん</a>
                                <a class="link-effect text-dual-primary-dark" href="{{url('admin/logout')}}" data-toggle="tooltip" title="Logout">
                                    <i class="si si-logout"></i>
                                </a>
                            </li>
                            <!--<li class="list-inline-item">-->
                                <!-- Layout API, functionality initialized in Codebase() -> uiApiLayout() -->
                                <!--<a class="link-effect text-dual-primary-dark" data-toggle="layout" data-action="sidebar_style_inverse_toggle" href="javascript:void(0)">
                                    <i class="si si-drop"></i>
                                </a>-->
                            <!--</li>-->
                            {{-- <li class="list-inline-item">
                                <a class="link-effect text-dual-primary-dark" href="{{url('admin/logout')}}" data-toggle="tooltip" title="Logout">
                                    <i class="si si-logout"></i>
                                </a>
                            </li> --}}
                        </ul>
                    </div>
                    <!-- END Visible only in normal mode -->
                </div>
                <!-- END Side User -->

                <!-- Side Navigation -->
                <div class="content-side content-side-full">
                    <ul class="nav-main">
{{--                        <li style="display: {{App\Models\StaffAuthorityPage::display('system_notifications', Auth::user())}}">--}}
{{--                            <a class='{{sidebar_menu_active('admin/system_notifications')}}' id="sb-dashboard" href="{{url('admin/system_notifications')}}"><i class="si si-info"></i><span class="sidebar-mini-hide">通知一覧</span></a>--}}
{{--                        </li>--}}
                        <li style="display: {{App\Models\StaffAuthorityPage::display('reservation', Auth::user())}}">
                            <a class='{{sidebar_menu_active('admin/reservation')}}' id="sb-dashboard" href="{{url('admin/reservation')}}"><i class="si si-note"></i><span class="sidebar-mini-hide">予約一覧</span></a>
                        </li>
{{--                        <li style="display: {{App\Models\StaffAuthorityPage::display('cancels', Auth::user())}}">--}}
{{--                            <a class='{{sidebar_menu_active('admin/cancels')}}' id="sb-dashboard" href="{{url('admin/cancels')}}"><i class="si si-umbrella"></i><span class="sidebar-mini-hide">キャンセル一覧</span></a>--}}
{{--                        </li>--}}
                        <li style="display: {{App\Models\StaffAuthorityPage::display('payment', Auth::user())}}">
                            <a class='{{sidebar_menu_active('admin/payment')}}' id="sb-dashboard" href="{{url('admin/payment')}}"><i class="fa fa-yen"></i><span class="sidebar-mini-hide">入金一覧</span></a>
                        </li>
                        <li style="display: {{App\Models\StaffAuthorityPage::display('payment', Auth::user())}}">
                            <a class='{{sidebar_menu_active('admin/newpayment')}}' id="sb-dashboard" href="{{qs_url('admin/newpayment',['date_from'=>date('Y-m-d'), 'date_to'=>date('Y-m-d'), 'serviceCd' => 'rs'])}}"><i class="fa fa-yen"></i><span class="sidebar-mini-hide">NEW 入金一覧</span></a>
                        </li>
                        @can('settlementAdmin-higher')
                        <div class="dropdown-divider"></div><!-- 他の集計が追加されたら移動する -->
                        <li style="display: {{ App\Models\StaffAuthorityPage::display('settlement_confirm', Auth::user()) }}">
                            <a class='{{ sidebar_menu_active('admin/settlement_confirm') }}' id="sb-dashboard" href="{{ route('admin.settlementConfirm') }}"><i class="fa fa-file-pdf-o"></i><span class="sidebar-mini-hide">精算確認</span></a>
                        </li>
                        <li style="display: {{ App\Models\StaffAuthorityPage::display('settlement_aggregate', Auth::user()) }}">
                            <a class='{{ sidebar_menu_active('admin/settlement_aggregate') }}' id="sb-dashboard" href="{{ route('admin.settlementAggregate') }}"><i class="fa fa-balance-scale"></i><span class="sidebar-mini-hide">精算額集計</span></a>
                        </li>
                        @endcan
                        <div class="dropdown-divider"></div>
                        <li style="display: {{ App\Models\StaffAuthorityPage::display('staff', Auth::user()) }}">
                            <a class='{{ sidebar_menu_active('admin/staff(?!/edit_password)') }}' id="sb-dashboard" href="{{ route('admin.staff') }}"><i class="si si-people"></i><span class="sidebar-mini-hide">スタッフ一覧</span></a>
                        </li>
                        @can('inAndOutHouseGeneral-only')
                        <li style="display: {{ App\Models\StaffAuthorityPage::display('store', Auth::user()) }}">
                            <a class='{{ sidebar_menu_active('admin/settlement_company') }}' id="sb-dashboard" href="{{ route('admin.settlementCompany') }}"><i class="fa fa-building-o"></i><span class="sidebar-mini-hide">精算会社一覧</span></a>
                        </li>
                        @endcan
                        @can('clientAdmin-higher')
                        <div class="dropdown-divider"></div>
                        @endcan
                        <li style="display: {{ App\Models\StaffAuthorityPage::display('store', Auth::user()) }}">
                            <a class='{{ sidebar_menu_active('admin/store') }}' id="sb-dashboard" href="{{ route('admin.store') }}">
                                <i class="si si-list"></i>
                                <span class="sidebar-mini-hide">@can('inAndOutHouseGeneral-only')店舗一覧@else基本情報管理@endcan</span>
                            </a>
                        </li>
                        <li style="display: {{ App\Models\StaffAuthorityPage::display('menu', Auth::user()) }}">
                            <a class='{{ sidebar_menu_active('admin/menu') }}' id="sb-dashboard" href="{{ route('admin.menu') }}"><i class="fa fa-cutlery"></i><span class="sidebar-mini-hide">メニュー一覧</span></a>
                        </li>
                        @can('clientAdmin-higher')
                        <div class="dropdown-divider"></div>
                        @endcan
                        <li style="display: {{ App\Models\StaffAuthorityPage::display('genre', Auth::user()) }}">
                            <a class='{{ sidebar_menu_active('admin/genre') }}' id="sb-dashboard" href="{{ route('admin.genre') }}"><i class="fa fa-beer"></i><span class="sidebar-mini-hide">ジャンル一覧</span></a>
                        </li>
                        <li style="display: {{ App\Models\StaffAuthorityPage::display('area', Auth::user()) }}">
                            <a class='{{ sidebar_menu_active('admin/area') }}' id="sb-dashboard" href="{{ route('admin.area') }}"><i class="fa fa-map"></i><span class="sidebar-mini-hide">エリア一覧</span></a>
                        </li>
                        <li style="display: {{ App\Models\StaffAuthorityPage::display('station', Auth::user()) }}">
                            <a class='{{ sidebar_menu_active('admin/station') }}' id="sb-dashboard" href="{{ route('admin.station') }}"><i class="fa fa-subway"></i><span class="sidebar-mini-hide">駅一覧</span></a>
                        </li>
                        @can('inHouseGeneral-higher')
                        <li style="display: {{ App\Models\StaffAuthorityPage::display('story', Auth::user()) }}">
                            <a class='{{ sidebar_menu_active('admin/story') }}' id="sb-dashboard" href="{{ route('admin.story') }}"><i class="si si-camcorder"></i><span class="sidebar-mini-hide">ストーリーマスタ</span></a>
                        </li>
                        @endcan
                        @can('inHouseAdmin-only')
                        <div class="dropdown-divider"></div>
                        @endcan
                        <li style="display: {{App\Models\StaffAuthorityPage::display('notice', Auth::user())}}">
                            <a class='{{sidebar_menu_active('admin/notice')}}' id="sb-dashboard" href="{{url('admin/notice')}}"><i class="si si-envelope"></i><span class="sidebar-mini-hide">お知らせ管理</span></a>
                        </li>
                        <li>
                            <a class='{{ sidebar_menu_active('admin/staff/edit_password') }}' id="sb-dashboard" href="{{ route('admin.staff.editPasswordForm') }}"><i class="si si-key"></i><span class="sidebar-mini-hide">パスワード変更</span></a>
                        </li>
{{--                        <li style="display: {{App\Models\StaffAuthorityPage::display('maintenance', Auth::user())}}">--}}
{{--                            <a class='{{sidebar_menu_active('admin/maintenance')}}' id="sb-dashboard" href="{{url('admin/maintenance')}}"><i class="fa fa-exclamation-triangle"></i><span class="sidebar-mini-hide">メンテナンス設定</span></a>--}}
{{--                        </li>--}}
                    </ul>
                </div>
                <!-- END Side Navigation -->
            </div>
            <!-- Sidebar Content -->
        </div>
        <!-- END Sidebar Scroll Container -->
    </nav>
    <!-- END Sidebar -->
@endsection
