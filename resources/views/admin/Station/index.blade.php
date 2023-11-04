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
        <h2 class="content-heading">駅一覧</h2>

        <div class="block">
            <div class="block-content block-content-full">
                <form action="{{ route('admin.station') }}" method="get" class="form-inline">
                    <input class="form-control mr-sm-2" name="id" placeholder="ID" value="{{old('id', \Request::query('id'))}}">
                    <input class="form-control mr-sm-2" name="name" placeholder="名前"  value="{{old('name', \Request::query('name'))}}">
                    <button type="submit" class="btn btn-alt-primary" value="search">検索する</button>
                </form>
            </div>
        </div>

        <span class="text-danger">csvをフルインポートすると時間も負荷もかかるので更新日時を見てcsvの差分だけインポートしてください</span>
        <!-- Table -->
        <div class="block">
            <div class="block-header block-header-default">
                <h3 class="block-title">総件数 : {{ $stations->total() }}件</h3>
                <div class="block-options">
                    <div class="block-options-item">
                        <form method="post" action="{{ route('admin.station.upload') }}" enctype="multipart/form-data" class="form-inline">
                            {{ csrf_field() }}
                            <input type="file" id="file" name="file">
                            <button type="submit" class="btn btn-alt-primary">CSVインポート</button>&nbsp;
                            <a href="{{route('admin.station.status')}}" class="btn btn-alt-primary">処理状況確認</a>
                        </form>
                    </div>
                </div>
            </div>
            <div class="block-content">
                <table class="table table-borderless table-vcenter">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 50px;">@sortablelink('id','#')</th>
                            <th>@sortablelink('name','名前')</th>
                            <th>@sortablelink('name_roma','linkコード')</th><!-- station_cd ではない -->
                            <th>@sortablelink('latitude','緯度')</th>
                            <th>@sortablelink('longitude','経度')</th>
                            <th>@sortablelink('created_at','作成日時')</th>
                            <th>@sortablelink('updated_at','更新日時')</th>
                            <th class="text-center" style="width: 100px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($stations as $station)
                        <tr @if(!is_null($station->deleted_at))class="table-dark" @endif>
                            <th class="text-center" scope="row">{{ $station->id }}</th>
                            <td>{{ $station->name }}</td>
                            <td>{{ $station->name_roma }}</td>
                            <td>{{ $station->latitude }}</td>
                            <td>{{ $station->longitude }}</td>
                            <td>{{ $station->created_at }}</td>
                            <td>{{ $station->updated_at }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="block-content block-content-full block-content-sm bg-body-light font-size-md">
                {{ $stations->appends(\Request::except('page'))->render() }}
            </div>
        </div>
        <!-- END Table -->

    </div>
    <!-- END Content -->

    @include('admin.Layouts.js_files')

@endsection

@include('admin.Layouts.footer')
