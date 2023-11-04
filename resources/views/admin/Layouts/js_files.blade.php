
    <!-- Codebase Core JS -->
    <script src="{{ asset('vendor/codebase/assets/js/core/jquery.min.js') }}"></script>
    <script src="{{ asset('vendor/codebase/assets/js/core/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('vendor/codebase/assets/js/core/jquery.slimscroll.min.js') }}"></script>
    <script src="{{ asset('vendor/codebase/assets/js/core/jquery.scrollLock.min.js') }}"></script>
    <script src="{{ asset('vendor/codebase/assets/js/core/jquery.appear.min.js') }}"></script>
    <script src="{{ asset('vendor/codebase/assets/js/core/jquery.countTo.min.js') }}"></script>
    <script src="{{ asset('vendor/codebase/assets/js/core/js.cookie.min.js') }}"></script>
    <script src="{{ asset('vendor/codebase/assets/js/codebase.js') }}"></script>

    <script src="https://cdn.jsdelivr.net/npm/jquery-datetimepicker@2.5.20/build/jquery.datetimepicker.full.min.js"></script>
    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    </script>
    @yield('js')
