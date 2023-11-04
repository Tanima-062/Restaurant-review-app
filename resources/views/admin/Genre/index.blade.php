@extends('admin.Layouts.base')
@include('admin.Layouts.head')

@include('admin.Layouts.side_overlay')
@include('admin.Layouts.sidebar')
@include('admin.Layouts.page_header')
@section('css')
<link href="{{ asset('css/custom-select-box.css') }}" rel="stylesheet">

<style>
    /* pc 既存の表示への影響をしないよう */
    @media screen and (min-width: 961px) {

        table.item-list-pc {
            display: block;
        }

        table.item-list-sp {
            display: none;
        }
    }

    /* sp 表示設定 */
    @media screen and (max-width: 960px) {
        .content {
            padding: 0 !important;
        }

        .content-heading {
            padding-left: 16px !important;

        }

        table.item-list-pc {
            display: none;
        }

        table.item-list-sp {
            display: block;
        }

        .block-content {
            padding: 20px 16px 1px;
        }

        table {
            margin-bottom: 0 !important;
        }

        .table.item-list-sp {
            width: 100%;
        }

        .table.item-list-sp tr {
            border-bottom: 1px solid #e4e7ed;
        }

        .table td,
        .table th {
            padding-left: 0;
            padding-right: 0;
            padding-top: 5px;
            padding-bottom: 5px;
        }

        .table td.search-btn {
            text-align: right;
        }

        .block-title {
            font-size: 1rem;
            font-weight: normal;
        }

        table.list-item {
            table-layout: fixed;
            width: 100%;
            padding-bottom: 1rem !important;
            background-color: transparent !important;
            margin-top: 8px !important;
        }

        table.list-item tr {
            padding-top: 0;
            padding-bottom: 0;
            border-bottom: none !important;
        }

        table.list-item td {
            width: 100% !important;
            padding-top: 0 !important;
            padding-bottom: 0;
        }

        table.list-item td.pencil-btn {
            text-align: right;
        }

        h3.block-title {
            font-size: 14px;
            font-weight: 400;
        }
    }

    input.form-control,
    select.form-control,
    button {
        margin-bottom: 8px;
    }
</style>
@endsection
@section('content')
<!-- Content -->
<div class="content">
    @include('admin.Layouts.flash_message')
    <!-- Default Table Style -->
    <h2 class="content-heading">ジャンル一覧</h2>

    <div class="block">
        <div class="block-content block-content-full">
            <form action="{{ route('admin.genre') }}" method="get" class="form-inline">
                <div class="row">
                    <div class="col-12">
                        <input class="form-control mr-sm-2" name="name" placeholder="名前" value="{{old('name', \Request::query('name'))}}">
                        <input class="form-control mr-sm-2" name="genre_cd" placeholder="ジャンルコード" value="{{old('genre_cd', \Request::query('genre_cd'))}}">
                        <input class="form-control mr-sm-2" name="app_cd" placeholder="利用サービス" value="{{old('app_cd', \Request::query('app_cd'))}}">
                        <input class="form-control mr-sm-2" name="path" placeholder="path" value="{{old('path', \Request::query('path'))}}">

                    </div>
                    <!-- </div>
                <div class="row"> -->
                    <div class="col-12 text-right">
                        <button type="submit" class="btn btn-alt-primary" value="search">検索する</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Table -->
    <div class="block">
        <div class="block-header block-header-default">
            <h3 class="block-title">総件数 : {{ $genres->total() }}件</h3>
            @can('inHouseGeneral-higher')
            <div class="block-options">
                <div class="block-options-item">
                    <a href="{{ route('admin.genre.add') }}" class="btn btn-sm btn-secondary" data-toggle="tooltip" title="Add">
                        <i class="fa fa-plus"></i>
                    </a>
                </div>
            </div>
            @endcan
        </div>
        <div class="block-content">
            <table class="table table-borderless table-vcenter item-list-sp">
                <tbody>
                    @foreach ($genres as $genre)

                    <tr>
                        <td>
                            <table class="table list-item">
                                <tr>
                                    <td>
                                        <b>#{{ sprintf("%03d", $genre->id) }}</b>
                                    </td>
                                    <td rowspan="4" class="pencil-btn">
                                        @can('inHouseGeneral-higher')
                                        <div class="btn-group">
                                            <a href="{{ route('admin.genre.edit', ['id' => $genre->id]) }}" class="btn btn-sm btn-secondary" data-toggle="tooltip" title="Edit">
                                                <i class="fa fa-pencil"></i>
                                            </a>
                                        </div>
                                        @endcan
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding-bottom:.6rem;">
                                        <b>{{ $genre->name }}</b>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        {{ $genre->genre_cd }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        {{ config('code.appCd')[strtolower($genre->app_cd)][$genre->app_cd] }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        {{ $genre->path }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        　
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>



            <table class="table table-borderless table-vcenter item-list-pc">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 50px;">@sortablelink('id','#')</th>
                        <th>@sortablelink('name','名前')</th>
                        <th>@sortablelink('genre_cd','ジャンルコード')</th>
                        <th>@sortablelink('app_cd','利用サービス')</th>
                        <th>@sortablelink('path','path')</th>
                        <th class="text-center" style="width: 100px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($genres as $genre)
                    <tr @if(!$genre->published)class="table-dark" @endif>
                        <th class="text-center" scope="row">{{ $genre->id }}</th>
                        <td>{{ $genre->name }}</td>
                        <td>{{ $genre->genre_cd }}</td>
                        <td>{{ config('code.appCd')[strtolower($genre->app_cd)][$genre->app_cd] }}</td>
                        <td>{{ $genre->path }}</td>
                        <td class="text-center">
                            @can('inHouseGeneral-higher')
                            <div class="btn-group">
                                <a href="{{ route('admin.genre.edit', ['id' => $genre->id]) }}" class="btn btn-sm btn-secondary" data-toggle="tooltip" title="Edit">
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
            {{ $genres->appends(\Request::except('page'))->render() }}
        </div>
    </div>
    <!-- END Table -->

</div>
<!-- END Content -->

@include('admin.Layouts.js_files')

@endsection

@include('admin.Layouts.footer')
