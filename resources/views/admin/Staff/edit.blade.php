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
        <h2 class="content-heading">スタッフ編集</h2>
        <!-- Floating Labels -->
        <div class="block col-md-9">
            <div class="block-content">
                <form action="{{url('admin/staff/edit').'/'.$staff->id}}" method="post">
                    {{csrf_field()}}
                    <input type="hidden" name="this_staff_authority_id" id="this_staff_authority_id" value="{{$staff->staff_authority_id}}">
                    <input type="hidden" name="redirect_to" value="{{old('redirect_to', url()->previous())}}">
                    <div class="form-group">
                        <div class="form-material">
                            <input type="text" class="form-control" id="name" name="name" value="{{old('name', $staff->name)}}" required>
                            <label for="name">お名前</label>
                        </div>
                    </div>
                    @if((Auth::user())->staff_authority_id <= config('const.staff.authority.IN_HOUSE_GENERAL'))
                    <div class="form-group">
                        <div class="form-material">
                            <select class="form-control" id="staff_authority_id" name="staff_authority_id">
                                @foreach($staffAuthorities as $staffAuthority)
                                    <option value="{{$staffAuthority->id}}" @if(old('staff_authority_id', $staff->staff_authority_id) == $staffAuthority->id) selected @endif>{{$staffAuthority->name}}</option>
                                @endforeach
                            </select>
                            <label for="material-select">権限</label>
                        </div>
                    </div>
                    @endif
                    @if((Auth::user())->staff_authority_id <= config('const.staff.authority.IN_HOUSE_GENERAL'))
                    <div class="form-group">
                        <div class="form-material">
                            <select class="form-control" id="settlement_company_id" name="settlement_company_id">
                                <option value="0">選択してください</option>
                                @foreach($settlementCompanies as $settlementCompany)
                                <option value="{{$settlementCompany->id}}" @if(old('settlement_company_id', $staff->settlement_company_id) == $settlementCompany->id) selected @endif>{{$settlementCompany->id}}.{{$settlementCompany->name}}</option>
                                @endforeach
                            </select>
                            <label for="material-select">精算会社<span class="badge bg-danger ml-2 text-white">必須</span></label>
                        </div>
                    </div>
                    @endif
                    @if((Auth::user())->staff_authority_id <= config('const.staff.authority.IN_HOUSE_GENERAL'))
                    <div class="form-group">
                        <div class="form-material">
                            <select class="form-control" id="store_id" name="store_id">
                                <option value="0">選択してください</option>
                                @foreach($stores as $store)
                                <option value="{{$store->id}}" @if(old('store_id', $staff->store_id) == $store->id) selected @endif>{{$store->id}}.{{$store->name}}</option>
                                @endforeach
                            </select>
                            <label for="material-select">店舗<span class="badge bg-danger ml-2 text-white">必須</span></label>
                        </div>
                    </div>
                    @endif
                    <div class="form-group">
                        <div class="form-material">
                            <div class="text-right">
                                <label class="css-control css-control css-control-primary css-switch">
                                    @php
                                        $check = $staff->published ? true : false;
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
                            <button type="submit" class="btn btn-alt-primary" value="update">更新</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="block col-md-9">
            <div class="block-content">
                <!-- jquery.validate.jsにチェックさせるため、name名は決まったものにする  -->
                <form action="{{url('admin/staff/edit_password').'/'.$staff->id}}" method="post" class="js-validation-material">
                    {{csrf_field()}}
                    <input type="hidden" name="redirect_to" value="{{old('redirect_to', url()->previous())}}">
                    <div class="form-group">
                        <div class="form-material">
                            <input type="password" class="form-control" id="val-password2" name="val-password2" value="" required>
                            <label for="val-password2">パスワード</label>
                        </div>
                        <div class="form-material">
                            <input type="password" class="form-control" id="val-confirm-password2" name="val-confirm-password2" value="" required>
                            <label for="val-confirm-password2">パスワード確認</label>
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

        <button type="button" class="btn btn-secondary" onclick="location.href='{{route('admin.staff')}}'">戻る</button>

    </div>
    <!-- END Content -->
    @include('admin.Layouts.js_files')

    <script src="{{ asset('vendor/admin/assets/js/staff.js').'?'.time() }}"></script>
@endsection

@include('admin.Layouts.footer')
