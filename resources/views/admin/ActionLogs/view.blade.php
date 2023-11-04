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
        <h2 class="content-heading">操作履歴詳細</h2>
        <!-- Floating Labels -->
        <div class="block col-md-9">
            <div class="block-content">
                <form>
                    {{csrf_field()}}
                    <div class="form-group">
                        <div class="form-material">
                            <input type="text" class="form-control" id="created_at" name="created_at" value="{{$log->created_at}}" disabled>
                            <label for="name">日時</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <input type="text" class="form-control" id="staff_id" name="staff_id" value="@if(isset($log->staff)) {{$log->staff->name}} @endif" disabled>
                            <label for="name">スタッフ</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <input type="text" class="form-control" id="url" name="url" value="{{$log->url}}" disabled>
                            <label for="name">要求パス</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <input type="text" class="form-control" id="method" name="method" value="{{$log->method}}" disabled>
                            <label for="name">メソッド</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <label for="message">内容</label>
                            <div>
                                <textarea id="message" class="form-control" name="message" rows="{{substr_count($log->message, "\n") > 10 ? 10 : substr_count($log->message, "\n")}}" disabled>{{$log->message}}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <input type="text" class="form-control" id="status" name="status" value="{{$log->status}}" disabled>
                            <label for="name">結果</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <input type="text" class="form-control" id="remote_addr" name="remote_addr" value="{{$log->remote_addr}}" disabled>
                            <label for="name">クライアントIP</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <input type="text" class="form-control" id="user_agent" name="user_agent" value="{{$log->user_agent}}" disabled>
                            <label for="name">ブラウザ</label>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <button type="button" class="btn btn-secondary" onclick="location.href='{{url()->previous('admin/action_logs')}}'">戻る</button>

    </div>
    <!-- END Content -->
    @include('admin.Layouts.js_files')

    <script src="{{ asset('vendor/codebase/assets/js/plugins/jquery-validation/jquery.validate.min.js') }}"></script>
    <script src="{{ asset('vendor/codebase/assets/js/pages/be_forms_validation.js') }}"></script>
@endsection

@include('admin.Layouts.footer')
