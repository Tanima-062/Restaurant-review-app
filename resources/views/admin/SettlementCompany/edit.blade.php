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
        <h2 class="content-heading">精算会社編集</h2>
        <!-- Floating Labels -->
        <div class="block col-md-9">
            <div class="block-content">
                <form action="{{url('admin/settlement_company/edit').'/'.$settlementCompany->id}}" method="post">
                    {{csrf_field()}}
                    <input type="hidden" name="redirect_to" value="{{old('redirect_to', url()->previous())}}">
                    <div class="form-group">
                        <div class="form-material">
                            <input type="text" class="form-control" id="name" name="name" value="{{old('name', $settlementCompany->name)}}" required>
                            <label for="name">会社名<span class="badge bg-danger ml-2 text-white">必須</span></label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <input type="text" class="form-control" id="tel" name="tel" value="{{old('tel', $settlementCompany->tel)}}" required>
                            <label for="tel">電話番号(ハイフンなし)<span class="badge bg-danger ml-2 text-white">必須</span></label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <input type="text" class="form-control" id="postal_code" name="postal_code" value="{{old('postal_code', $settlementCompany->postal_code)}}" required>
                            <label for="tel">郵便番号(ハイフンなし)<span class="badge bg-danger ml-2 text-white">必須</span></label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <input type="text" class="form-control" id="address" name="address" value="{{old('address', $settlementCompany->address)}}" required>
                            <label for="address">住所<span class="badge bg-danger ml-2 text-white">必須</span></label>
                        </div>
                    </div>
                    <label for="cycle" style="margin-bottom:10px">支払いサイクル</label>
                    <div class="form-group row" id="cycle">
                        <div class="col-8">
                            @foreach($cycle as $v)
                                <div class="custom-control custom-radio custom-control-inline">
                                    <input class="custom-control-input" type="radio" name="payment_cycle" id="payment_cycle_{{$v['value']}}" value="{{$v['value']}}" @if($v['value'] == old('payment_cycle', $settlementCompany->payment_cycle)) checked @endif>
                                    <label class="custom-control-label" for="payment_cycle_{{$v['value']}}" >{{$v['label']}}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <label for="cycle" style="margin-bottom:10px">成果基準額</label>
                    <div class="form-group row" id="cycle">
                        <div class="col-8">
                            @foreach($baseAmount as $v)
                                <div class="custom-control custom-radio custom-control-inline">
                                    <input class="custom-control-input" type="radio" name="result_base_amount" id="result_base_amount_{{$v['value']}}" value="{{$v['value']}}" @if($v['value'] == old('result_base_amount', $settlementCompany->result_base_amount)) checked @endif>
                                    <label class="custom-control-label" for="result_base_amount_{{$v['value']}}" >{{$v['label']}}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <label for="cycle" style="margin-bottom:10px">消費税計算</label>
                    <div class="form-group row" id="cycle">
                        <div class="col-8">
                            @foreach($taxCalculation as $v)
                                <div class="custom-control custom-radio custom-control-inline">
                                    <input class="custom-control-input" type="radio" name="tax_calculation" id="tax_calculation_{{$v['value']}}" value="{{$v['value']}}" @if($v['value'] == old('tax_calculation', $settlementCompany->tax_calculation)) checked @endif>
                                    <label class="custom-control-label" for="tax_calculation_{{$v['value']}}" >{{$v['label']}}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material" style="padding-top: 40px">
                            <input type="text" class="form-control" id="bank_name" name="bank_name" value="{{old('bank_name', $settlementCompany->bank_name)}}">
                            <label for="bank_name">銀行名
                                <span class="badge bg-danger ml-2 text-white">必須</span>
                                <small class="d-block text-danger text-secondary">&#x203B; 銀行名が未定の方は、-（半角ハイフン）で登録してください</small>
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material" style="padding-top: 40px">
                            <input type="text" class="form-control" id="branch_name" name="branch_name" value="{{old('branch_name', $settlementCompany->branch_name)}}">
                            <label for="branch_name">支店名
                                <span class="badge bg-danger ml-2 text-white">必須</span>
                                <small class="d-block text-danger text-secondary">&#x203B; 支店名が未定の方は、-（半角ハイフン）で登録してください</small>
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material" style="padding-top: 40px">
                            <input type="text" class="form-control" id="branch_number" name="branch_number" value="{{old('branch_number', $settlementCompany->branch_number)}}">
                            <label for="branch_number">支店番号
                                <span class="badge bg-danger ml-2 text-white">必須</span>
                                <small class="d-block text-danger text-secondary">&#x203B; 支店番号が未定の方は、-（半角ハイフン）で登録してください</small>
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material" style="padding-top: 40px">
                            <input type="text" class="form-control" id="account_number" name="account_number" value="{{old('account_number', $settlementCompany->account_number)}}">
                            <label for="account_number">口座番号
                                <span class="badge bg-danger ml-2 text-white">必須</span>
                                <small class="d-block text-danger text-secondary">&#x203B; 口座番号が未定の方は、-（半角ハイフン）で登録してください</small>
                            </label>
                        </div>
                    </div>
                    <label for="type" style="margin-bottom:10px">種別</label>
                    <div class="form-group row" id="type">
                        <div class="col-8">
                            @foreach($accountType as $v)
                                <div class="custom-control custom-radio custom-control-inline">
                                    <input class="custom-control-input" type="radio" name="account_type" id="account_type_{{$v['value']}}" value="{{$v['value']}}" @if($v['value'] == old('account_type', $settlementCompany->account_type)) checked @endif>
                                    <label class="custom-control-label" for="account_type_{{$v['value']}}" >{{$v['label']}}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material" style="padding-top: 40px">
                            <input type="text" class="form-control" id="account_name_kana" name="account_name_kana" value="{{old('account_name_kana', $settlementCompany->account_name_kana)}}">
                            <label for="account_name_kana">口座名義
                                <span class="badge bg-danger ml-2 text-white">必須</span>
                                <small class="d-block text-danger text-secondary">&#x203B; 口座名義が未定の方は、-（半角ハイフン）で登録してください</small>
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <input type="text" class="form-control" id="billing_email_1" name="billing_email_1" value="{{old('billing_email_1', $settlementCompany->billing_email_1)}}" required>
                            <label for="billing_email_1">通知書送付先メールアドレス1<span class="badge bg-danger ml-2 text-white">必須</span></label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <input type="text" class="form-control" id="billing_email_2" name="billing_email_2" value="{{old('billing_email_2', $settlementCompany->billing_email_2)}}">
                            <label for="billing_email_2">通知書送付先メールアドレス2</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <div class="text-right">
                                <label class="css-control css-control css-control-primary css-switch">
                                    @php
                                        $check = $settlementCompany->published ? true : false;
                                        if (!empty(old('redirect_to'))) {
                                            if (old('published') !== '1') {
                                                $check = false;
                                            } else {
                                                $check = true;
                                            }
                                        }
                                    @endphp
                                    <input type="checkbox" class="css-control-input" id="published" name="published" value="1" @if($check) checked @endif>
                                    <span class="css-control-indicator"></span> 公開する
                                </label>
                            </div>
                            <label for="published">公開/非公開</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="text-right">
                            <button type="submit" class="btn btn-alt-primary">更新</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <button type="button" class="btn btn-secondary" onclick="location.href='{{old('redirect_to', url()->previous())}}'">戻る</button>

    </div>
    <!-- END Content -->
    @include('admin.Layouts.js_files')
@endsection

@include('admin.Layouts.footer')
