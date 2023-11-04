@extends('admin.Layouts.base')
@include('admin.Layouts.head')

@include('admin.Layouts.side_overlay')
@include('admin.Layouts.sidebar')
@include('admin.Layouts.page_header')

@section('css')
    <style>
        .form-material> div .form-control, #salesLunchTime .form-control, #salesDinnerTime .form-control {
            padding-left: 0;
            padding-right: 0;
            border-color: transparent;
            border-radius: 0;
            background-color: transparent;
            box-shadow: 0 1px 0 #d4dae3;
            transition: box-shadow .3s ease-out;
        }
        .hide {display: none;}
    </style>
@endsection

@section('content')
    <!-- Content -->
    <div class="content">
    @include('admin.Layouts.flash_message')

    <!-- Default Table Style -->
        <h2 class="content-heading">ストーリー追加</h2>
        <!-- Floating Labels -->
        <div class="block col-md-9">
            <div class="block-content">
                <form action="" method="post" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="redirect_to" value="{{ old('redirect_to', url()->previous()) }}">
                    <div class="form-group">
                        <div class="form-material">
                            <input type="file" class="form-control" id="image" name="image">
                            <label for="image">ストーリー画像<span class="badge bg-danger ml-2 text-white">必須</span></label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <input type="text" class="form-control" id="title" name="title" value="{{ old('title') }}" required>
                            <label for="title">記事タイトル<span class="badge bg-danger ml-2 text-white">必須</span></label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <input type="text" class="form-control" id="url" name="url" value="{{ old('url') }}" required>
                            <label for="url">URL<span class="badge bg-danger ml-2 text-white">必須</span></label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <label for="app_cd">利用サービス<span class="badge bg-danger ml-2 text-white">必須</span></label>
                            <select class="form-control app_cd" id="app_cd" name="app_cd">
                                <option value="">選択してください</option>
                                @foreach($app_cd as $code => $content)
                                    @php if(strlen($code) > 2) continue; @endphp
                                    <option value="{{ strtoupper($code) }}"
                                        {{ old('app_cd', $app_cd) == strtoupper($code) ? 'selected' : '' }}
                                    >{{ $content[strtoupper($code)] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <div class="text-right">
                                <label class="css-control css-control css-control-primary css-switch">
                                    @php
                                        $check = false;
                                        if (! empty(old('redirect_to'))) {
                                            if (old('published') !== '1') {
                                                $check = true;
                                            }
                                        }
                                    @endphp
                                    <input type="checkbox" class="css-control-input" id="published" name="published" value="1">
                                    <span class="css-control-indicator"></span> 公開する
                                </label>
                            </div>
                            <label for="published">公開/非公開</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="text-right">
                            <button type="submit" class="btn btn-alt-primary" value="update">更新</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <button type="button" class="btn btn-secondary" onclick="location.href='{{ route('admin.story') }}'">戻る</button>

    </div>
    <!-- END Content -->
@endsection

@include('admin.Layouts.footer')
