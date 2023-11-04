<!doctype html>
<!--[if lte IE 9]>     <html lang="ja" class="no-focus lt-ie10 lt-ie10-msg"> <![endif]-->
<!--[if gt IE 9]><!--> <html lang="ja" class="no-focus"> <!--<![endif]-->
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">

    <title>レストラン管理画面</title>

    <meta name="description" content="レストラン管理画面">
    <meta name="author" content="pixelcave">
    <meta name="robots" content="noindex, nofollow">

    <!-- Open Graph Meta -->
    <meta property="og:title" content="レストラン管理画面">
    <meta property="og:site_name" content="レストラン管理画面">
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
        };
    @endphp
    <link rel="shortcut icon" href="{{ $favicon }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ $favicon }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ $favicon }}">
    <!-- END Icons -->

    <!-- Stylesheets -->
    <!-- Codebase framework -->
    <link rel="stylesheet" id="css-main" href="{{ asset('vendor/codebase/assets/css/codebase.min.css') }}">

    <!-- You can include a specific file from css/themes/ folder to alter the default color theme of the template. eg: -->
    <!-- <link rel="stylesheet" id="css-theme" href="css/themes/flat.min.css"> -->
    <!-- END Stylesheets -->
</head>
<body>
<div id="page-container" class="main-content-boxed">

    <!-- Main Container -->
    <main id="main-container">

        <!-- Page Content -->
        <div class="bg-gd-sun">
            <div class="hero-static content content-full bg-white invisible" data-toggle="appear">
                <!-- Header -->
                <div class="py-30 px-5 text-center">
                    <a class="link-effect font-w700">
                        <span class="font-size-xl text-primary-dark">レストラン管理画面</span>
                    </a>
                    <h2 class="h4 font-w400 text-muted mb-0">Please sign in</h2>
                </div>
                <!-- END Header -->

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <!-- Sign In Form -->
                <div class="row justify-content-center px-5">
                    <div class="col-sm-8 col-md-6 col-xl-4">
                        <!-- jQuery Validation (.js-validation-signin class is initialized in js/pages/op_auth_signin.js) -->
                        <!-- For more examples you can check out https://github.com/jzaefferer/jquery-validation -->
                        <!--<form class="js-validation-signin" action="be_pages_auth_all.html" method="post">-->
                        <form class="js-validation-signin" action="{{ route('admin.login') }}" method="post">
                            @csrf
                            <div class="form-group row">
                                <div class="col-12">
                                    <div class="form-material floating">
                                        <input type="text" class="form-control" id="username" name="username">
                                        <label for="login-username">ログインID</label>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group row">
                                <div class="col-12">
                                    <div class="form-material floating">
                                        <input type="password" class="form-control" id="password" name="password">
                                        <label for="login-password">パスワード</label>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group row gutters-tiny">
                                <div class="col-12 mb-10">
                                    <button type="submit" class="btn btn-block btn-hero btn-noborder btn-rounded btn-alt-primary">
                                        <i class="si si-login mr-10"></i> ログイン
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <!-- END Sign In Form -->
            </div>
        </div>
        <!-- END Page Content -->

    </main>
    <!-- END Main Container -->
</div>
<!-- END Page Container -->

<!-- Codebase Core JS -->
<script src="{{ asset('vendor/codebase/assets/js/core/jquery.min.js') }}"></script>
<script src="{{ asset('vendor/codebase/assets/js/core/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('vendor/codebase/assets/js/core/jquery.slimscroll.min.js') }}"></script>
<script src="{{ asset('vendor/codebase/assets/js/core/jquery.scrollLock.min.js') }}"></script>
<script src="{{ asset('vendor/codebase/assets/js/core/jquery.appear.min.js') }}"></script>
<script src="{{ asset('vendor/codebase/assets/js/core/jquery.countTo.min.js') }}"></script>
<script src="{{ asset('vendor/codebase/assets/js/core/js.cookie.min.js') }}"></script>
<script src="{{ asset('vendor/codebase/assets/js/codebase.js') }}"></script>

<!-- Page JS Plugins -->
<script src="{{ asset('vendor/codebase/assets/js/plugins/jquery-validation/jquery.validate.min.js') }}"></script>

<!-- Page JS Code -->
<script src="{{ asset('vendor/codebase/assets/js/pages/op_auth_signin.js') }}"></script>
</body>
</html>
