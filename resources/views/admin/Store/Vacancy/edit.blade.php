@extends('admin.Layouts.base')
@include('admin.Layouts.head')

@include('admin.Layouts.side_overlay')
@include('admin.Layouts.sidebar')
@include('admin.Layouts.page_header')

@section('css')
<link href="{{ asset('css/custom-select-box.css') }}" rel="stylesheet">
<style>
    .form-material>div .form-control {
        padding-left: 0;
        padding-right: 0;
        border-color: transparent;
        border-radius: 0;
        background-color: transparent;
        box-shadow: 0 1px 0 #d4dae3;
        transition: box-shadow .3s ease-out;
    }

    .content-heading {
        border-bottom: 0;
    }

    .date {
        font-size: 24px;
        margin-bottom: 40px;
        vertical-align: bottom;
    }

    .subTitle {
        line-height: 50px;
    }

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
    }

    /* sp 表示設定 */
    @media screen and (max-width: 960px) {

        table.item-list-pc,
        div.item-list-pc {
            display: none;
        }

        table.item-list-sp,
        div.item-list-sp {
            display: block;
        }

        main#main-container {
            min-height: initial !important;
            min-height: auto !important;
        }

        .checkbox-weekday {
            margin-top: 8px !important;
            margin-right: 24px !important;
        }

        .date {
            margin-bottom: 0 !important;
        }

        .block-content {
            padding: 20px 0 1px;
        }

        table th,
        td {
            text-align: center;
        }

        .cellInterval {
            padding: 4px 4px !important;
        }
/*
        select {
            -moz-appearance: menulist;
            -webkit-appearance: menulist;
            appearance: menulist;
        }*/

        /*IE用*/
        /*select::-ms-expand {
            display: block;
        } */
    }
</style>
@endsection

@section('content')

