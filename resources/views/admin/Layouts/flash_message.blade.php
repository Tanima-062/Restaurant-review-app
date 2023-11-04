
    @if (Session::has('message'))
        <div class="alert alert-success">
            {{ session('message') }}
        </div>
    @endif
    @if (Session::has('custom_error'))
        <div class="alert alert-danger">
            {{ session('custom_error') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
