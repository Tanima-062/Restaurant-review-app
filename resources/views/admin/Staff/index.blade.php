@extends('admin.Layouts.base')
@include('admin.Layouts.head')

@include('admin.Layouts.side_overlay')
@include('admin.Layouts.sidebar')
@include('admin.Layouts.page_header')
@section('css')
<style>
    /* pc 既存の表示への影響をしないよう */
    @media screen and (min-width: 961px) {

        table.item-list-pc {
            display: inline-table;
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
            display: inline-table;
        }

        .block-content {
            padding: 20px 16px 1px;
        }

        table {
            margin-bottom: 0 !important;
        }

        .table.item-list-sp {
            table-layout: fixed;
            width: 100%;
        }

        table.item-list-sp a {
            /* padding: 8px 0 !important; */
            padding-left: 7.6px !important;
            padding-right: 7.6px !important;
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
            border-bottom: 1px solid #e4e7ed;
            background-color: transparent !important;
        }

        table.list-item tr {
            padding-top: 0;
            padding-bottom: 0;
        }

        table.list-item td {
            /* width: auto !important; */
            padding-top: 0;
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
    <h2 class="content-heading">スタッフ一覧</h2>

    <div class="block">
        <div class="block-content block-content-full">
            <form action="{{ route('admin.staff') }}" method="get" class="form-inline">
                <div class="row">
                    <div class="col-12">
                        <input class="form-control mr-sm-2" name="id" placeholder="ID" value="{{old('id', \Request::query('id'))}}">
                        <input class="form-control mr-sm-2" name="name" placeholder="名前" value="{{old('name', \Request::query('name'))}}">
                        <input class="form-control mr-sm-2" name="username" placeholder="ログインID" value="{{old('username', \Request::query('username'))}}">
                        <select class="form-control mr-sm-2" name="staff_authority_id">
                            <option value="">権限</option>
                            @foreach($staffAuthorities as $staffAuthority)
                            <option value="{{$staffAuthority->id}}" @if(old('staff_authority_id', \Request::query('staff_authority_id'))==$staffAuthority->id) selected @endif>{{$staffAuthority->name}}</option>
                            @endforeach
                        </select>
                    </div>
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
            <h3 class="block-title">総件数 : {{ $staffs->total() }}件</h3>
            <div class="block-options">
                <div class="block-options-item">
                    <a href="{{ route('admin.staff.add') }}" class="btn btn-sm btn-secondary" data-toggle="tooltip" title="Add">
                        <i class="fa fa-plus"></i>
                    </a>
                </div>
            </div>
        </div>
        <div class="block-content">
            <table class="table table-borderless table-vcenter item-list-pc">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 50px;">@sortablelink('id','#')</th>
                        <th>@sortablelink('name','名前')</th>
                        <th>@sortablelink('username','ログインID')</th>
                        <th class="d-none d-sm-table-cell" style="width: 15%;">@sortablelink('staff_authority_id','権限')</th>
                        <th>@sortablelink('last_login_at','最終ログイン日時')</th>
                        <th class="text-center" style="width: 100px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($staffs as $staff)
                    <tr @if(!$staff->published)class="table-dark" @endif>
                        <th class="text-center" scope="row">{{ $staff->id }}</th>
                        <td>{{ $staff->name }}</td>
                        <td>{{ $staff->username }}</td>
                        <td class="d-none d-sm-table-cell">
                            <span class="badge badge-{{ badge_color($staff->staffAuthority->id, $staffAuthorityCount) }}">{{ $staff->staffAuthority->name }}</span>
                        </td>
                        <td>{{ $staff->last_login_at }}</td>
                        <td class="text-center">
                            <div class="btn-group">
                                <a href="{{ route('admin.staff.edit', ['id' => $staff->id]) }}" class="btn btn-sm btn-secondary" data-toggle="tooltip" title="Edit">
                                    <i class="fa fa-pencil"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>


            <table class="table table-borderless table-vcenter item-list-sp">
                <tbody>
                    @foreach ($staffs as $staff)
                    <tr>
                        <td>
                            <table class="table list-item">
                                <tr>
                                    <td>
                                        <b>#{{ sprintf("%03d", $staff->id) }}</b>
                                    </td>
                                    <td rowspan="4" class="pencil-btn">
                                        <div class="btn-group">
                                            <a href="{{ route('admin.staff.edit', ['id' => $staff->id]) }}" class="btn btn-sm btn-secondary" data-toggle="tooltip" title="Edit">
                                                <i class="fa fa-pencil"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        {{ $staff->name }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        {{ $staff->username }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        {{ $staff->last_login_at }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <span class="badge badge-{{ badge_color($staff->staffAuthority->id, $staffAuthorityCount) }}">{{ $staff->staffAuthority->name }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        　
                                    </td>
                                </tr>
                                <!-- <tr>
                                        <td>　
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="{{ route('admin.staff.edit', ['id' => $staff->id]) }}" class="btn btn-sm btn-secondary" data-toggle="tooltip" title="Edit">
                                                    <i class="fa fa-pencil"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr> -->
                            </table>
                            <!-- <tr @if(!$staff->published)class="table-dark" @endif>
                            <th class="text-center" scope="row">{{ $staff->id }}</th>
                            <td>{{ $staff->name }}</td>
                            <td>{{ $staff->username }}</td>
                            <td class="d-none d-sm-table-cell">
                                <span class="badge badge-{{ badge_color($staff->staffAuthority->id, $staffAuthorityCount) }}">{{ $staff->staffAuthority->name }}</span>
                            </td>
                            <td>{{ $staff->last_login_at }}</td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <a href="{{ route('admin.staff.edit', ['id' => $staff->id]) }}" class="btn btn-sm btn-secondary" data-toggle="tooltip" title="Edit">
                                        <i class="fa fa-pencil"></i>
                                    </a>
                                </div>
                            </td>
                        </tr> -->

                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>


        </div>
        <div class="block-content block-content-full block-content-sm bg-body-light font-size-md">
            {{ $staffs->appends(\Request::except('page'))->render() }}
        </div>
    </div>
    <!-- END Table -->

</div>
<!-- END Content -->

@include('admin.Layouts.js_files')

@endsection

@include('admin.Layouts.footer')
