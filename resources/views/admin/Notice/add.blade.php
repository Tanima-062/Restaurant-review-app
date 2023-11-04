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
        <h2 class="content-heading">お知らせ追加</h2>
        <!-- Floating Labels -->
        <div class="block col-md-9">
            <div class="block-content">
                <form action="{{url('admin/notice/add')}}" method="post" class="js-validation-material">
                    {{csrf_field()}}
                    <input type="hidden" name="redirect_to" value="{{old('redirect_to', url()->previous())}}">
                    <div class="form-group">
                        <div class="form-material">
                            <label for="app_cd">利用サービス<span class="badge bg-danger ml-2 text-white">必須</span></label>
                            <select class="form-control" id="app_cd" name="app_cd" required>
                                <option value="">利用サービス</option>
                                @foreach(config('code.appCd') as $key => $appCd)
                                    @php if(strlen($key) > 2) continue; @endphp
                                <option value="{{ strtoupper($key) }}" {{ old('app_cd', \Request::query('app_cd')) == strtoupper($key) ? 'selected' : '' }}>{{ $appCd[strtoupper($key)] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <input type="text" class="form-control" id="title" name="title" value="{{old('title')}}" required>
                            <label for="name">タイトル<span class="badge bg-danger ml-2 text-white">必須</span></label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <textarea class="form-control" id="message" name="message" rows="3" required>{{old('message')}}</textarea>
                            <label for="precautions">本文<span class="badge bg-danger ml-2 text-white">必須</span></label>
                        </div>
                    </div>
                    <label for="published_at" style="margin-bottom:10px">公開日時<span class="badge bg-danger ml-2 text-white">必須</span></label>
                    <div class="form-group row">
                        <div class="col-3">
                            <input type="text" class="form-control" id="published_at" name="published_at" value="{{old('published_at')}}" autocomplete="off" required>
                        </div>
                    </div>
                    <label for="apply_term" style="margin-bottom:10px">掲載期間<span class="badge bg-danger ml-2 text-white">必須</span></label>
                    <div class="form-group row" id="datetime">
                        <div class="col-3">
                            <input type="text" class="form-control" id="datetime_from" name="datetime_from" value="{{old('datetime_from')}}" autocomplete="off" required>
                        </div>
                        <span style="margin-top:5px">～</span>
                        <div class="col-3">
                            <input type="text" class="form-control" id="datetime_to" name="datetime_to" value="{{old('datetime_to')}}" autocomplete="off" required>
                        </div>
                    </div>
                    <label for="ui" style="margin-bottom:10px">表示箇所<span class="badge bg-danger ml-2 text-white">必須</span></label>
                    <div class="form-group row" id="ui">
                        <div class="col-12">
                            <div class="custom-control custom-checkbox mb-5">
                                @php
                                    $check = false;
                                    if (!empty(old('redirect_to'))) {
                                        if (old('ui_website_flg') === '1') {
                                            $check = true;
                                        }
                                    }
                                @endphp
                                <input class="custom-control-input" type="checkbox" name="ui_website_flg" id="ui_website_flg" value="1" @if($check) checked @endif>
                                <label class="custom-control-label" for="ui_website_flg">サービストップ</label>
                            </div>
                            <div class="custom-control custom-checkbox mb-5">
                                @php
                                    $check = false;
                                    if (!empty(old('redirect_to'))) {
                                        if (old('ui_admin_flg') === '1') {
                                            $check = true;
                                        }
                                    }
                                @endphp
                                <input class="custom-control-input" type="checkbox" name="ui_admin_flg" id="ui_admin_flg" value="1" @if($check) checked @endif>
                                <label class="custom-control-label" for="ui_admin_flg">社内管理画面トップ</label>
                            </div>
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
                            <button type="submit" class="btn btn-alt-primary" value="update">追加</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <button type="button" class="btn btn-secondary" onclick="location.href='{{old('redirect_to', url()->previous())}}'">戻る</button>

    </div>
    <!-- END Content -->
    @include('admin.Layouts.js_files')

    <script>
        $.datetimepicker.setLocale('ja');
        $(function () {
            $("#datetime_from").datetimepicker({format:'Y-m-d H:i'});
            $("#datetime_to").datetimepicker({format:'Y-m-d H:i'});
            $("#published_at").datetimepicker({format:'Y-m-d H:i'});
        });
    </script>
@endsection

@include('admin.Layouts.footer')
