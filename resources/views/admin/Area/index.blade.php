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
        <h2 class="content-heading">エリア一覧</h2>

        <div class="block">
            <div class="block-content block-content-full">
                <form action="{{ route('admin.area') }}" method="get" class="form-inline">
                    <input class="form-control mr-sm-2" name="name" placeholder="名前"  value="{{old('name', \Request::query('name'))}}">
                    <input class="form-control mr-sm-2" name="area_cd" placeholder="エリアコード"  value="{{old('area_cd', \Request::query('area_cd'))}}">
                    <input class="form-control mr-sm-2" name="path" placeholder="path"  value="{{old('path', \Request::query('path'))}}">
                    <button type="submit" class="btn btn-alt-primary" value="search">検索する</button>
                </form>
            </div>
        </div>

        <!-- Table -->
        <div class="block">
            <div class="block-header block-header-default">
                <h3 class="block-title">総件数 : {{ $areas->total() }}件</h3>
                @can('inHouseGeneral-higher')
                <div class="block-options">
                    <div class="block-options-item">
                        <a href="{{ route('admin.area.add') }}" class="btn btn-sm btn-secondary" data-toggle="tooltip" title="Add">
                            <i class="fa fa-plus"></i>
                        </a>
                    </div>
                </div>
                @endcan
            </div>
            <div class="block-content">
                <table class="table table-borderless table-vcenter">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 50px">@sortablelink('id','#')</th>
                            <th>@sortablelink('name','名前')</th>
                            <th>@sortablelink('area_cd','エリアコード')</th>
                            <th>@sortablelink('path','PATH')</th>
                            <th class="text-center" style="width: 100px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($areas as $area)
                        <tr @if(!$area->published)class="table-dark" @endif>
                            <th class="text-center" scope="row">{{ $area->id }}</th>
                            <td>{{ $area->name }}</td>
                            <td>{{ $area->area_cd }}</td>
                            <td>{{ $area->path }}</td>
                            <td class="text-center">
                                @can('inHouseGeneral-higher')
                                <div class="btn-group">
                                    <a href="{{ route('admin.area.edit', ['id' => $area->id]) }}" class="btn btn-sm btn-secondary" data-toggle="tooltip" title="Edit">
                                        <i class="fa fa-pencil"></i>
                                    </a>
                                </div>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="block-content block-content-full block-content-sm bg-body-light font-size-md">
                {{ $areas->appends(\Request::except('page'))->render() }}
            </div>
        </div>
        <!-- END Table -->

    </div>
    <!-- END Content -->

    @include('admin.Layouts.js_files')

@endsection

@include('admin.Layouts.footer')
