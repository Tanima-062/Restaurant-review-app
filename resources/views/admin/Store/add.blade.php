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
        <h2 class="content-heading">店舗追加</h2>
        <!-- Floating Labels -->
        <div class="block col-md-9">
            <div class="block-content">
                <form action="{{ route('admin.store.add') }}" method="post" class="js-validation-material" novalidate>
                    @csrf
                    <input type="hidden" name="redirect_to" value="{{ old('redirect_to', url()->previous()) }}">
                    <div class="form-group">
                        <div class="form-material">
                            <label for="app_cd">利用サービス<span class="badge bg-danger ml-2 text-white">必須</span></label>
                            <select class="form-control app_cd" id="app_cd" name="app_cd">
                                <option value="">選択してください</option>
                                @foreach($appCd as $code => $content)
                                    <option value="{{ strtoupper($code) }}"
                                        {{ old('app_cd') == strtoupper($code) ? 'selected' : '' }}
                                    >{{ $content[strtoupper($code)] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <label for="settlement_company_id">精算会社名<span class="badge bg-danger ml-2 text-white">必須</span></label>
                            <select class="form-control" id="settlement_company_id" name="settlement_company_id">
                                <option value="">選択してください</option>
                                @foreach($settlementCompanies as $settlementCompany)
                                    <option value="{{ $settlementCompany->id }}" {{ $settlementCompany->id == old('settlement_company_id') ? 'selected' : '' }}>
                                        {{ $settlementCompany->id }}.{{ $settlementCompany->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <label for="name">店舗名<span class="badge bg-danger ml-2 text-white">必須</span></label>
                            <input type="text" class="form-control" id="name" placeholder="例. スカイチケット" name="name" value="{{ old('name') }}" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <label for="alias_name">店舗別名</label>
                            <input type="text" class="form-control" id="alias_name" placeholder="例. スカイチケット" name="alias_name" value="{{ old('alias_name') }}">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <label for="code">店舗コード（半角英数字、-(ハイフン)、_(アンダーバー)利用可）<span class="badge bg-danger ml-2 text-white">必須</span></label>
                            <input type="text" class="form-control" id="code" placeholder="例. skyticket" name="code" value="{{ old('code') }}">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <label for="tel">店舗電話番号（ハイフンを含める）<span class="badge bg-danger ml-2 text-white">必須</span></label>
                            <input type="text" class="form-control" id="tel" placeholder="例. 012-3456-7890" name="tel" value="{{ old('tel') }}" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <label for="tel">予約用電話番号（ハイフンを含める）</label>
                            <input type="text" class="form-control" id="tel_order" placeholder="例. 012-3456-7890" name="tel_order" value="{{ old('tel_order') }}">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <label for="tel">携帯番号（ハイフンを含める）</label>
                            <input type="text" class="form-control" id="mobile_phone" placeholder="例. 012-3456-7890" name="mobile_phone" value="{{ old('mobile_phone') }}">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <label for="fax">店舗FAX番号（ハイフンを含める）</label>
                            <input type="text" class="form-control" id="fax" placeholder="例. 012-3456-7890" name="fax" value="{{ old('fax') }}">
                        </div>
                    </div>
                    <div class="form-group" id="use_fax">
                        <div class="form-material">
                            <label for="use_fax">FAX通知（必要/不要）<span class="badge bg-danger ml-2 text-white">必須</span></label>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input class="custom-control-input" type="radio" name="use_fax" id="use_fax1" value="1"
                                    {{ old('use_fax', '1') === '1' ? 'checked' : '' }}
                                >
                                <label class="custom-control-label" for="use_fax1" >あり</label>
                            </div>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input class="custom-control-input" type="radio" name="use_fax" id="use_fax0" value="0"
                                    {{ old('use_fax', '0') === '0' ? 'checked' : '' }}
                                >
                                <label class="custom-control-label" for="use_fax0" >なし</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <label for="postal_code">郵便番号（ハイフンなし）<span class="badge bg-danger ml-2 text-white">必須</span></label>
                            <input type="text" class="form-control" id="postal_code" placeholder="例. 0123456" name="postal_code" value="{{ old('postal_code') }}" onKeyUp="$('#postal_code').zip2addr({pref:'#address_1',addr:'#address_2'});">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <label for="address_1">住所1（都道府県）<span class="badge bg-danger ml-2 text-white">必須</span></label>
                            <input type="text" class="form-control" id="address_1" placeholder="例. 東京都" name="address_1" value="{{ old('address_1') }}" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <label for="address_2">住所2（市区町村）<span class="badge bg-danger ml-2 text-white">必須</span></label>
                            <input type="text" class="form-control" id="address_2" placeholder="例. 渋谷区" name="address_2" value="{{ old('address_2') }}" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <label for="address_3">住所3（残り）<span class="badge bg-danger ml-2 text-white">必須</span></label>
                            <input type="text" class="form-control" id="address_3" placeholder="例. 恵比寿4-20" name="address_3" value="{{ old('address_3') }}" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <label for="geourl">Google Map URL</label>
                            <input type="text" class="form-control" id="geourl" placeholder="例. google map url" name="geourl" value="{{ old('geourl') }}" onkeyup="extractXY()">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <label for="latitude">緯度<span class="badge bg-danger ml-2 text-white">必須</span></label>
                            <input type="text" class="form-control" id="latitude" placeholder="例. 35.6811673" name="latitude" value="{{ old('latitude') }}" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <label for="longitude">経度<span class="badge bg-danger ml-2 text-white">必須</span></label>
                            <input type="text" class="form-control" id="longitude" placeholder="例. 139.7648629" name="longitude" value="{{ old('longitude') }}" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                        <div id='email_1check' class="badge bg-danger ml-2 text-white"></div>
                            <label for="email_1">予約受付時お知らせメールアドレス1<span class="badge bg-danger ml-2 text-white">必須</span></label>
                            <input type="text" class="form-control" id="email_1" placeholder="例. xxx@skyticket.jp" name="email_1" value="{{ old('email_1') }}" required onkeyup="inputCheck('email_1')">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                        <div id='email_2check' class="badge bg-danger ml-2 text-white"></div>
                            <label for="email_2">予約受付時お知らせメールアドレス2</label>
                            <input type="text" class="form-control" id="email_2" name="email_2" value="{{ old('email_2') }}" onkeyup="inputCheck('email_2')">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                        <div id='email_3check' class="badge bg-danger ml-2 text-white"></div>
                            <label for="email_3">予約受付時お知らせメールアドレス3</label>
                            <input type="text" class="form-control" id="email_3" name="email_3" value="{{ old('email_3') }}" onkeyup="inputCheck('email_3')">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <label for="description">店舗説明</label>
                            <textarea class="form-control" id="description" name="description" rows="5">{{ old('description') }}</textarea>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <label for="access">交通手段</label>
                            <textarea type="text" class="form-control" id="access" name="access" rows="5">{{ old('access') }}</textarea>
                        </div>
                    </div>
                    <div class="form-group" id="regular_holiday">
                        <div class="form-material">
                            <label for="regular_holiday">定休日<span class="badge bg-danger ml-2 text-white">必須</span></label>
                        @foreach($regularHoliday as $key => $value)
                                <div class="custom-control custom-checkbox custom-control-inline">
                                    <input class="custom-control-input" type="checkbox"
                                           name="regular_holiday[{{ $key }}]" id="regular_holiday{{ $key }}" value="0"
                                            {{is_array(old("regular_holiday")) && isset(old("regular_holiday")[$key]) && in_array("1", old("regular_holiday"), true) ? 'checked="checked"' : ''}}
                                    >
                                    <label class="custom-control-label" for="regular_holiday{{ $key }}" >{{ $value['name'] }}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="form-group" id="can_card">
                        <div class="form-material">
                            <label for="can_card">カード（有/無）<span class="badge bg-danger ml-2 text-white">必須</span></label>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input class="custom-control-input" type="radio" name="can_card" id="can_card1" value="1"
                                    {{ old('can_card', '1') === '1' ? 'checked' : '' }}
                                >
                                <label class="custom-control-label" for="can_card1" >あり</label>
                            </div>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input class="custom-control-input" type="radio" name="can_card" id="can_card0" value="0"
                                    {{ old('can_card', '0') === '0' ? 'checked' : '' }}
                                >
                                <label class="custom-control-label" for="can_card0" >なし</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group" id="card_types">
                        <div class="form-material">
                            <label for="card_types">カード種類</label>
                            @foreach($cardTypes as $key => $value)
                                <div class="custom-control custom-checkbox custom-control-inline">
                                    <input class="custom-control-input" type="checkbox"
                                           name="card_types[]" id="can_types{{ $key }}" value="{{ $key }}"
                                        {{ \App\Libs\HasProperty::implodedString($key, old("card_types")) ? 'checked' : '' }}
                                    >
                                    <label class="custom-control-label" for="can_types{{ $key }}" >{{ $value }}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="form-group" id="can_digital_money">
                        <div class="form-material">
                            <label for="can_digital_money">電子マネー（有/無）<span class="badge bg-danger ml-2 text-white">必須</span></label>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input class="custom-control-input" type="radio" name="can_digital_money" id="can_digital_money1" value="1"
                                    {{ old('can_digital_money', '1') === '1' ? 'checked' : '' }}
                                >
                                <label class="custom-control-label" for="can_digital_money1" >あり</label>
                            </div>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input class="custom-control-input" type="radio" name="can_digital_money" id="can_digital_money0" value="0"
                                    {{ old('can_digital_money', '0') === '0' ? 'checked' : '' }}
                                >
                                <label class="custom-control-label" for="can_digital_money0" >なし</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group" id="digital_money_types">
                        <div class="form-material">
                            <label for="digital_money_types">電子マネー種類</label>
                            @foreach($digitalMoneyTypes as $key => $value)
                                <div class="custom-control custom-checkbox custom-control-inline">
                                    <input class="custom-control-input" type="checkbox"
                                           name="digital_money_types[]" id="digital_money_types{{ $key }}" value="{{ $key }}"
                                        {{ \App\Libs\HasProperty::implodedString($key, old("digital_money_types")) ? 'checked' : '' }}
                                    >
                                    <label class="custom-control-label" for="digital_money_types{{ $key }}" >{{ $value }}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <label for="smoking_types">喫煙・禁煙 <span class="badge bg-danger ml-2 text-white">必須</span></label>
                            <select class="form-control" id="smoking_types" name="smoking_types">
                                <option value="">選択してください</option>
                                @foreach($smokingTypes as $key => $value)
                                    <option value="{{ $key }}" {{ $key == old('smoking_types') ? 'selected' : '' }}>
                                        {{ $value }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group" id="can_charter">
                        <div class="form-material">
                            <label for="can_charter">貸切（有/無）<span class="badge bg-danger ml-2 text-white">必須</span></label>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input class="custom-control-input" type="radio" name="can_charter" id="can_charter1" value="1"
                                    {{ old('can_charter', '1') === '1' ? 'checked' : '' }}
                                >
                                <label class="custom-control-label" for="can_charter1" >あり</label>
                            </div>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input class="custom-control-input" type="radio" name="can_charter" id="can_charter0" value="0"
                                    {{ old('can_charter', '0') === '0' ? 'checked' : '' }}
                                >
                                <label class="custom-control-label" for="can_charter0" >なし</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group" id="charter_types">
                        <div class="form-material">
                            <label for="charter_types">貸切種類</label>
                            <select class="form-control" id="charter_types" name="charter_types">
                                <option value="">選択してください</option>
                                @foreach($charterTypes as $key => $value)
                                    <option value="{{ $key }}" {{ $key == old('charter_types') ? 'selected' : '' }}>
                                        {{ $value }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group" id="has_private_room">
                        <div class="form-material">
                            <label for="has_private_room">個室（有/無）<span class="badge bg-danger ml-2 text-white">必須</span></label>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input class="custom-control-input" type="radio" name="has_private_room" id="has_private_room1" value="1"
                                    {{ old('has_private_room', '1') === '1' ? 'checked' : '' }}
                                >
                                <label class="custom-control-label" for="has_private_room1" >あり</label>
                            </div>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input class="custom-control-input" type="radio" name="has_private_room" id="has_private_room0" value="0"
                                    {{ old('has_private_room', '0') === '0' ? 'checked' : '' }}
                                >
                                <label class="custom-control-label" for="has_private_room0" >なし</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group" id="private_room_types">
                        <div class="form-material">
                            <label for="private_room_types">個室種類</label>
                            @foreach($privateRoomTypes as $key => $value)
                                <div class="custom-control custom-checkbox custom-control-inline">
                                    <input class="custom-control-input" type="checkbox"
                                           name="private_room_types[]" id="private_room_types{{ $key }}" value="{{ $key }}"
                                        {{ \App\Libs\HasProperty::implodedString($key, old("private_room_types")) ? 'checked' : '' }}
                                    >
                                    <label class="custom-control-label" for="private_room_types{{ $key }}" >{{ $value }}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="form-group" id="has_parking">
                        <div class="form-material">
                            <label for="has_parking">駐車場（有/無）<span class="badge bg-danger ml-2 text-white">必須</span></label>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input class="custom-control-input" type="radio" name="has_parking" id="has_parking1" value="1"
                                    {{ old('has_parking', '1') === '1' ? 'checked' : '' }}
                                >
                                <label class="custom-control-label" for="has_parking1" >あり</label>
                            </div>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input class="custom-control-input" type="radio" name="has_parking" id="has_parking0" value="0"
                                    {{ old('has_parking', '0') === '0' ? 'checked' : '' }}
                                >
                                <label class="custom-control-label" for="has_parking0" >なし</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group" id="has_coin_parking">
                        <div class="form-material">
                            <label for="has_coin_parking">近隣にコインパーキング（有/無）<span class="badge bg-danger ml-2 text-white">必須</span></label>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input class="custom-control-input" type="radio" name="has_coin_parking" id="has_coin_parking1" value="1"
                                    {{ old('has_coin_parking', '1') === '1' ? 'checked' : '' }}
                                >
                                <label class="custom-control-label" for="has_coin_parking1" >あり</label>
                            </div>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input class="custom-control-input" type="radio" name="has_coin_parking" id="has_coin_parking0" value="0"
                                    {{ old('has_coin_parking', '0') === '0' ? 'checked' : '' }}
                                >
                                <label class="custom-control-label" for="has_coin_parking0" >なし</label>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label for="number_of_seats">座席数</label>
                        <div class="form-group row" id="number_of_seats">
                            <div class="col-2">
                                <input type="text" class="form-control" name="number_of_seats" value="{{ old('number_of_seats') }}">
                            </div>
                            <span style="margin-top:5px">席</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <label for="daytime_budget_lower_limit">予算下限（昼）</label>
                            <div class="form-material d-flex col-9 col-md-5" style="padding-top: 0; padding-left: 0;">
                                <select class="form-control" id="daytime_budget_lower_limit" name="daytime_budget_lower_limit">
                                    <option value="">選択してください</option>
                                    @foreach($budgetLowerLimit as $key => $value)
                                        <option value="{{ $value }}" {{ $value == old('daytime_budget_lower_limit') ? 'selected' : '' }}>
                                            {{ $value }}
                                        </option>
                                    @endforeach
                                </select>
                                <span class="m-3">円</span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <label for="daytime_budget_limit">予算上限（昼）</label>
                            <div class="form-material d-flex col-9 col-md-5" style="padding-top: 0; padding-left: 0;">
                                <select class="form-control" id="daytime_budget_limit" name="daytime_budget_limit">
                                    <option value="">選択してください</option>
                                    @foreach($budgetLimit as $key => $value)
                                        <option value="{{ $value }}" {{ $value == old('daytime_budget_limit') ? 'selected' : '' }}>
                                            {{ $value }}
                                        </option>
                                    @endforeach
                                </select>
                                <span class="m-3">円</span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <label for="night_budget_lower_limit">予算下限（夕）</label>
                            <div class="form-material d-flex col-9 col-md-5" style="padding-top: 0; padding-left: 0;">
                                <select class="form-control" id="night_budget_lower_limit" name="night_budget_lower_limit">
                                    <option value="">選択してください</option>
                                    @foreach($budgetLowerLimit as $key => $value)
                                        <option value="{{ $value }}" {{ $value == old('night_budget_lower_limit') ? 'selected' : '' }}>
                                            {{ $value }}
                                        </option>
                                    @endforeach
                                </select>
                                <span class="m-3">円</span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <label for="night_budget_limit">予算上限（夕）</label>
                            <div class="form-material d-flex col-9 col-md-5" style="padding-top: 0; padding-left: 0;">
                                <select class="form-control" id="night_budget_limit" name="night_budget_limit">
                                    <option value="">選択してください</option>
                                    @foreach($budgetLimit as $key => $value)
                                        <option value="{{ $value }}" {{ $value == old('night_budget_limit') ? 'selected' : '' }}>
                                            {{ $value }}
                                        </option>
                                    @endforeach
                                </select>
                                <span class="m-3">円</span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group show_takeout">
                        <div class="form-material">
                            <label for="price_level">テイクアウト価格帯</label>
                            <div class="form-material d-flex col-9 col-md-3" style="padding-top: 0; padding-left: 0;">
                                <select class="form-control" id="price_level" name="price_level">
                                    <option value="">選択してください</option>
                                    <option value="1">~1000円</option>
                                    <option value="2">~2000円</option>
                                    <option value="3">~3000円</option>
                                    <option value="4">3001円~</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group show_takeout">
                        <div class="form-material">
                            <label for="pick_up_time_interval">テイクアウト受取時間間隔</label>
                            <div class="form-material d-flex col-9 col-md-4" style="padding-top: 0; padding-left: 0;">
                                <select class="form-control" id="pick_up_time_interval" name="pick_up_time_interval">
                                    <option value="">選択してください</option>
                                    @foreach($pickUpTimeInterval as $key => $value)
                                        <option value="{{ $value }}" {{ $value == old('pick_up_time_interval') ? 'selected' : '' }}>
                                            {{ $value }}
                                        </option>
                                    @endforeach
                                </select>
                                <span class="m-3">分</span>
                            </div>
                        </div>
                    </div>
                    <div class="show_takeout">
                        <label for="lower_orders_time_hour">
                            最低注文時間(テイクアウト)<small class="d-block text-secondary">&#x203B; 何時間何分前まで注文が可能か。</small>
                        </label>
                        <div class="form-group row">
                            <div class="col-2">
                                <input type="text" class="form-control" name="lower_orders_time_hour" id="lower_orders_time_hour" value="{{ old('lower_orders_time_hour') }}">
                            </div>
                            <span style="margin-top:5px">時間</span>
                            <div class="col-2">
                                <input type="text" class="form-control" name="lower_orders_time_minute" id="lower_orders_time_minute" value="{{ old('lower_orders_time_minute') }}" size="5">
                            </div>
                            <span style="margin-top:5px">分</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <label for="account">公式SNSアカウント</label>
                            <textarea type="text" class="form-control" id="account" name="account">{{ old('account') }}</textarea>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <label for="remarks">備考</label>
                            <textarea class="form-control" id="remarks" name="remarks" rows="5">{{ old('remarks') }}</textarea>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="form-material">
                            <label for="area">検索エリア設定<span class="badge bg-danger ml-2 text-white">必須</span></label>
                            <div class="form-material d-flex col-9 col-md-4" style="padding-top: 0; padding-left: 0;">
                                <select class="form-control" id="areaLevel1" name="areaLevel1">
                                    <option value="">選択してください</option>
                                        @foreach($areasLevel1 as $key => $value)
                                            <option value="{{ $value->area_cd }}" {{ $value->area_cd == old('areaLevel1') ? 'selected' : '' }}

                                            >{{ $value->name }}</option>
                                        @endforeach
                                </select>
                            </div>
                            <div class="form-material d-flex col-9 col-md-4" style="padding-top: 0; padding-left: 0;">
                                <select class="form-control" id="areaLevel2" name="area_id">
                                    <option value="">大エリアを選択してください</option>

                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="text-right">
                            <button type="submit" class="btn btn-alt-primary" value="update">追加</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <button type="button" class="btn btn-secondary" onclick="location.href='{{ old('redirect_to', url()->previous()) }}'">戻る</button>

    </div>
    <!-- END Content -->
    {{--    @include('admin.Layouts.js_files')--}}

    {{--    <script src="{{ asset('vendor/codebase/assets/js/plugins/jquery-validation/jquery.validate.min.js') }}"></script>--}}
    {{--    <script src="{{ asset('vendor/codebase/assets/js/pages/be_forms_validation.js') }}"></script>--}}
@endsection

@section('js')
    <script>
        // When the access
        $(document).ready(function(){
            if ($("#can_digital_money1").prop("checked")) {
                $("#digital_money_types").show();
            } else {
                $("#digital_money_types").hide();
            }
            if ($("#can_card1").prop("checked")) {
                $("#card_types").show();
            } else {
                $("#card_types").hide();
            }
            if ($("#can_charter1").prop("checked")) {
                $("#charter_types").show();
            } else {
                $("#charter_types").hide();
            }
            if ($("#has_private_room1").prop("checked")) {
                $("#private_room_types").show();
            } else {
                $("#private_room_types").hide();
            }

            if($('#areaLevel1').val()){
                var area_id = {!! json_encode(old('area_id'), JSON_HEX_TAG) !!};
                $.ajax('/admin/v1/area',
                {
                    type: 'get',
                    data: { areaCd: $('#areaLevel1').val() },
                    dataType: 'json'
                }
                )
                .done(function(data) {
                    console.log(area_id);
                    $('#areaLevel2 > option').remove();
                    $.each(data, function (){
                        if(this.id == area_id){
                            $("#areaLevel2").append($("<option selected >").val(this.id).text(this.name));
                        }else{
                            $("#areaLevel2").append($("<option />").val(this.id).text(this.name));
                        }
                    });
                })
                .fail(function() {
                    window.alert('エリアデータを得られませんでした。');
                });
            }


        })

        $(function () {
            // Digital money types show/hide
            $("#can_digital_money1").on('click', function() {
                if ($(this).prop('checked')) {
                    $("#digital_money_types").show();
                }
            })
            $("#can_digital_money0").on("click", function() {
                if($(this).is(":checked")){
                    $("#digital_money_types").hide();
                    $("#digital_money_types").find('input[type=checkbox]').prop('checked', false);
                }
            })
            // Card types show/hide
            $("#can_card1").on('click', function() {
                if ($(this).prop('checked')) {
                    $("#card_types").show();
                }
            })
            $("#can_card0").on("click", function() {
                if($(this).is(":checked")){
                    $("#card_types").hide();
                    $("#card_types").find('input[type=checkbox]').prop('checked', false);
                }
            })
            $("#can_charter1").on('click', function() {
                if ($(this).prop('checked')) {
                    $("#charter_types").show();
                }
            })
            $("#can_charter0").on("click", function() {
                if($(this).is(":checked")){
                    $("#charter_types").hide();
                }
            })
            // Private room types show/hide
            $("#has_private_room1").on('click', function() {
                if ($(this).prop('checked')) {
                    $("#private_room_types").show();
                }
            })
            $("#has_private_room0").on("click", function() {
                if($(this).is(":checked")){
                    $("#private_room_types").hide();
                    $("#private_room_types").find('input[type=checkbox]').prop('checked', false);
                }
            })

            $("#regular_holiday input").on('change', function() {
                if ($(this).prop('checked')) {
                    $(this).val(1);
                } else {
                    $(this).val(0);
                }
            });

            $("#areaLevel1").on('change', function() {

                if(!$('#areaLevel1').val()){
                    return;
                }

                $.ajax('/admin/v1/area',
                {
                    type: 'get',
                    data: { areaCd: $('#areaLevel1').val() },
                    dataType: 'json'
                }
                )
                .done(function(data) {
                    console.log(data);
                    $('#areaLevel2 > option').remove();
                    $.each(data, function (){
                        $("#areaLevel2").append($("<option     />").val(this.id).text(this.name));
                    });
                })
                .fail(function() {
                    window.alert('エリアデータを得られませんでした。');
                });
            });

        })
    </script>
    <script src="{{ asset('vendor/admin/assets/js/zip2addr.js').'?'.time() }}"></script>
    <script src="{{ asset('vendor/admin/assets/js/store.js').'?'.time() }}"></script>
@endsection


@include('admin.Layouts.footer')
