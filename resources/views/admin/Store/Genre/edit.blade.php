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

        table.item-list-pc,
        div.item-list-pc {
            display: block;
        }

        table.item-list-sp,
        div.item-list-sp {
            display: none;
        }

        div.content-item-list-pc {
            max-width: 1720px;
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

        table {
            table-layout: fixed;
            width: 100%;
            margin-bottom: 0 !important;
        }

        table.item-list-pc,
        div.item-list-pc {
            display: none;
        }

        table.item-list-sp,
        div.item-list-sp {
            display: block;
        }

        div.item-list-sp {
            padding-right: 0 !important;
            padding-left: 0 !important;
        }

        table.item-list-sp a {
            /* padding: 8px 0 !important; */
            padding-left: 4px !important;
            padding-right: 4px !important;
        }

        .block-content {
            padding: 20px 8px 1px;
        }

        .block-header {
            padding: 14px 16px !important;
        }

        .list-content {
            padding-top: 0;
            padding-left: 16px;
            padding-right: 16px;
        }

        .table.item-list-sp {
            table-layout: fixed;
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
            width: 100%;
            text-align: left !important;
        }

        .table.item-list-sp label {
            text-align: left !important;
            display: -ms-flexbox;
            display: flex;
            -ms-flex-align: left;
            align-items: center;
            -ms-flex-pack: start;
            justify-content: left;
            margin-bottom: 8px;
        }

        .table.item-list-sp.reservationList tr {
            border-bottom: 1px solid #e4e7ed;
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
            background-color: transparent !important;
            margin-bottom: 8px !important;
            padding-bottom: 2rem !important;
        }

        table.list-item tr {
            padding-top: 0 !important;
            padding-bottom: 0;
            border-bottom: none !important;
        }

        table.list-item td {
            width: auto !important;
            /* width: 100% !important; */
            text-align: left !important;
            padding-top: 0;
            padding-bottom: 0;
        }

        table.list-item td.pencil-btn {
            text-align: right !important;
        }

        h3.block-title {
            font-size: 14px;
            font-weight: 400;
        }

        #published {
            margin-bottom: 8px;
        }

    }

    @media screen and (max-width: 425px) {
        .cond-date {
            /* width: 140px; */
            font-size: 1em !important;
        }
    }

    @media screen and (max-width: 375px) {
        .cond-date {
            width: 140px !important;
            /* font-size: .8em !important; */
            letter-spacing: -0.04em;
        }
    }

    @media screen and (max-width: 320px) {
        .cond-date {
            width: 136px !important;
            /* font-size: .9em !important; */
            letter-spacing: -0.05em;
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
<!-- Start Content -->
<div class="content">
    @include('admin.Layouts.flash_message')

    <!-- Default Table Style -->
    <h2 class="content-heading">{{ $storeName }} 店舗ジャンル設定</h2>
    <!-- Start detailed -->
    @include('admin.Store.Genre.detailed_edit')
    <!-- End detailed -->

    <!-- Start cooking -->
    @include('admin.Store.Genre.cooking_edit')
    <!-- End cooking -->

    <!-- End Content -->
</div>

@include('admin.Layouts.js_files')

<script src="{{ asset('vendor/admin/assets/js/genre.js').'?'.time() }}"></script>
@endsection

@include('admin.Layouts.footer')

@section('js')
<script>
    $(document).ready(function() {
        let count_detailed = {{(!$detailedGenreGroups->isEmpty()) ? count($detailedGenreGroups): 0}};
        let count_cooking = {{(!$cookingGenreGroups->isEmpty()) ? count($cookingGenreGroups): 0}};
        @if($detailedGenreGroups -> isEmpty())
        $('button[data-save=detailed]').hide();
        @endif
        @if($cookingGenreGroups -> isEmpty())
        $('button[data-save=cooking]').hide();
        @endif

        function dynamic_field_detailed(number) {
            if ($('div.item-list-pc').css('display') == 'block' || $('div.item-list-pc').css('display') != 'none') {
                console.log('dynamic_field_detailed!! PC' + number);
                html = '<div class="add_form-group">';
                html += '<input type="hidden" id="genre_group_id_' + number + '" name="genre_group_id[]" value="">';
                html += '<div class="form-group">';
                html += '<div class="form-material col-12">';
                html += '<label for="middle_genre">ジャンル(中)<span class="badge bg-danger ml-2 text-white">必須</span></label>';
                html += '<select class="form-control" id="middle_genre_' + number + '" name="middle_genre[]" required>';
                html += '<option value="">-</option>';
                html += '@foreach($detailedMiddleGenres as $key => $value)';
                html += '<option value="{{ strtolower($value->genre_cd) }}">{{ $value->name }}</option>';
                html += '@endforeach';
                html += '</select>';
                html += '</div>';
                html += '</div>';
                html += '<div class="form-group">';
                html += '<div class="form-material col-12">';
                html += '<label for="small_genre">ジャンル(小)<span class="badge bg-danger ml-2 text-white">必須</span></label>';
                html += '<select class="form-control" id="small_genre_' + number + '" name="small_genre[]" required>';
                html += '<option value="">-</option>';
                html += '</select>';
                html += '</div>';
                html += '</div>';

                if (number > 1) {
                    html += '<div class="form-group">';
                    html += '<div class="float-left">&nbsp;</div>';
                    html += '<div class="row" id="genreType">';
                    html += '<div class="col-11"></div>';
                    html += '<div class="col-1">';
                    html += '<button type="button" class="btn btn-sm btn-secondary remove-form" data-toggle="tooltip" data_rase="' + number + '" title="Erase">';
                    html += '<i class="fa fa-minus"></i>';
                    html += '</button>';
                    html += '</div>';
                    html += '</div>';
                    html += '</div>';
                    html += '</div>';
                    $('#store_detailed_form').append(html);
                } else {
                    $('[data-nodata=detailed]').hide();
                    $('button[data-save=detailed]').show();
                    html += '</div>';
                    $('#store_detailed_form').html(html);
                }
            } else {
                console.log('dynamic_field_detailed!! SP' + number);

                html =
                `<div class="add_form-group">
                    <input type="hidden" id="genre_group_id_` + number + `" name="genre_group_id[]" value="">
                    <div class="form-group">
                        <table>
                            <tr>
                                <td rowspan="2" style="width: 24px; padding: 8px; vertical-align: top;">
                                </td>
                                <td>
                                    <div class="form-material">
                                        <label for="middle_genre">ジャンル(中)<span class="badge bg-danger ml-2 text-white">必須</span></label>
                                        <select class="form-control" id="middle_genre_` + number + `" name="middle_genre[]" required>
                                        <option value="">-</option>
                                        @foreach($detailedMiddleGenres as $key => $value)
                                        <option value="{{ strtolower($value->genre_cd) }}">{{ $value->name }}</option>
                                        @endforeach
                                        </select>
                                    </div>
                                </td>
                                <td rowspan="2" style="width:48px; padding:8px;">
                                    <div id="genreType">
                                        <div>
                                            <button type="button" class="btn btn-sm btn-secondary remove-form" data-toggle="tooltip" data_rase="` + number + `" title="Erase">
                                                <i class="fa fa-minus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-material">
                                        <label for="small_genre">ジャンル(小)<span class="badge bg-danger ml-2 text-white">必須</span></label>
                                        <select class="form-control" id="small_genre_` + number + `" name="small_genre[]" required>
                                            <option value="">-</option>
                                        </select>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>`;
                $('#store_detailed_form').append(html);

            }

        }

        function dynamic_field_cooking(number) {
            if ($('div.item-list-pc').css('display') == 'block' || $('div.item-list-pc').css('display') != 'none') {
                html = '<div class="add_form-group">';
                html += '<input type="hidden" id="cooking_genre_group_id_' + number + '" name="cooking_genre_group_id[]" value="">';
                html += '<div class="form-group">';
                html += '<div class="form-material col-12">';
                html += '<label for="is_delegate">通常/メイン<span class="badge bg-danger ml-2 text-white">必須</span></label>';
                html += '<select class="form-control" id="cooking_delegate_' + number + '" name="cooking_delegate[]" required>';
                html += '<option value="">-</option>';
                html += '@foreach(config("const.genre.delegate") as $parentKey => $delegate)';
                html += '@foreach($delegate as $childKey => $word)';
                html += '<option value="{{ $parentKey }}">{{ $word }}</option>';
                html += '@endforeach';
                html += '@endforeach';
                html += '</select>';
                html += '</div>';
                html += '</div>';
                html += '<div class="form-group">';
                html += '<div class="form-material col-12">';
                html += '<label for="middle_genre">ジャンル(中)<span class="badge bg-danger ml-2 text-white">必須</span></label>';
                html += '<select class="form-control" id="cooking_middle_genre_' + number + '" name="cooking_middle_genre[]" required>';
                html += '<option value="">-</option>';
                html += '@foreach($cookingMiddleGenres as $key => $value)';
                html += '<option value="{{ strtolower($value->genre_cd) }}">{{ $value->name }}</option>';
                html += '@endforeach';
                html += '</select>';
                html += '</div>';
                html += '</div>';
                html += '<div class="form-group">';
                html += '<div class="form-material col-12">';
                html += '<label for="small_genre">ジャンル(小)<span class="badge bg-danger ml-2 text-white">必須</span></label>';
                html += '<select class="form-control" id="cooking_small_genre_' + number + '" name="cooking_small_genre[]" required>';
                html += '<option value="">-</option>';
                html += '</select>';
                html += '</div>';
                html += '</div>';
                html += '<div class="form-group">';
                html += '<div class="form-material col-12">';
                html += '<label for="small2_genre">ジャンル(小)2</label>';
                html += '<select class="form-control" id="cooking_small2_genre_' + number + '" name="cooking_small2_genre[]">';
                html += '<option value="">-</option>';
                html += '</select>';
                html += '</div>';
                html += '</div>';

                if (number > 1) {
                    html += '<div class="form-group">';
                    html += '<div class="float-left">&nbsp;</div>';
                    html += '<div class="row" id="genreType">';
                    html += '<div class="col-11"></div>';
                    html += '<div class="col-1">';
                    html += '<button type="button" class="btn btn-sm btn-secondary remove-form" data-toggle="tooltip" data_rase="' + number + '" title="Erase">';
                    html += '<i class="fa fa-minus"></i>';
                    html += '</button>';
                    html += '</div>';
                    html += '</div>';
                    html += '</div>';
                    html += '</div>';
                    $('#store_cooking_form').append(html);
                } else {
                    $('[data-nodata="cooking"]').hide();
                    $('button[data-save=cooking]').show();
                    html += '</div>';
                    $('#store_cooking_form').html(html);
                }
             } else {
                html =
                `<div class="add_form-group">
                    <input type="hidden" id="cooking_genre_group_id_` + number + `" name="cooking_genre_group_id[]" value="">
                    <div class="form-group">
                        <table>
                            <tr>
                                <td rowspan="4" style="width: 24px; padding: 8px; vertical-align: top;">
                                </td>
                                <td style="padding-right: 8px;">
                                    <div class="form-material">
                                    <label for="is_delegate">通常/メイン<span class="badge bg-danger ml-2 text-white">必須</span></label>
                                    <select class="form-control" id="cooking_delegate_` + number + `" name="cooking_delegate[]" required>
                                    <option value="">-</option>
                                    @foreach(config("const.genre.delegate") as $parentKey => $delegate)
                                    @foreach($delegate as $childKey => $word)
                                    <option value="{{ $parentKey }}">{{ $word }}</option>
                                    @endforeach
                                    @endforeach
                                    </select>
                                    </div>
                                </td>`;
                                if (number > 1) {
                                    html += `
                                <td rowspan="4" style="width:48px; padding:8px;">
                                    <div id="genreType">
                                        <div>
                                            <button type="button" class="btn btn-sm btn-secondary remove-form" data-toggle="tooltip" data_rase="` + number + `" title="Erase">
                                                <i class="fa fa-minus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </td>`;
                                } else {

                                }
                                html += `</tr>
                            <tr>
                                <td>
                                    <div class="form-material">
                                        <label for="middle_genre">ジャンル(中)<span class="badge bg-danger ml-2 text-white">必須</span></label>
                                        <select class="form-control" id="cooking_middle_genre_` + number + `" name="cooking_middle_genre[]" required>
                                        <option value="">-</option>
                                        @foreach($cookingMiddleGenres as $key => $value)
                                        <option value="{{ strtolower($value->genre_cd) }}">{{ $value->name }}</option>
                                        @endforeach
                                        </select>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-material">
                                        <label for="small_genre">ジャンル(小)<span class="badge bg-danger ml-2 text-white">必須</span></label>
                                        <select class="form-control" id="cooking_small_genre_` + number + `" name="cooking_small_genre[]" required>
                                        <option value="">-</option>
                                        </select>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-material">
                                        <label for="small2_genre">ジャンル(小)2</label>
                                        <select class="form-control" id="cooking_small2_genre_` + number + `" name="cooking_small2_genre[]">
                                        <option value="">-</option>
                                        </select>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>`;
                if (number > 1) {
                    $('#store_cooking_form').append(html);
                } else {
                    $('[data-nodata="cooking"]').hide();
                    $('button[data-save=cooking]').show();
                    html += '</div>';
                    $('#store_cooking_form').html(html);
                }
            }
        }

        $(document).on('click', '#add', function() {
            console.log('Click#add!!!');
            let genre = $(this).data('genre');
            eval("count_" + genre + "++;")
            eval("dynamic_field_" + genre + "(count_" + genre + ");")
        });

        $(document).on('click', '.remove-form', function() {
            $(this).parents('.add_form-group').remove();
        });

        update_ids();

    });



    /* ID被りを排除 */
    function update_ids() {
        if ($('div.item-list-pc').css('display') == 'none') {
            $('div.item-list-pc').find('*').each(function(i, e) {
                if ($(this).attr('id')) {
                    // console.log($(this).attr('id'));
                    $(this).attr('id', $(this).attr('id').split('_--_')[0] + '_--_' + i);
                }
            });
            $('div.item-list-sp').find('*').each(function(i, e) {
                if ($(this).attr('id')) {
                    // console.log($(this).attr('id'));
                    $(this).attr('id', $(this).attr('id').split('_--_')[0]);
                }
            });
        } else {
            $('div.item-list-pc').find('*').each(function(i, e) {
                if ($(this).attr('id')) {
                    // console.log($(this).attr('id'));
                    $(this).attr('id', $(this).attr('id').split('_--_')[0]);
                }
            });
            $('div.item-list-sp').find('*').each(function(i, e) {
                if ($(this).attr('id')) {
                    // console.log($(this).attr('id'));
                    $(this).attr('id', $(this).attr('id').split('_--_')[0] + '_--_' + i);
                }
            });
        }

        // for debug
        console.log('PC!!!!!!!!');
        $('div.item-list-pc').find('*').each(function(i, e) {
            if ($(this).attr('id')) {
                console.log($(this).attr('id'));
            }
        });
        console.log('SP!!!!!!!!');
        $('div.item-list-sp').find('*').each(function(i, e) {
            if ($(this).attr('id')) {
                console.log($(this).attr('id'));
            }
        });
    }
    $(window).resize(function() {
        update_ids();
    });
</script>
@endsection
