<!doctype html>
<!--[if lte IE 9]>     <html lang="ja" class="no-focus lt-ie10 lt-ie10-msg"> <![endif]-->
<!--[if gt IE 9]><!--> <html lang="ja" class="no-focus"> <!--<![endif]-->
<head>
    @yield('admin_head')
    @yield('css')
</head>
<body>
<div id="page-container" class="sidebar-o side-scroll page-header-modern main-content-boxed">
@yield('admin_side_overlay')
@yield('admin_sidebar')
@yield('admin_page_header')

<!-- Main Container -->
    <main id="main-container">
        @yield('content')
    </main>
    <!-- END Main Container -->
    @yield('admin_footer')
</div>
@include('admin.Layouts.js_files')
</body>
</html>
