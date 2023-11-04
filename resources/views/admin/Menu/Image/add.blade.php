@extends('admin.Layouts.base')
@include('admin.Layouts.head')

@include('admin.Layouts.side_overlay')
@include('admin.Layouts.sidebar')
@include('admin.Layouts.page_header')

@section('css')
<style>
    .form-material> div .form-control {
        padding-left: 0;
        padding-right: 0;
        border-color: transparent;
        border-radius: 0;
        background-color: transparent;
        box-shadow: 0 1px 0 #d4dae3;
        transition: box-shadow .3s ease-out;
    }
    .modal-dialog { width: 500px!important; }
    .content-heading { border-bottom: 0; }
</style>
@endsection

@section('content')
    <!-- Content -->
    <div class="content pb-3">
    @include('admin.Layouts.flash_message')

    <!-- Default Table Style -->
        <div class="d-flex col-md-9 justify-content-between">
            <h2 class="content-heading">{{ $menu->name }} 画像追加</h2>
        </div>
        <!-- Floating Labels -->
        <form id="add_menuImage" action="{{ route("admin.menu.image.add") }}" method="post" enctype="multipart/form-data">
            @csrf
            <div class="block col-md-9">
                <div class="block-content" id="dynamicForm" style="padding-bottom: 20px;">
                        <input type="hidden" name="menu_id" value="{{ $menu->id }}">
                        <input type="hidden" name="menu_name" value="{{ $menu->name }}">
                        <input type="hidden" name="app_cd" value="{{ $menu->app_cd }}">
                        <input type="hidden" name="store_id" value="{{ $menu->store_id }}">
                        <input type="hidden" name="redirect_to" value="{{ old('redirect_to', url()->previous()) }}">
                    <div id="menuImage_form">

                        <div class="add_form-group">
                            <div class="form-group">
                                <div class="form-material col-10 col-md-6 pl-0">
                                    <label for="image_cd">メニュー画像コード（追加）<span class="badge bg-danger ml-2 text-white">必須</span></label>
                                    <select class="form-control" name="image_cd" style="margin-top: 16px;" required>
                                        <option value="">選択してください</option>
                                        @foreach($menuImageCodes as $key => $value)
                                        <option value="{{ $key }}" {{ $key == old('image_cd') ? 'selected' : '' }}>{{ $value }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group" style="margin-top: 24px;">
                                    <div class="form-material">
                                        <p>{{ config('const.messageBoard.img_file_desc') }}</p>
                                        <input type="file" class="form-control" style="box-shadow: none;" id="image_path" name="image_path">
                                        <label for="image_path">メニュー画像<span class="badge bg-danger ml-2 text-white">必須</span></label>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <div class="col-md-9 d-flex justify-content-between">
                <div>
                    <button type="button" class="btn btn-secondary" onclick="location.href='{{ route('admin.menu.image.editForm', ['id' => $menu->id]) }}'">戻る</button>
                </div>
                <div style="padding-right: 20px;">
                    <button type="submit" class="btn btn-alt-primary" value="add">追加</button>
                </div>
            </div>
        </form>
    </div>
    <!-- END Content -->
    @include('admin.Layouts.js_files')

@endsection

@include('admin.Layouts.footer')


@section('js')
    <script src="{{ asset('vendor/admin/assets/js/common.js').'?'.time() }}"></script>
@endsection
