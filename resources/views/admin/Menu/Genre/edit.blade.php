@extends('admin.Layouts.base')
@include('admin.Layouts.head')

@include('admin.Layouts.side_overlay')
@include('admin.Layouts.sidebar')
@include('admin.Layouts.page_header')
<style>
    /* pc 既存の表示への影響をしないよう */
    @media screen and (min-width: 961px) {

        div.item-list-pc {
            display: block;
        }

        div.item-list-sp {
            display: none;
        }

        select {
            margin-bottom: 4px !important;
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

        div.item-list-pc {
            display: none;
        }

        div.item-list-sp {
            display: block;
        }

        /*
        table.item-list-sp a {
            padding: 8px 8px !important;
        } */

        .block-content {
            padding: 20px 0 1px !important;
        }

        table {
            margin-bottom: 0 !important;

        }

        .table.item-list {
            table-layout: fixed;
            width: 100%;
        }

        .table.item-list td {
            width: 100%;
        }

        .table td,
        .table th {
            /* padding-left: 10px;
            padding-right: 10px; */
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
            padding-bottom: 2rem !important;
            /* border-bottom: 1px solid #e4e7ed; */
            background-color: transparent !important;
            margin-bottom: 8px !important;
        }

        table.list-item tr {
            padding-top: 0;
            padding-bottom: 0;
        }

        table.list-item tr td:first-child {
            width: 10%;
            text-align: center;
        }

        table.list-item tr td:last-child {
            width: 90%;
            padding-bottom: 8px;
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

        #published {
            margin-bottom: 8px;
        }
    }
    .form-material>label {
        position: relative !important;
    }
</style>

@section('content')
<!-- Content -->
<div class="content">
    @include('admin.Layouts.flash_message')

    <!-- Default Table Style -->
    <h2 class="content-heading">{{ $menuName }} メニュージャンル設定</h2>
    <!-- Floating Labels -->
    <div class="block-header block-header-default col-md-12">
        <h3 class="block-title">総件数 : {{ $genreGroups->count() }}件</h3>
    </div>
    <div class="block col-md-12">
        <div class="block-content item-list-pc">
            <form action="{{ route('admin.menu.genre.edit', ['id' => $id]) }}" method="post" class="js-validation-material">
                @csrf
                <input type="hidden" name="redirect_to" value="{{ old('redirect_to', url()->previous()) }}">
                <input type="hidden" name="app_cd" id="app_cd" value="{{$appCd}}">
                <input type="hidden" name="id" id="id" value="{{$id}}">
                <input type="hidden" name="big_genre" id="big_genre" value="{{$bigGenre}}">
                <input type="hidden" name="menu_published" id="menu_published" value="{{$menuPublished}}">
                @if(!$genreGroups->isEmpty())
                <label for="genreType" style="margin-bottom:10px">ジャンル一覧/編集</label>
                @foreach($genreGroups as $i => $genreGroup)
                <input type="hidden" id="genre_group_id_{{$i}}" name="genre_group_id[]" value="{{$genreGroup['genreGroupId']}}">
                <div class="form-group row" id="genreType">
                    <div class="col-1">{{$i+1}}</div>
                    <div class="col-4">
                        <select class="form-control" id="middle_genre_{{$i}}" name="middle_genre[]" required>
                            <option value="">- ジャンル(中) -</option>
                            @foreach($middleGenres as $key => $value)
                            <option value="{{ strtolower($value->genre_cd) }}" @if(strtolower($value->genre_cd) == old('middle_genre.'.$i, $genreGroup['middleGenre'])) selected @endif>{{ $value->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-3">
                        <select class="form-control" id="small_genre_{{$i}}" name="small_genre[]" required>
                            <option value="">- ジャンル(小) -</option>
                            @foreach($genreGroup['smallGenres'] as $key => $value)
                            <option value="{{ strtolower($value->genre_cd) }}" @if(strtolower($value->genre_cd) == old('small_genre.'.$i, $genreGroup['smallGenre'])) selected @endif>{{ $value->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-3">
                        <select class="form-control" id="small2_genre_{{$i}}" name="small2_genre[]">
                            <option value="">- ジャンル(小)2 -</option>
                            @foreach($genreGroup['small2Genres'] as $key => $value)
                            <option value="{{ strtolower($value->genre_cd) }}" @if(strtolower($value->genre_cd) == old('small2_genre.'.$i, $genreGroup['genre']->genre_cd)) selected @endif>{{ $value->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-1" id="delete">
                        <a href="#" class="btn btn-sm btn-secondary" data-toggle="tooltip" title="Delete" id="{{$genreGroup['genreGroupId']}}">
                            <i class="fa fa-trash"></i>
                        </a>
                    </div>
                </div>
                @endforeach
                @else
                <div class="block-content block" data-nodata="1">
                    <div class="text-center">ジャンルの登録がありません</div>
                </div>
                @endif

                <!-- Start addForm -->
                <div id="menu_form" class="menu_form mb-3">
                </div>
                <!-- End Form -->

                <div class="form-group">
                    <div class="text-right">
                        <button type="button" id="add" class="btn btn-sm btn-secondary mr-2" data-toggle="tooltip" title="Add">
                            <i class="fa fa-plus"></i>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <div class="text-right">
                        <button type="submit" class="btn btn-alt-primary" value="update" data-save="1">保存</button>
                    </div>
                </div>
            </form>
        </div>
        <div class="block-content item-list-sp">
            <form action="{{ route('admin.menu.genre.edit', ['id' => $id]) }}" method="post" class="js-validation-material">
                @csrf
                <input type="hidden" name="redirect_to" value="{{ old('redirect_to', url()->previous()) }}">
                <input type="hidden" name="app_cd" id="app_cd" value="{{$appCd}}">
                <input type="hidden" name="id" id="id" value="{{$id}}">
                <input type="hidden" name="big_genre" id="big_genre" value="{{$bigGenre}}">
                <input type="hidden" name="menu_published" id="menu_published-sp" value="{{$menuPublished}}">
                @if(!$genreGroups->isEmpty())
                <label for="genreType" style="margin-bottom:10px">ジャンル一覧/編集</label>
                <table class="item-list">
                    @foreach($genreGroups as $i => $genreGroup)
                    <tr>
                        <td>
                            <input type="hidden" id="genre_group_id_{{$i}}" name="genre_group_id[]" value="{{$genreGroup['genreGroupId']}}">
                            <table style="margin-bottom:8px" class="list-item">
                                <tr>
                                    <td rowspan="4" style="vertical-align: top;">
                                        {{$i+1}}
                                    </td>
                                    <td>
                                        <select class="form-control" id="middle_genre_{{$i}}" name="middle_genre[]" required>
                                            <option value="">- ジャンル(中) -</option>
                                            @foreach($middleGenres as $key => $value)
                                            <option value="{{ strtolower($value->genre_cd) }}" @if(strtolower($value->genre_cd) == old('middle_genre.'.$i, $genreGroup['middleGenre'])) selected @endif>{{ $value->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <select class="form-control" id="small_genre_{{$i}}" name="small_genre[]" required>
                                            <option value="">- ジャンル(小) -</option>
                                            @foreach($genreGroup['smallGenres'] as $key => $value)
                                            <option value="{{ strtolower($value->genre_cd) }}" @if(strtolower($value->genre_cd) == old('small_genre.'.$i, $genreGroup['smallGenre'])) selected @endif>{{ $value->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <select class="form-control" id="small2_genre_{{$i}}" name="small2_genre[]">
                                            <option value="">- ジャンル(小)2 -</option>
                                            @foreach($genreGroup['small2Genres'] as $key => $value)
                                            <option value="{{ strtolower($value->genre_cd) }}" @if(strtolower($value->genre_cd) == old('small2_genre.'.$i, $genreGroup['genre']->genre_cd)) selected @endif>{{ $value->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2" style="text-align:right" id="delete">

                                        <!-- <div colspan="2" style="text-align:right" id="delete"> -->
                                        <a href="#" class="btn btn-sm btn-secondary" data-toggle="tooltip" title="Delete" id="{{$genreGroup['genreGroupId']}}">
                                            <i class="fa fa-trash"></i>
                                        </a>
                                        <!-- </div> -->
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    @endforeach
                </table>
                @else
                <div class="block-content block" data-nodata="1">
                    <div class="text-center">ジャンルの登録がありません</div>
                </div>
                @endif

                <!-- Start addForm -->
                <div id="menu_form" class="menu_form mb-3">
                </div>
                <!-- End Form -->

                <div class="form-group">
                    <div class="text-right">
                        <button type="button" id="add" class="btn btn-sm btn-secondary mr-2" data-toggle="tooltip" title="Add">
                            <i class="fa fa-plus"></i>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <div class="text-right">
                        <button type="submit" class="btn btn-alt-primary" value="update" data-save="1">保存</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <button type="button" class="btn btn-secondary" onclick="location.href='{{ session('menuGenreRedirectTo', route('admin.menu')) }}'">戻る</button>

</div>
<!-- END Content -->
@include('admin.Layouts.js_files')

<script src="{{ asset('vendor/admin/assets/js/genre.js').'?'.time() }}"></script>
@endsection

@include('admin.Layouts.footer')

@section('js')
<script>
    $(document).ready(function() {
        let count = {{(!$genreGroups -> isEmpty()) ? count($genreGroups): 0}};
        @if($genreGroups -> isEmpty())
        $('button[data-save=1]').hide();
        @endif

        load_field_small2();

        function load_field_small2() {
            const small_genres = $("[id*='small_genre_']");
            const small2_genres = $("[id*='small2_genre_']");
            if (small2_genres.length === 0) {
                return;
            }
            for (let i = 0; i < small2_genres.length; ++i) {
                let selected = $("option:selected", small2_genres[i]).val();
                if (selected.length === 0) {
                    $(small_genres[i]).trigger('change');
                }
            }
        }

        function dynamic_field(number) {
            html = '<div class="add_form-group">';
            html += '<input type="hidden" id="genre_group_id_' + number + '" name="genre_group_id[]" value="">';
            html += '<div class="form-group">';
            html += '<div class="form-material col-12">';
            html += '<label for="middle_genre">ジャンル(中)<span class="badge bg-danger ml-2 text-white">必須</span></label>';
            html += '<select style="margin-top:8px;" class="form-control" id="middle_genre_' + number + '" name="middle_genre[]" required>';
            html += '<option value="">-</option>';
            html += '@foreach($middleGenres as $key => $value)';
            html += '<option value="{{ strtolower($value->genre_cd) }}">{{ $value->name }}</option>';
            html += '@endforeach';
            html += '</select>';
            html += '</div>';
            html += '</div>';
            html += '<div class="form-group">';
            html += '<div class="form-material col-12">';
            html += '<label for="small_genre">ジャンル(小)<span class="badge bg-danger ml-2 text-white">必須</span></label>';
            html += '<select style="margin-top:8px;" class="form-control" id="small_genre_' + number + '" name="small_genre[]" required>';
            html += '<option value="">-</option>';
            html += '</select>';
            html += '</div>';
            html += '</div>';
            html += '<div class="form-group">';
            html += '<div class="form-material col-12">';
            html += '<label for="small2_genre">ジャンル(小)2</label>';
            html += '<select style="margin-top:8px;" class="form-control" id="small2_genre_' + number + '" name="small2_genre[]">';
            html += '<option value="">-</option>';
            html += '</select>';
            html += '</div>';
            html += '</div>';

            if (number > 1) {
                html += '<div class="form-group">';
                html += '<div class="text-right">';
                html += '<button type="button" class="btn btn-sm btn-secondary remove-form mr-2" data-toggle="tooltip" data_rase="' + number + '" title="Erase">';
                html += '<i class="fa fa-minus"></i>';
                html += '</button>';
                html += '</div>';
                html += '</div>';
                html += '</div>';

                $('#menu_form').append(html);
            } else {
                $('[data-nodata=1]').hide();
                $('button[data-save=1]').show();
                html += '</div>';
                $('#menu_form').html(html);
            }
        }

        $(document).on('click', '#add', function() {
            count++;
            dynamic_field(count);
        });

        $(document).on('click', '.remove-form', function() {
            $(this).parents('.add_form-group').remove();
        });
    });

    /* ID被りを排除 */
    function update_ids() {
        if ($('div.item-list-pc').css('display') == 'none') {
            $('div.item-list-pc').find('*').each(function(i,e) {
                if ($(this).attr('id')) {
                    // console.log($(this).attr('id'));
                    $(this).attr('id', $(this).attr('id').split('_--_')[0]+'_--_' + i);
                }
            });
            $('div.item-list-sp').find('*').each(function(i,e) {
                if ($(this).attr('id')) {
                    // console.log($(this).attr('id'));
                    $(this).attr('id', $(this).attr('id').split('_--_')[0]);
                }
            });
        } else {
            $('div.item-list-pc').find('*').each(function(i,e) {
                if ($(this).attr('id')) {
                    // console.log($(this).attr('id'));
                    $(this).attr('id', $(this).attr('id').split('_--_')[0]);
                }
            });
            $('div.item-list-sp').find('*').each(function(i,e) {
                if ($(this).attr('id')) {
                    // console.log($(this).attr('id'));
                    $(this).attr('id', $(this).attr('id').split('_--_')[0]+'_--_' + i);
                }
            });
        }

        // for debug
        console.log('PC!!!!!!!!');
        $('div.item-list-pc').find('*').each(function(i,e) {
            if ($(this).attr('id')) {
                console.log($(this).attr('id'));
            }
        });
        console.log('SP!!!!!!!!');
        $('div.item-list-sp').find('*').each(function(i,e) {
            if ($(this).attr('id')) {
                console.log($(this).attr('id'));
            }
        });
    }
    $(window).resize(function () {
        update_ids();
    });
    $(document).ready(function() {
        update_ids();
    });
</script>
@endsection
