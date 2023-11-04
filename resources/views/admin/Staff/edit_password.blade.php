@extends('admin.Layouts.base')
@include('admin.Layouts.head')

@include('admin.Layouts.side_overlay')
@include('admin.Layouts.sidebar')
@include('admin.Layouts.page_header')

@section('content')
<!-- Content -->
<div class="content">
    @include('admin.Layouts.flash_message')
    @php
        $tmp = old('redirect_to', url()->previous());
        if (Str::endsWith($tmp, 'admin') || Str::endsWith($tmp, 'edit_password_first_login')) {
            // $redirect_to = url('admin/system_notifications'); // パスワード変更後のリダイレクト先
            $redirect_to =  ($staff->staff_authority_id === config('const.staff.authority.OUT_HOUSE_GENERAL')) ? route('admin.settlementCompany') : route('admin.reservation');
        } else {
            $redirect_to = $tmp;
        }
    @endphp
    <h2 class="content-heading">@if($firstLogin)初回@endifパスワード変更</h2>
    <div class="block col-md-9">
        <div class="block-content">
            <!-- jquery.validate.jsにチェックさせるため、name名は決まったものにする  -->
            <form action="{{ route('admin.staff.editPassword', ['id' => $staff->id]) }}" method="post" class="js-validation-material">
                @csrf
                <input type="hidden" name="redirect_to" value="{{ $redirect_to }}">
                <div class="form-group">
                    <div class="form-material" style="padding-top: 60px">
                        <input type="password" class="form-control" id="val-password2" name="val-password2" value="" required>
                        <label for="val-password2">新しいパスワード
                        <small class="d-block text-secondary text-danger">&#x203B; 半角英数字のみ利用できます</small>
                        <small class="d-block text-secondary text-danger">&#x203B; 最低6文字以上かつ、「半角英字の大文字」を必ず1文字以上含めてください</small>
                        </label>
                    </div>
                    <div class="form-material">
                        <input type="password" class="form-control" id="val-confirm-password2" name="val-confirm-password2" value="" required>
                        <label for="val-confirm-password2">新しいパスワード(確認)</label>
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
</div>
<!-- END Content -->
@include('admin.Layouts.js_files')

<script src="{{ asset('vendor/codebase/assets/js/plugins/jquery-validation/jquery.validate.min.js') }}"></script>
<script src="{{ asset('vendor/codebase/assets/js/pages/be_forms_validation.js') }}"></script>
@endsection

@include('admin.Layouts.footer')
