@extends('admin.Layouts.base')
@include('admin.Layouts.head')

@include('admin.Layouts.side_overlay')
@include('admin.Layouts.sidebar')
@include('admin.Layouts.page_header')

@section('content')
    <!-- Content -->
    <div class="content">
    @include('admin.Layouts.flash_message')

    <!-- Default Table Style -->
        <h2 class="content-heading">店舗ジャンル追加</h2>
        <!-- Floating Labels -->
        <div class="block col-md-9">
            <div class="block-content">
                <form action="{{ route('admin.store.genre.add', ['id' => $store->id]) }}" method="post" class="js-validation-material">
                    @csrf
                    <input type="hidden" name="redirect_to" value="{{ old('redirect_to', url()->previous()) }}">
                    <input type="hidden" name="big_genre" id="big_genre" value="{{$bigGenre}}">
                    <div class="form-group">
                        <div class="form-material">
                            <label for="middle_genre">ジャンル(中)<span class="badge bg-danger ml-2 text-white">必須</span></label>
                            <select class="form-control" id="middle_genre" name="middle_genre" required>
                                <option value="">-</option>
                                @foreach($middleGenres as $key => $value)
                                    <option value="{{ strtolower($value->genre_cd) }}" @if(strtolower($value->genre_cd) == old('middle_genre')) selected @endif>{{ $value->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <label for="small_genre">ジャンル(小)<span class="badge bg-danger ml-2 text-white">必須</span></label>
                            <select class="form-control" id="small_genre" name="small_genre" required>
                                <option value="">-</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="text-right">
                            <button type="submit" class="btn btn-alt-primary" value="add">追加</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <button type="button" class="btn btn-secondary" onclick="location.href='{{ old('redirect_to', route('admin.store.genre.edit', ['id' => $store->id])) }}'">戻る</button>

    </div>
    <!-- END Content -->
    @include('admin.Layouts.js_files')

    <script src="{{ asset('vendor/admin/assets/js/genre.js').'?'.time() }}"></script>
@endsection

@include('admin.Layouts.footer')
