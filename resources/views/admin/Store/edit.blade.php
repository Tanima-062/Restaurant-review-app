@extends('admin.Layouts.base')
@include('admin.Layouts.head')

@include('admin.Layouts.side_overlay')
@include('admin.Layouts.sidebar')
@include('admin.Layouts.page_header')
@section('css')
<link href="{{ asset('css/custom-select-box.css') }}" rel="stylesheet">

<style>
    /* pc 既存の表示への影響をしないよう */
    /* sp 表示設定 */
    @media screen and (max-width: 960px) {
        .checkbox-weekday {
            margin-top: 8px !important;
            margin-right: 24px !important;
        }

        .checkbox-card,.checkbox-privateroom {
            margin-top: 8px !important;
            margin-right: 24px !important;
        }

        .checkbox-emoney {
            margin-top: 8px !important;
            margin-right: 24px !important;
        }

        .radio-card,.radio-emoney,.radio-rentall,.radio-privateroom,.radio-park,.radio-outpark {
            margin-top: 8px !important;
            margin-right: 24px !important;
        }
    }
</style>
@endsection
@section('content')
    <!-- Content -->
    <div class="content">
    @include('admin.Layouts.flash_message')

    <!-- Default Table Style -->
        <h2 class="content-heading">店舗情報編集</h2>
        <!-- Floating Labels -->
        <div class="block col-md-9">
            <div class="block-content">
                <form action="{{ route('admin.store.edit', ['id' => $store->id]) }}" method="post" novalidate>
                    @csrf
                    <input type="hidden" name="redirect_to" value="{{ old('redirect_to', url()->previous()) }}">
                    <div class="form-group">
                        <div class="item-list-sp form-material">
                            <label for="app_cd">利用サービス<span class="badge bg-danger ml-2 text-white">必須</span></label>
                            <select class="form-control app_cd" id="app_cd" name="app_cd">
                                <option value="">選択してください</option>
                                @foreach($appCd as $code => $content)
                                    <option value="{{ strtoupper($code) }}"
                                        @if (old('app_cd'))
                                            {{ old('app_cd') == strtoupper($code) ? 'selected' : '' }}
                                        @else
                                            {{ $store->app_cd == strtoupper($code) ? 'selected' : ''}}
                                        @endif
                                    >{{ $content[strtoupper($code)] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="item-list-sp form-material">
                            <label for="settlement_company_id">精算会社名
                                @cannot('inAndOutHouseGeneral-only')
                                    <span class="badge bg-info ml-2 text-white">閲覧のみ</span>
                                @else
                                    <span class="badge bg-danger ml-2 text-white">必須</span>
                                @endcan
                            </label>
                            @cannot('inAndOutHouseGeneral-only')
                                <small class="d-block text-secondary">&#x203B; 精算会社名を変更されたい場合は、サイト管理者へお問合せください</small>
                            @endcan
                            <select class="form-control" id="settlement_company_id" name="settlement_company_id"
                                    @cannot('inAndOutHouseGeneral-only') style="appearance: none;" disabled @endcan
                            >
                                <option value="">選択してください</option>
                                @foreach($settlementCompanies as $settlementCompany)
                                    <option value="{{ $settlementCompany->id }}"
                                        {{ old('settlement_company_id', $store->settlement_company_id) == $settlementCompany->id ? 'selected' : '' }}
                                    >{{ $settlementCompany->id }}.{{ $settlementCompany->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <label for="name">店舗名<span class="badge bg-danger ml-2 text-white">必須</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $store->name) }}">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <label for="alias_name">店舗別名</label>
                            <input type="text" class="form-control" id="alias_name" name="alias_name" value="{{ old('alias_name', $store->alias_name) }}">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <label for="store_code">店舗コード
                                @cannot('inAndOutHouseGeneral-only')
                                    <span class="badge bg-info ml-2 text-white">閲覧のみ</span>
                                @else
                                    <span class="badge bg-danger ml-2 text-white">必須</span>
                                @endcan
                            </label>
                            <small class="d-block text-secondary">&#x203B; 半角英数字、-(ハイフン)、_(アンダーバー)の利用ができます</small>
                            @cannot('inAndOutHouseGeneral-only')
                            <small class="d-block text-secondary">&#x203B; 店舗コードを変更されたい場合は、サイト管理者へお問合せください</small>
                            @endcan
                            <input type="text" class="form-control" id="code" placeholder="例. skyticket" name="code" value="{{ old('code', $store->code) }}" @cannot('inAndOutHouseGeneral-only') readonly @endcan>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <label for="tel">店舗電話番号（ハイフンを含める）<span class="badge bg-danger ml-2 text-white">必須</span></label>
                            <input type="text" class="form-control" id="tel" name="tel" value="{{ old('tel', $store->tel) }}">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <label for="tel">予約用電話番号（ハイフンを含める）</label>
                            <input type="text" class="form-control" id="tel_order" name="tel_order" value="{{ old('tel_order', $store->tel_order) }}">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <label for="tel">携帯番号（ハイフンを含める）</label>
                            <input type="text" class="form-control" id="mobile_phone" name="mobile_phone" value="{{ old('mobile_phone', $store->mobile_phone) }}">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <label for="fax">店舗FAX番号（ハイフンを含める）</label>
                            <input type="text" class="form-control" id="fax" name="fax" value="{{ old('fax', $store->fax) }}">
                        </div>
                    </div>
                    <div class="form-group" id="use_fax">
                        <div class="form-material">
                            <label for="use_fax">FAX通知（必要/不要）</label>
                            @foreach($useFax as $key => $value)
                                <div class="custom-control custom-radio custom-control-inline">
                                    <input class="custom-control-input" type="radio"
                                           name="use_fax" id="use_fax{{ $key }}" value="{{ $key }}"
                                        {{ $key == old('use_fax', $store->use_fax) ? 'checked' : '' }}
                                    >
                                    <label class="custom-control-label" for="use_fax{{ $key }}" >{{ $value }}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <label for="postal_code">郵便番号（ハイフンなし）<span class="badge bg-danger ml-2 text-white">必須</span></label>
                            <input type="text" class="form-control" id="postal_code" name="postal_code" value="{{ old('postal_code', $store->postal_code) }}" onKeyUp="$('#postal_code').zip2addr({pref:'#address_1',addr:'#address_2'});">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <label for="address_1">住所1（都道府県）<span class="badge bg-danger ml-2 text-white">必須</span></label>
                            <input type="text" class="form-control" id="address_1" name="address_1" value="{{ old('address_1', $store->address_1) }}" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <label for="address_2">住所2（市区町村）<span class="badge bg-danger ml-2 text-white">必須</span></label>
                            <input type="text" class="form-control" id="address_2" name="address_2" value="{{ old('address_2', $store->address_2) }}" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <label for="address_3">住所3（残り）<span class="badge bg-danger ml-2 text-white">必須</span></label>
                            <input type="text" class="form-control" id="address_3" name="address_3" value="{{ old('address_3', $store->address_3) }}">
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
                            <input type="text" class="form-control" id="latitude" placeholder="例. 35.6811673" name="latitude" value="{{ old('latitude', $store->latitude) }}" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <label for="longitude">経度<span class="badge bg-danger ml-2 text-white">必須</span></label>
                            <input type="text" class="form-control" id="longitude" placeholder="例. 139.7648629" name="longitude" value="{{ old('longitude', $store->longitude) }}" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                        <div id='email_1check' class="badge bg-danger ml-2 text-white"></div>
                            <label for="email_1">予約受付時お知らせメールアドレス1<span class="badge bg-danger ml-2 text-white">必須</span></label>
                            <input type="text" class="form-control" id="email_1" name="email_1" value="{{ old('email_1', $store->email_1) }}" required onkeyup="inputCheck('email_1')">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                        <div id='email_2check' class="badge bg-danger ml-2 text-white"></div>
                            <label for="email_2">予約受付時お知らせメールアドレス2</label>
                            <input type="text" class="form-control" id="email_2" name="email_2" value="{{ old('email_2', $store->email_2) }}" onkeyup="inputCheck('email_2')">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                        <div id='email_3check' class="badge bg-danger ml-2 text-white"></div>
                            <label for="email_3">予約受付時お知らせメールアドレス3</label>
                            <input type="text" class="form-control" id="email_3" name="email_3" value="{{ old('email_3', $store->email_3) }}" onkeyup="inputCheck('email_3')">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <label for="description">店舗説明</label>
                            <textarea class="form-control" id="description" name="description" rows="5">{{ old('description', $store->description) }}</textarea>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <label for="access">交通手段</label>
                            <textarea type="text" class="form-control" id="access" name="access" rows="5">{{ old('access', $store->access) }}</textarea>
                        </div>
                    </div>
                    <div class="form-group" id="regular_holiday">
                        <div class="form-material">
                            <label for="regular_holiday">定休日<span class="badge bg-danger ml-2 text-white">必須</span></label>
                            @foreach($regularHoliday as $key => $value)
                                <div class="checkbox-weekday custom-control custom-checkbox custom-control-inline">
                                    <input class="custom-control-input" type="checkbox"
                                           name="regular_holiday[{{ $key }}]" id="regular_holiday{{ $key }}" value={{$store->getRegularHoliday($key)}}
                                            {{($store->getRegularHoliday($key) ? '' : 'checked')}}
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
                            @foreach($canCard as $key => $value)
                                <div class="radio-card custom-control custom-radio custom-control-inline">
                                    <input class="custom-control-input" type="radio"
                                           name="can_card" id="can_card{{ $key }}" value="{{ $key }}"
                                        {{ $key == old('can_card', $store->can_card) ? 'checked' : '' }}
                                    >
                                    <label class="custom-control-label" for="can_card{{ $key }}" >{{ $value }}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="form-group" id="card_types">
                        <div class="form-material">
                            <label for="card_types">カード種類</label>
                            @foreach($cardTypes as $key => $value)
                                <div class="checkbox-card custom-control custom-checkbox custom-control-inline">
                                    <input class="custom-control-input" type="checkbox"
                                           name="card_types[]" id="can_types{{ $key }}" value="{{ $key }}"
                                        {{ \App\Libs\HasProperty::implodedString($key, old("card_types", $store->card_types)) ? 'checked' : '' }}
                                    >
                                    <label class="custom-control-label" for="can_types{{ $key }}" >{{ $value }}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="form-group" id="can_digital_money">
                        <div class="form-material">
                            <label for="can_digital_money">電子マネー（有/無）<span class="badge bg-danger ml-2 text-white">必須</span></label>
                            @foreach($canDigitalMoney as $key => $value)
                                <div class="radio-emoney custom-control custom-radio custom-control-inline">
                                    <input class="custom-control-input" type="radio"
                                           name="can_digital_money" id="can_digital_money{{ $key }}" value="{{ $key }}"
                                        {{ $key == old('can_digital_money', $store->can_digital_money) ? 'checked' : '' }}
                                    >
                                    <label class="custom-control-label" for="can_digital_money{{ $key }}" >{{ $value }}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="form-group" id="digital_money_types">
                        <div class="form-material">
                            <label for="digital_money_types">電子マネー種類</label>
                            @foreach($digitalMoneyTypes as $key => $value)
                                <div class="checkbox-emoney custom-control custom-checkbox custom-control-inline">
                                    <input class="custom-control-input" type="checkbox"
                                           name="digital_money_types[]" id="digital_money_types{{ $key }}" value="{{ $key }}"
                                        {{ \App\Libs\HasProperty::implodedString($key, old("digital_money_types", $store->digital_money_types)) ? 'checked' : '' }}
                                    >
                                    <label class="custom-control-label" for="digital_money_types{{ $key }}" >{{ $value }}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="item-list-sp form-material">
                            <label for="smoking_types">喫煙・禁煙 <span class="badge bg-danger ml-2 text-white">必須</span></label>
                            <select class="form-control" id="smoking_types" name="smoking_types">
                                <option value="">選択してください</option>
                                @foreach($smokingTypes as $key => $value)
                                    <option value="{{ $key }}"
                                        {{ old('smoking_types', $store->smoking_types) == $key ? 'selected' : '' }}
                                    >{{ $value }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group" id="can_charter">
                        <div class="form-material">
                            <label for="can_charter">貸切（有/無）<span class="badge bg-danger ml-2 text-white">必須</span></label>
                            @foreach($canCharter as $key => $value)
                                <div class="radio-rentall custom-control custom-radio custom-control-inline">
                                    <input class="custom-control-input" type="radio"
                                           name="can_charter" id="can_charter{{ $key }}" value="{{ $key }}"
                                        {{ $key == old('can_charter', $store->can_charter) ? 'checked' : '' }}
                                    >
                                    <label class="custom-control-label" for="can_charter{{ $key }}" >{{ $value }}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="form-group" id="charter_types">
                        <div class="item-list-sp form-material">
                            <label for="charter_types">貸切種類</label>
                            <select class="form-control" id="charter_types" name="charter_types">
                                <option value="">選択してください</option>
                                @foreach($charterTypes as $key => $value)
                                    <option value="{{ $key }}"
                                        {{ old('charter_types', $store->charter_types) == $key ? 'selected' : '' }}
                                    >{{ $value }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group" id="has_private_room">
                        <div class="form-material">
                            <label for="has_private_room">個室（有/無）<span class="badge bg-danger ml-2 text-white">必須</span></label>
                            @foreach($hasPrivateRoom as $key => $value)
                                <div class="radio-privateroom custom-control custom-radio custom-control-inline">
                                    <input class="custom-control-input" type="radio"
                                           name="has_private_room" id="has_private_room{{ $key }}" value="{{ $key }}"
                                        {{ $key == old('has_private_room', $store->has_private_room) ? 'checked' : '' }}
                                    >
                                    <label class="custom-control-label" for="has_private_room{{ $key }}" >{{ $value }}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="form-group" id="private_room_types">
                        <div class="form-material">
                            <label for="private_room_types">個室種類</label>
                            @foreach($privateRoomTypes as $key => $value)
                                <div class="checkbox-privateroom custom-control custom-checkbox custom-control-inline">
                                    <input class="custom-control-input" type="checkbox"
                                           name="private_room_types[]" id="private_room_types{{ $key }}" value="{{ $key }}"
                                        {{ \App\Libs\HasProperty::implodedString($key, old("private_room_types", $store->private_room_types)) ? 'checked' : '' }}
                                    >
                                    <label class="custom-control-label" for="private_room_types{{ $key }}" >{{ $value }}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="form-group" id="has_parking">
                        <div class="form-material">
                            <label for="has_parking">駐車場（有/無）<span class="badge bg-danger ml-2 text-white">必須</span></label>
                            @foreach($hasParking as $key => $value)
                                <div class="radio-park custom-control custom-radio custom-control-inline">
                                    <input class="custom-control-input" type="radio"
                                           name="has_parking" id="has_parking{{ $key }}" value="{{ $key }}"
                                        {{ $key == old('has_parking', $store->has_parking) ? 'checked' : '' }}
                                    >
                                    <label class="custom-control-label" for="has_parking{{ $key }}" >{{ $value }}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="form-group" id="has_coin_parking">
                        <div class="form-material">
                            <label for="has_coin_parking">近隣にコインパーキング（有/無）<span class="badge bg-danger ml-2 text-white">必須</span></label>
                            @foreach($hasCoinParking as $key => $value)
                                <div class="radio-outpark custom-control custom-radio custom-control-inline">
                                    <input class="custom-control-input" type="radio"
                                           name="has_coin_parking" id="has_coin_parking{{ $key }}" value="{{ $key }}"
                                        {{ $key == old('has_coin_parking', $store->has_coin_parking) ? 'checked' : '' }}
                                    >
                                    <label class="custom-control-label" for="has_coin_parking{{ $key }}" >{{ $value }}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <label for="number_of_seats">座席数</label>
                        <div class="form-group row" id="number_of_seats">
                            <div class="col-4">
                                <input type="text" class="form-control" name="number_of_seats" style="width:6rem;" value="{{ old('number_of_seats', $store->number_of_seats) }}">
                            </div>
                            <span style="margin-top:5px">席</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="item-list-sp form-material">
                            <label for="daytime_budget_lower_limit">予算下限（昼）</label>
                            <div class="item-list-sp form-material d-flex col-9 col-md-5" style="padding-top: 0; padding-left: 0;">
                                <select class="form-control" id="daytime_budget_lower_limit" name="daytime_budget_lower_limit">
                                    <option value="">選択してください</option>
                                    @foreach($budgetLowerLimit as $key => $value)
                                        <option value="{{ $value }}"
                                            {{ old('daytime_budget_lower_limit', $store->daytime_budget_lower_limit) == $value ? 'selected' : '' }}
                                        >{{ $value }}</option>
                                    @endforeach
                                </select>
                                <span class="m-3">円</span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="item-list-sp form-material">
                            <label for="daytime_budget_limit">予算上限（昼）</label>
                            <div class="form-material d-flex col-9 col-md-5" style="padding-top: 0; padding-left: 0;">
                                <select class="form-control" id="daytime_budget_limit" name="daytime_budget_limit">
                                    <option value="">選択してください</option>
                                    @foreach($budgetLimit as $key => $value)
                                        <option value="{{ $value }}"
                                            {{ old('daytime_budget_limit', $store->daytime_budget_limit) == $value ? 'selected' : '' }}
                                        >{{ $value }}</option>
                                    @endforeach
                                </select>
                                <span class="m-3">円</span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="item-list-sp form-material">
                            <label for="night_budget_lower_limit">予算下限（夕）</label>
                            <div class="form-material d-flex col-9 col-md-5" style="padding-top: 0; padding-left: 0;">
                                <select class="form-control" id="night_budget_lower_limit" name="night_budget_lower_limit">
                                    <option value="">選択してください</option>
                                    @foreach($budgetLowerLimit as $key => $value)
                                        <option value="{{ $value }}"
                                            {{ old('night_budget_lower_limit', $store->night_budget_lower_limit) == $value ? 'selected' : '' }}
                                        >{{ $value }}</option>
                                    @endforeach
                                </select>
                                <span class="m-3">円</span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="item-list-sp form-material">
                            <label for="night_budget_limit">予算上限（夕）</label>
                            <div class="form-material d-flex col-9 col-md-5" style="padding-top: 0; padding-left: 0;">
                                <select class="form-control" id="night_budget_limit" name="night_budget_limit">
                                    <option value="">選択してください</option>
                                    @foreach($budgetLimit as $key => $value)
                                        <option value="{{ $value }}"
                                            {{ old('night_budget_limit', $store->night_budget_limit) == $value ? 'selected' : '' }}
                                        >{{ $value }}</option>
                                    @endforeach
                                </select>
                                <span class="m-3">円</span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group show_takeout">
                        <div class="item-list-sp form-material">
                            <label for="price_level">テイクアウト価格帯</label>
                            <div class="form-material d-flex col-9 col-md-3" style="padding-top: 0; padding-left: 0;">
                                <select class="form-control" id="price_level" name="price_level">
                                    <option value="">選択してください</option>
                                    <option value="1"
                                    {{ old('price_level', $store->price_level) == 1 ? 'selected' : '' }}>~1000円</option>
                                    <option value="2"
                                    {{ old('price_level', $store->price_level) == 2 ? 'selected' : '' }}>~2000円</option>
                                    <option value="3"
                                    {{ old('price_level', $store->price_level) == 3 ? 'selected' : '' }}>~3000円</option>
                                    <option value="4"
                                    {{ old('price_level', $store->price_level) == 4 ? 'selected' : '' }}>3001円~</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group show_takeout">
                        <div class="item-list-sp form-material">
                            <label for="pick_up_time_interval">テイクアウト受取時間間隔</label>
                            <div class="form-material d-flex col-9 col-md-4" style="padding-top: 0; padding-left: 0;">
                                <select class="form-control" id="pick_up_time_interval" name="pick_up_time_interval">
                                    <option value="">選択してください</option>
                                    @foreach($pickUpTimeInterval as $key => $value)
                                        <option value="{{ $value }}"
                                            {{ old('pick_up_time_interval', $store->pick_up_time_interval) == $value ? 'selected' : '' }}
                                        >{{ $value }}</option>
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
                                <input type="text" class="form-control" name="lower_orders_time_hour" id="lower_orders_time_hour" value="{{ old('lower_orders_time_hour', $store->lower_orders_time_hour) }}">
                            </div>
                            <span style="margin-top:5px">時間</span>
                            <div class="col-2">
                                <input type="text" class="form-control" name="lower_orders_time_minute" id="lower_orders_time_minute" value="{{ old('lower_orders_time_minute', $store->lower_orders_time_minute) }}">
                            </div>
                            <span style="margin-top:5px">分</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <label for="account">公式SNSアカウント</label>
                            <textarea type="text" class="form-control" id="account" name="account">{{ old('account', $store->account) }}</textarea>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <label for="remarks">備考</label>
                            <textarea class="form-control" id="remarks" name="remarks" rows="5">{{ old('remarks', $store->remarks) }}</textarea>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="item-list-sp form-material">
                            <label for="area">検索エリア設定<span class="badge bg-danger ml-2 text-white">必須</span></label>
                            <div class="form-material d-flex col-9 col-md-4" style="padding-top: 0; padding-left: 0;">
                                <select class="form-control area-level-1" id="areaLevel1" name="areaLevel1">
                                    <option value="">大エリアを選択してください</option>
                                        @foreach($areasLevel1 as $key => $value)
                                            <option value="{{ $value->area_cd }}"
                                                @if($parentArea)
                                                {{ old('areaLevel1', $value->id) == $parentArea->id ? 'selected' : '' }}
                                                @endif
                                            >{{ $value->name }}</option>
                                        @endforeach
                                </select>
                            </div>
                            <div class="form-material d-flex col-9 col-md-4" style="padding-top: 0; padding-left: 0;">
                                <select class="form-control area-level-2" id="areaLevel2" name="area_id">
                                    <option value="">中エリアを選択してください</option>
                                        @if ($areasLevel2)
                                        @foreach($areasLevel2 as $key => $value)
                                            <option value="{{ $value->id }}"
                                                {{ old('areaLevel2', $store->area_id) == $value->id ? 'selected' : '' }}
                                            >{{ $value->name }}</option>
                                        @endforeach
                                        @endif
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="text-right">
                            <button type="submit" class="btn btn-alt-primary" value="update">保存</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <button type="button" class="btn btn-secondary" onclick="location.href='{{ old('redirect_to', url()->previous()) }}'">戻る</button>

    </div>
    <!-- END Content -->
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
                var area_id = $('#areaLevel2').val();
                $.ajax('/admin/v1/area',
                {
                    type: 'get',
                    data: { areaCd: $('#areaLevel1').val() },
                    dataType: 'json'
                }
                )
                .done(function(data) {
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

            $(".area-level-1").on('change', function() {

                if(!$('#areaLevel1_--_').val()){
                    $('#areaLevel2 > option').remove();
                    $("#areaLevel2").append($("<option/>").val('').text('中エリアを選択してください'));
                    return;
                }

                $.ajax('/admin/v1/area',
                {
                    type: 'get',
                    data: { 'areaCd': $('.area-level-1').val() },
                    dataType: 'json'
                }
                )
                .done(function(data) {
                    $('.area-level-2 > option').remove();
                    $.each(data, function (){
                        $(".area-level-2").append($("<option     />").val(this.id).text(this.name));
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
    <script>
        $(document).ready(function() {
        update_ids();
    });

    /* ID被りを排除 */
    function update_ids() {
        if ($('div.item-list-pc').css('display') == 'none') {
            $('div.item-list-pc').find('*').each(function(i, e) {
                if ($(this).attr('id')) {
                    $(this).attr('id', $(this).attr('id').split('_--_')[0] + '_--_');
                }
            });
            $('div.item-list-sp').find('*').each(function(i, e) {
                if ($(this).attr('id')) {
                    $(this).attr('id', $(this).attr('id').split('_--_')[0]);
                }
            });
        } else {
            $('div.item-list-pc').find('*').each(function(i, e) {
                if ($(this).attr('id')) {
                    $(this).attr('id', $(this).attr('id').split('_--_')[0]);
                }
            });
            $('div.item-list-sp').find('*').each(function(i, e) {
                if ($(this).attr('id')) {
                    $(this).attr('id', $(this).attr('id').split('_--_')[0] + '_--_');
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