<!-- Content -->
<div class="content pb-3 item-list-pc">
    @include('admin.Layouts.flash_message')

    <!-- Default Table Style -->
    <form id="commit" action="{{ route('admin.store.vacancy.edit',['id'=>$store->id]) }}" method="post">
        @csrf
        <div class="d-flex col-md-9 justify-content-between">
            <h2 class="content-heading">{{ $store->name }}</h2>
        </div>
        <div class="d-flex col-md-9 justify-content-between">

            <div class="date subTitle">{{ $date }}</div>
            <div class="form-material">

                <label /div for="interval">時間間隔</label>
                <select class="form-control" id="intervalTime" name="intervalTime">
                    <option value="">選択してください</option>
                    @foreach(config('const.storeVacancy.interval') as $key => $value)
                    <option value="{{ $value['value'] }}" @if(old('intervalTime')){{ old('intervalTime') == $value['value'] ? 'selected' : '' }}@elseif(!empty($intervalTime)){{ $intervalTime === $value['value'] ? 'selected' : '' }}@endif>{{ $value['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="subTitle">
                <button type="button" class="btn btn-secondary" onclick='location.href="{{ route('admin.store.vacancy', ['id' => $store->id]) }}"'>空席カレンダーへ戻る</button>
            </div>

        </div>
        <div class="block col-md-9">
            <input type="hidden" id="date" name="date" value="{{$date}}">
            <input type="hidden" id="regexp" name="regexp" value="{{$regexp}}">
            <input type="hidden" id="intervalTime" name="intervalTime" value="{{$intervalTime}}">

            <div class="block-content">
                <table class="table table-borderless table-vcenter interval">
                    <thead>
                        <tr>
                            <th>時間</th>
                            <th>予約済み数(来店人数)</th>
                            <th>在庫数</th>
                            <th>有効/無効</th>

                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($intervals as $i => $vacancy)
                        @if($vacancy['isOpen'] === 1)
                        <tr>
                            <td class="cellInterval">
                                <div class="form-group" id="time_{{$i}}" name="interval[{{$i}}][time]">{{ $vacancy['time'] }}</div>
                            </td>
                            <td class="cellInterval">
                                <div class="form-group" id="countReservation_{{$i}}" name="interval[{{$i}}][countReservation]">{{ $vacancy['countReservation'] }}</div>
                            </td>
                            <td class="cellInterval">
                                <div class="form-group" id="base_stock_{{$i}}">
                                    <input type="text" class="form-control" name="interval[{{$i}}][base_stock]" value="{{ old('interval.'.$i.'.base_stock', $vacancy['base_stock'] )}}">
                                </div>
                            </td>
                            <td class="cellInterval">
                                <div class="form-group">
                                    <select class="form-control api_cd" id="is_stop_sale_{{$i}}" name="interval[{{$i}}][is_stop_sale]">
                                        <option value="0" {{ "0" === old('interval.'.$i.'.is_stop_sale', strVal($vacancy['is_stop_sale'])) ? 'selected' : '' }}>有効</option>
                                        <option value="1" {{ "1" === old('interval.'.$i.'.is_stop_sale', strVal($vacancy['is_stop_sale'])) ? 'selected' : '' }}>無効</option>

                                    </select>
                                </div>
                            </td>
                        </tr>
                        @else
                        <tr bgcolor="grey">
                            <td class="cellInterval">
                                <div class="form-group">{{ $vacancy['time'] }}</div>
                            </td>
                            <td class="cellInterval">
                                <div class="form-group">{{ $vacancy['countReservation'] }}</div>
                            </td>
                            <td class="cellInterval">
                                <div class="form-group">
                                    <input type="text" readonly class="form-control" value="設定不可">
                                </div>
                            </td>
                            <td class="cellInterval">
                                <div class="form-group">
                                    <select disabled class="form-control api_cd" id="is_stop_sale_{{$i}}" name="postis_stop_sale-{{$i}}">
                                        <option value="">設定不可</option>
                                    </select>
                                </div>
                            </td>
                        </tr>
                        @endif
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="form-group">
                <div class="text-right">
                    <button id="commit" type="submit" class="btn btn-alt-primary" value="update">更新</button>
                </div>
            </div>


        </div>
    </form>
</div>
<div class="content pb-3 item-list-sp">
    @include('admin.Layouts.flash_message')

    <!-- Default Table Style -->
    <form id="commit" action="{{ route('admin.store.vacancy.edit',['id'=>$store->id]) }}" method="post">
        @csrf
        <div class="d-flex col-md-9 justify-content-between">
            <h2 class="content-heading">{{ $store->name }}</h2>
        </div>
        <div class="d-flex col-md-9 justify-content-between">
            <div class="date">{{ $date }}</div>
        </div>
        <div class="d-flex col-md-9 pb-4 justify-content-between">
            <div class="form-material">
                <label /div for="interval">時間間隔</label>
                <select class="form-control" id="intervalTime" name="intervalTime">
                    <option value="">選択してください</option>
                    @foreach(config('const.storeVacancy.interval') as $key => $value)
                    <option value="{{ $value['value'] }}" @if(old('intervalTime')){{ old('intervalTime') == $value['value'] ? 'selected' : '' }}@elseif(!empty($intervalTime)){{ $intervalTime === $value['value'] ? 'selected' : '' }}@endif>{{ $value['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="subTitle" style="display: flex;align-items: flex-end;">
                <button type="button" class="btn btn-secondary" onclick='location.href="{{ route('admin.store.vacancy', ['id' => $store->id]) }}"'>空席カレンダーへ戻る</button>
            </div>

        </div>
        <div class="block col-md-9">
            <input type="hidden" id="date" name="date" value="{{$date}}">
            <input type="hidden" id="regexp" name="regexp" value="{{$regexp}}">
            <input type="hidden" id="intervalTime" name="intervalTime" value="{{$intervalTime}}">

            <div class="block-content">
                <table class="table table-borderless table-vcenter interval">
                    <thead>
                        <tr>
                            <th style="width:25%">時間</th>
                            <th style="width:25%;">予約済み数<br>(来店人数)</th>
                            <th style="width:25%;">在庫数</th>
                            <th style="width:25%;">有効/無効</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($intervals as $i => $vacancy)
                        @if($vacancy['isOpen'] === 1)
                        <tr>
                            <td class="cellInterval">
                                <div class="form-group" id="time_{{$i}}" name="interval[{{$i}}][time]">{{ $vacancy['time'] }}</div>
                            </td>
                            <td class="cellInterval">
                                <div class="form-group" id="countReservation_{{$i}}" name="interval[{{$i}}][countReservation]">{{ $vacancy['countReservation'] }}</div>
                            </td>
                            <td class="cellInterval">
                                <div class="form-group" id="base_stock_{{$i}}">
                                    <input type="text" class="form-control" name="interval[{{$i}}][base_stock]" value="{{ old('interval.'.$i.'.base_stock', $vacancy['base_stock'] )}}" style="padding:4px 4px;text-align:right;">
                                </div>
                            </td>
                            <td class="cellInterval">
                                <div class="form-group">
                                    <select class="form-control api_cd" id="is_stop_sale_{{$i}}" name="interval[{{$i}}][is_stop_sale]" style="padding:4px 0px;">
                                        <option value="0" {{ "0" === old('interval.'.$i.'.is_stop_sale', strVal($vacancy['is_stop_sale'])) ? 'selected' : '' }}>有効</option>
                                        <option value="1" {{ "1" === old('interval.'.$i.'.is_stop_sale', strVal($vacancy['is_stop_sale'])) ? 'selected' : '' }}>無効</option>

                                    </select>
                                </div>
                            </td>
                        </tr>
                        @else
                        <tr bgcolor="grey">
                            <td class="cellInterval">
                                <div class="form-group">{{ $vacancy['time'] }}</div>
                            </td>
                            <td class="cellInterval">
                                <div class="form-group">{{ $vacancy['countReservation'] }}</div>
                            </td>
                            <td class="cellInterval">
                                <div class="form-group">
                                    <input type="text" readonly class="form-control" value="設定不可">
                                </div>
                            </td>
                            <td class="cellInterval">
                                <div class="form-group">
                                    <select disabled class="form-control api_cd" id="is_stop_sale_{{$i}}" name="postis_stop_sale-{{$i}}">
                                        <option value="">設定不可 </option>
                                    </select>
                                </div>
                            </td>
                        </tr>
                        @endif
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="form-group">
                <div class="text-right">
                    <button id="commit" type="submit" class="btn btn-alt-primary" value="update">更新</button>
                </div>
            </div>


        </div>
    </form>
</div>
<!-- END Content -->

@include('admin.Layouts.js_files')

@endsection

@section('js')
<script>
    $(document).on('focusin', '#intervalTime', function() {
        $(this).data('intervalVal', $(this).val());
    });

    $(document).on('change', '#intervalTime', function() {
        //alert("時間間隔を変更すると既存データは削除されます");
        if (!confirm('時間間隔を変更するボタンが押されました。本当に宜しいですか？')) {
            $("#intervalTime").val($(this).data('intervalVal'));
            return false;
        } else {
            url = location.href.replace(/\#.*$/, '').replace(/\?.*$/, '');
            date = $('#date').val();
            window.location = url + '?date=' + date + '&intervalTime=' + $(this).val();
        }

    });



    $(function() {
        $(document).on('submit', '#commit', function() {
        // $('#commit').submit(function() {
            if (!confirm('現在登録されているデータは削除され元に戻せません。本当に登録して宜しいですか？')) {
                return false;
            } else {
                return true;
            }
        });
    });

    $(document).ready(function() {
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
    }
    $(window).resize(function() {
        update_ids();
    });
</script>
@endsection

@include('admin.Layouts.footer')
