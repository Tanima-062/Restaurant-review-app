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
        <h2 class="content-heading">スタッフ追加</h2>
        <!-- Floating Labels -->
        <div class="block col-md-9">
            <div class="block-content">
                <form action="{{ route('admin.staff.add') }}" method="post" class="js-validation-material">
                    @csrf
                    <input type="hidden" name="redirect_to" style="margin-top: 16px;" value="{{ old('redirect_to', url()->previous()) }}">
                    <div class="form-group">
                        <div class="form-material">
                            <input type="text" class="form-control" id="name" name="name" value="{{old('name')}}" required>
                            <label for="name">お名前<span class="badge bg-danger ml-2 text-white">必須</span></label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material" style="padding-top: 60px">
                            <input type="text" class="form-control" style="margin-top: 16px;" id="username" name="username" value="{{old('username')}}" required>
                            <label for="username">ログインID<span class="badge bg-danger ml-2 text-white">必須</span>
                            <small class="d-block text-secondary text-danger">&#x203B; 半角英数字、-(半角ハイフン)、_(半角アンダーバー)の利用ができます</small>
                            <small class="d-block text-secondary text-danger">&#x203B; 最大文字数は64文字までになります</small>
                            </label>
                        </div>
                    </div>
                    @if((Auth::user())->staff_authority_id <= config('const.staff.authority.IN_HOUSE_GENERAL'))
                    <div class="form-group">
                        <div class="form-material">
                            <select class="form-control" id="staff_authority_id" name="staff_authority_id">
                                <option value="">選択してください</option>
                                @foreach($staffAuthorities as $staffAuthority)
                                <option value="{{ $staffAuthority->id }}" @if($staffAuthority->id == old('staff_authority_id')) selected @endif>{{ $staffAuthority->name }}</option>
                                @endforeach
                            </select>
                            <label for="material-select">権限<span class="badge bg-danger ml-2 text-white">必須</span></label>
                        </div>
                    </div>
                    @endif
                    @if((Auth::user())->staff_authority_id <= config('const.staff.authority.IN_HOUSE_GENERAL'))
                        <div class="form-group" style="display: none">
                            <div class="form-material">
                                <select class="form-control" id="settlement_company_id" name="settlement_company_id">
                                    <option value="0">選択してください</option>
                                    @foreach($settlementCompanies as $settlementCompany)
                                    <option value="{{$settlementCompany->id}}" @if($settlementCompany->id == old('settlement_company_id')) selected @endif>{{$settlementCompany->id}}.{{$settlementCompany->name}}</option>
                                    @endforeach
                                </select>
                                <label for="material-select">精算会社<span class="badge bg-danger ml-2 text-white">必須</span></label>
                            </div>
                        </div>
                    @endif
                    @if((Auth::user())->staff_authority_id <= config('const.staff.authority.IN_HOUSE_GENERAL'))
                    <div class="form-group" style="display: none">
                        <div class="form-material">
                            <select class="form-control" id="store_id" name="store_id">
                                <option value="0">選択してください</option>
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
                                        $check = true;
                                        if (!empty(old('redirect_to'))) {
                                            if (old('published') !== '1') {
                                                $check = false;
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
                        <div class="form-material" style="padding-top: 60px">
                            <!-- jquery.validate.jsにチェックさせるため、name名は決まったものにする  -->
                            <input type="password" class="form-control" style="margin-top: 16px;" id="val-password2" name="val-password2" value="{{ old('val-password2') }}" required>
                            <label for="val-password2">パスワード<span class="badge bg-danger ml-2 text-white">必須</span>
                            <small class="d-block text-secondary text-danger">&#x203B; 半角英数字のみ利用できます</small>
                            <small class="d-block text-secondary text-danger">&#x203B; 最低6文字以上かつ、「半角英字の大文字」を必ず1文字以上含めてください</small>
                            </label>
                        </div>
                        <div class="form-material" style="margin-top: 16px;">
                            <input type="password" class="form-control" style="margin-top: 16px;" id="val-confirm-password2" name="val-confirm-password2" value="{{ old('val-confirm-password2') }}" required>
                            <label for="val-confirm-password2">パスワード確認<span class="badge bg-danger ml-2 text-white">必須</span></label>
                        </div>
                    </div>
                    @can('clientAdmin-only')
                        <input type="hidden" name="staff_authority_id" value="4">
                        <input type="hidden" name="store_id" value="{{ Auth::user()->store_id }}">
                    @endcan
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
    @include('admin.Layouts.js_files')

    <script src="{{ asset('vendor/admin/assets/js/staff.js').'?'.time() }}"></script>
@endsection

@include('admin.Layouts.footer')
