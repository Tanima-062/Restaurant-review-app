@section('admin_head')

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">

    <title>レストラン管理画面</title>

    <meta name="description" content="レストラン管理画面">
    <meta name="author" content="pixelcave">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Open Graph Meta -->
    <meta property="og:title" content="レストラン管理画面">
    <meta property="og:site_name" content="レストラン">
    <meta property="og:description" content="レストラン管理画面">
    <meta property="og:type" content="website">
    <meta property="og:url" content="">
    <meta property="og:image" content="">

    <!-- Icons -->
    <!-- The following icons can be replaced with your own, they are used by desktop and mobile browsers -->
    @php
        if (config('app.env') === 'production') {
            $favicon = asset('favicon_prod.ico');
        } elseif (config('app.env') === 'staging') {
            $favicon = asset('favicon_test.ico');
        } else {
            $favicon = asset('favicon.ico');
        }
    @endphp
    <link rel="shortcut icon" href="{{$favicon}}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{$favicon}}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{$favicon}}">
    <!-- END Icons -->

    <!-- Stylesheets -->
    <!-- Codebase framework -->
    <link rel="stylesheet" id="css-main" href="{{ asset('vendor/codebase/assets/css/codebase.min.css') }}">
    <!-- END Stylesheets -->

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jquery-datetimepicker@2.5.20/jquery.datetimepicker.css">

    @if(isset($isMobile) && ($isMobile))
        <link href="{{ asset('css/reservation/index-mobile.css') }}" rel="stylesheet" type="text/css">
        <link href="{{ asset('css/reservation/edit-mobile.css') }}" rel="stylesheet" type="text/css">
    @endif

    @if(Request::is('*/newpayment/detail*'))
    <link href="{{ asset('css/payment/detail.css') }}" rel="stylesheet" type="text/css">
    @elseif((Request::is('*/newpayment*')))
    <link href="{{ asset('css/payment/index.css') }}" rel="stylesheet" type="text/css">
    @endif

    @if(Request::is('*/payment/detail*'))
        <link href="{{ asset('css/payment/detail.css') }}" rel="stylesheet" type="text/css">
    @endif

    @if(Request::is('*/reservation*'))
        <link href="{{ asset('css/reservation/index.css') }}" rel="stylesheet" type="text/css">
    @endif

@endsection
