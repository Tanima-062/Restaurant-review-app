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
        <h2 class="content-heading">お知らせ一覧</h2>

        <div class="block">
            <div class="block-content block-content-full">
                <form action="{{url('admin/notice')}}" method="get" class="form-inline">
                    <input class="form-control mr-sm-2" name="datetime_from" id="datetime_from" autocomplete="off" placeholder="掲載開始日時" value="{{old('datetime_from', \Request::query('datetime_from'))}}">
                    <input class="form-control mr-sm-2" name="datetime_to" id="datetime_to" autocomplete="off" placeholder="掲載終了日時" value="{{old('datetime_to', \Request::query('datetime_to'))}}">
                    <select class="form-control mr-sm-2" name="updated_by">
                        <option value="">更新者</option>
                        @foreach($staffs as $staff)
                            <option value="{{$staff->id}}" @if(old('updated_by', \Request::query('updated_by')) == $staff->id) selected @endif>{{$staff->name}}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-alt-primary" value="search">検索する</button>
                </form>
            </div>
        </div>

        <!-- Table -->
        <div class="block">
            <div class="block-header block-header-default">
                <h3 class="block-title">総件数 : {{ $notices->total() }}件</h3>
                <div class="block-options">
                    <div class="block-options-item">
                        <a href="{{url('admin/notice/add')}}" class="btn btn-sm btn-secondary" data-toggle="tooltip" title="Add">
                            <i class="fa fa-plus"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="block-content">
                <table class="table table-borderless table-vcenter">
                    <thead>
                    <tr>
                        <th class="text-center" style="width: 50px;">#</th>
                        <th>利用サービス</th>
                        <th>タイトル</th>
                        <th>表示箇所</th>
                        <th>掲載期間</th>
                        <th>作成者</th>
                        <th>更新者</th>
                        <th class="text-center" style="width: 100px;"></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($notices as $notice)
                        <tr @if(!$notice->published)class="table-dark" @endif>
                            <th class="text-center" scope="row">{{$notice->id}}</th>
                            <td>{{config('code.appCd.'.strtolower($notice->app_cd).'.'.$notice->app_cd)}}</td>
                            <td>{{$notice->title}}</td>
                            <td>
                                @if($notice->ui_website_flg)テイクアウトサービストップ<br>@endif
                                @if($notice->ui_admin_flg)社内管理画面トップ@endif
                            </td>
                            <td>{{$notice->datetime_from}}<br>～<br>{{$notice->datetime_to}}</td>
                            <td>@if(isset($notice->createdBy)){{$notice->createdBy->name}}<br>@endif{{$notice->created_at}}</td>
                            <td>@if(isset($notice->updatedBy)){{$notice->updatedBy->name}}<br>@endif{{$notice->updated_at}}</td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <a href="{{url('admin/notice/edit').'/'.$notice->id}}" class="btn btn-sm btn-secondary" data-toggle="tooltip" title="Edit">
                                        <i class="fa fa-pencil"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="block-content block-content-full block-content-sm bg-body-light font-size-md">
                {{ $notices->appends(\Request::except('page'))->render() }}
            </div>
        </div>
        <!-- END Table -->

    </div>
    <!-- END Content -->

    @include('admin.Layouts.js_files')

    <script>
        $.datetimepicker.setLocale('ja');
        $(function () {
            $("#datetime_from").datetimepicker({format:'Y-m-d H:i'});
            $("#datetime_to").datetimepicker({format:'Y-m-d H:i'});
        });
    </script>

@endsection

@include('admin.Layouts.footer')
