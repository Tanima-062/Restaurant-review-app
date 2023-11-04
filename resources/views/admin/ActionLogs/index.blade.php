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
        <h2 class="content-heading">操作履歴一覧</h2>

        <div class="block">
            <div class="block-content block-content-full">
                <form action="{{url('admin/action_logs')}}" method="get" class="form-inline">
                    <div style="margin-bottom: 10px">
                        <input class="form-control mr-sm-2" name="date_from" id="date_from" autocomplete="off" placeholder="日時from" value="{{\Request::query('date_from')}}">
                        <input class="form-control mr-sm-2" name="date_to" id="date_to" autocomplete="off" placeholder="日時to" value="{{\Request::query('date_to')}}">
                        <select class="form-control mr-sm-2" name="staff_id">
                            <option value="">スタッフ</option>
                            @foreach($staffs as $staff)
                                <option value="{{$staff->id}}" @if(\Request::query('staff_id') == $staff->id) selected @endif>{{$staff->name}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <input class="form-control mr-sm-2" name="url" placeholder="要求パス" value="{{\Request::query('url')}}">
                        <select class="form-control mr-sm-2" name="method">
                            <option value="">メソッド</option>
                            @foreach($methods as $method)
                                <option value="{{$method}}" @if(\Request::query('method') == $method) selected @endif>{{$method}}</option>
                            @endforeach
                        </select>
                        <input class="form-control mr-sm-2" name="status" placeholder="結果" value="{{\Request::query('status')}}">
                        <input class="form-control mr-sm-2" name="remote_addr" placeholder="クライアントIP"  value="{{\Request::query('remote_addr')}}">
                        <button type="submit" class="btn btn-alt-primary" value="search">検索する</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Table -->
        <div class="block">
            <div class="block-header block-header-default">
                <h3 class="block-title">総件数 : {{ $logs->total() }}件</h3>
            </div>
            <div class="block-content">
                <table class="table table-borderless table-vcenter">
                    <thead>
                        <tr>
                            <th>日時</th>
                            <th>スタッフ</th>
                            <th>要求パス</th>
                            <th>メソッド</th>
                            <th>結果</th>
                            <th>クライアントIP</th>
                            <th class="text-center" style="width: 50px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($logs as $log)
                        <tr>
                            <td>{{$log->created_at}}</td>
                            <td>@if(isset($log->staff)) {{$log->staff->name}} @endif</td>
                            <td>{{$log->url}}</td>
                            <td>{{$log->method}}</td>
                            <td>{{$log->status}}</td>
                            <td>{{$log->remote_addr}}</td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <a href="{{url('admin/action_logs/view').'/'.$log->id}}" class="btn btn-sm btn-secondary" data-toggle="tooltip" title="View">
                                        <i class="fa fa-search"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="block-content block-content-full block-content-sm bg-body-light font-size-md">
                {{ $logs->appends(\Request::except('page'))->render() }}
            </div>
        </div>
        <!-- END Table -->

    </div>
    <!-- END Content -->

    @include('admin.Layouts.js_files')

    <script>
        $.datetimepicker.setLocale('ja');
        $(function () {
            $("#date_from").datetimepicker({format:'Y-m-d H:i:00'});
            $("#date_to").datetimepicker({format:'Y-m-d H:i:00'});
        });
    </script>
@endsection

@include('admin.Layouts.footer')