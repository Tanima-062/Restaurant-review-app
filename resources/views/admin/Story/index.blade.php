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
        <h2 class="content-heading">ストーリーマスタ</h2>
        <div class="block">
            <div class="block-content block-content-full">
                <form action="{{ route('admin.story') }}" method="get" class="form-inline">
                    <input class="form-control mr-sm-2" name="id" placeholder="ID" value="{{ old('id', \Request::query('id')) }}">
                    <input class="form-control mr-sm-2" name="name" placeholder="記事タイトル"  value="{{ old('name', \Request::query('name')) }}">
                    <input class="form-control mr-sm-2" name="url" placeholder="URL"  value="{{ old('url', \Request::query('url')) }}">
                    <select class="form-control app_cd mr-sm-2" id="app_cd" name="app_cd">
                        <option value="">利用サービス</option>
                        @foreach($app_cd as $code => $content)
                            @php if(strlen($code) > 2) continue; @endphp
                            <option value="{{ strtoupper($code) }}"
                                {{ old('app_cd', \Request::query('app_cd')) == strtoupper($code) ? 'selected' : '' }}
                            >{{ $content[strtoupper($code)] }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-alt-primary" name="type" value="search">検索する</button>&nbsp;
                </form>
            </div>
        </div>

        <!-- Table -->
        <div class="block">
            <div class="block-header block-header-default">
                <h3 class="block-title">総件数 : {{ $stories->total() }}件</h3>
                <div class="block-options">
                    <div class="block-options-item">
                        <a href="{{ route('admin.story.add') }}" class="btn btn-sm btn-secondary" data-toggle="tooltip" title="Add">
                            <i class="fa fa-plus"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="block-content">
                <table class="table table-borderless table-vcenter">
                    <thead>
                    <tr>
                        <th class="text-center" style="width: 50px;">@sortablelink('id','#')</th>
                        <th style="max-width: 150px;">@sortablelink('title','画像')</th>
                        <th>@sortablelink('title','記事タイトル')</th>
                        <th >@sortablelink('url','URL')</th>
                        <th style="width: 200px;">@sortablelink('created_at','作成日時')</th>
                        <th style="width: 200px;">@sortablelink('updated_at','更新日時')</th>
                        <th class="text-center" style="width: 100px;"></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($stories as $story)
                        <tr @if(!$story->published)class="table-dark" @endif>
                            <th class="text-center" scope="row">{{ $story->id }}</th>
                            <td><img src="{{ optional($story->image)->url }}" alt="Story Image" width="100"></td>
                            <td>{{ $story->title }}</td>
                            <td>{{ $story->guide_url }}</td>
                            <td>{{ $story->created_at }}</td>
                            <td>{{ $story->updated_at }}</td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <a href="{{ route('admin.story.edit', ['id' => $story->id]) }}" class="btn btn-sm btn-secondary" data-toggle="tooltip" title="Edit">
                                        <i class="fa fa-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-secondary delete-confirm"
                                            data-id="{{ $story->id }}" data-title="{{ $story->title }}"
                                            data-toggle="tooltip" title="Delete"
                                    >
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="block-content block-content-full block-content-sm bg-body-light font-size-md">
                {{ $stories->appends(\Request::except('page'))->render() }}
            </div>
        </div>
        <!-- END Table -->

    </div>
    <!-- END Content -->

@endsection

@section('js')
    <script>
        $(function (){
            $(document).on('click', '.delete-confirm', function() {
                const story_id = $(this).data('id');
                const story_title = $(this).data('title');
                const deleteUrl = '/admin/story/' + story_id + '/delete';

                if (confirm(story_title + 'を削除しますか？')) {
                    $.ajax({
                        url: deleteUrl,
                        type: 'POST',
                        dataType: 'json',
                        data: {'_token': $('input[name="_token"]').val()},
                    })
                    .done(function(result) {
                        alert('削除しました');
                        location.reload();
                    })
                    .fail(function() {
                        alert('削除に失敗しました');
                        location.reload();
                    });
                }
            });
        })
    </script>
@endsection

@include('admin.Layouts.footer')
