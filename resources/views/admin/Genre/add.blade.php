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
        <h2 class="content-heading">ジャンル追加</h2>
        <!-- Floating Labels -->
        <div class="block col-md-9">
            <div class="block-content">
                <form action="{{ route('admin.genre.add') }}" method="post" class="js-validation-material">
                    @csrf
                    <input type="hidden" name="redirect_to" value="{{ old('redirect_to', url()->previous()) }}">
                    <label for="appServiceCd" style="margin-bottom:10px">利用サービス</label>
                    <div class="form-group row" id="appServiceCd">
                        <div class="col-5">
                            <select class="form-control" id="app_cd" name="app_cd">
                                @foreach(config('code.appCd') as $key => $appCd)
                                    <option value="{{ strtoupper($key) }}" @if(strtoupper($key) == old('app_cd')) selected @endif>{{ $appCd[strtoupper($key)] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <label for="genreType" style="margin-bottom:10px">ジャンル階層</label>
                    <div class="form-group row" id="genreType">
                        <div class="col-3">
                            <select class="form-control" id="big_genre" name="big_genre">
                                <option value="">- ジャンル(大) -</option>
                                @foreach(config('const.genre.bigGenre') as $key => $value)
                                    <option value="{{ $key }}" @if($key == old('big_genre')) selected @endif>{{ $value['value'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-3">
                            <select class="form-control" id="middle_genre" name="middle_genre">
                                <option value="">- ジャンル(中) -</option>
                            </select>
                        </div>
                        <div class="col-3">
                            <select class="form-control" id="small_genre" name="small_genre">
                                <option value="">- ジャンル(小) -</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <input type="text" class="form-control" id="name" name="name" value="{{old('name')}}" required>
                            <label for="name">カテゴリ名<span class="badge bg-danger ml-2 text-white">必須</span></label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <input type="text" class="form-control" id="genre_cd" name="genre_cd" value="{{old('genre_cd')}}" required>
                            <label for="tel">カテゴリコード<span class="badge bg-danger ml-2 text-white">必須</span></label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <div class="text-right">
                                <label class="css-control css-control css-control-primary css-switch">
                                    @php
                                        $check = true;
                                        if (!empty(old('redirect_to'))) {
                                            if (old('published') !== '1') {
                                                $check = false;
                                            }
                                        }
                                    @endphp
                                    <input type="checkbox" class="css-control-input" id="published" name="published" value="1" @if($check) checked @endif>
                                    <span class="css-control-indicator"></span> 公開する
                                </label>
                            </div>
                            <label for="published">公開/非公開</label>
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

        <button type="button" class="btn btn-secondary" onclick="location.href='{{ old('redirect_to', url()->previous()) }}'">戻る</button>

    </div>
    <!-- END Content -->
    @include('admin.Layouts.js_files')

    <script src="{{ asset('vendor/admin/assets/js/genre.js').'?'.time() }}"></script>
@endsection

@include('admin.Layouts.footer')
