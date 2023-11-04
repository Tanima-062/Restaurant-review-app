@extends('admin.Layouts.base')
@include('admin.Layouts.head')

@include('admin.Layouts.side_overlay')
@include('admin.Layouts.sidebar')
@include('admin.Layouts.page_header')

@section('css')
    <style>
        .form-material> div .form-control, #menuOptions .form-control {
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
    <div class="content">
        <!-- Validation Message -->
        @include('admin.Layouts.flash_message')
        <!-- Default Table Style -->
        <h2 class="content-heading" style="margin-bottom: 0; padding-left: 20px;">{{ $menu->name }} オプション</h2>
        <div class="block" style="background-color: #f0f2f5; margin-bottom: 0;">
            <div class="col-md-9 block-header">
                <h3 class="content-heading" style="margin-bottom: 0; padding-top: 0;">お好み追加</h3>
            </div>
        </div>
        <!-- Floating Labels -->
        <form id="add_form" action="{{ route('admin.menu.option.okonomiKeyword.add', ['id' => $menu->id]) }}" method="post">
            @csrf
            <div class="block col-md-9">
                <div class="block-content" id="dynamicForm">
                    <input type="hidden" name="menu_id" value="{{ $menu->id }}">
                    <input type="hidden" name="menu_name" value="{{ $menu->name }}">
                    <input type="hidden" name="redirect_to" value="{{ old('redirect_to', url()->previous()) }}">
                    <div id="menuOption_form">

                        <div class="add_form-group">
                            <input type="hidden" name="option_cd" value="OKONOMI">
                            <input type="hidden" name="contents_id" value="1">
                            <div class="form-group">
                                <div class="form-material col-6 col-md-4 pl-0">
                                    <label for="option_required">必須/任意<span class="badge bg-danger ml-2 text-white">必須</span></label>
                                    <select class="form-control" name="required">
                                        <option value="">選択してください</option>
                                        @foreach($menuOptionRequired as $key => $value)
                                        <option value="{{ $key }}"
                                            {{ old('required') == $key ? 'selected' : '' }}
                                        >{{ $value }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="form-material">
                                    <label for="option_keyword">項目<span class="badge bg-danger ml-2 text-white">必須</span></label>
                                    <input type="text" class="form-control" id="option_keyword" name="keyword" value="{{ old('keyword') }}" placeholder="例. 麺の量、麺の種類 など">
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="form-material">
                                    <label for="option_contents">内容<span class="badge bg-danger ml-2 text-white">必須</span></label>
                                    <input type="text" class="form-control" name="contents" value="{{ old('contents') }}" >
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="form-material">
                                    <label for="price">金額（税込）<span class="badge bg-danger ml-2 text-white">必須</span></label>
                                    <div class="d-flex pl-0 justify-content-between">
                                        <div class="d-flex">
                                            <input type="text" class="form-control" name="price" value="{{ old('price') }}">
                                            <span class="m-3">円</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

            </div>

            <div class="col-md-9 d-flex justify-content-between">
                <div>
                    <button type="button" class="btn btn-secondary" onclick="location.href='{{ route('admin.menu.option', ['id' => $menu->id]) }}'">戻る</button>
                </div>
                <div style="padding-right: 20px;">
                    <button type="submit" class="btn btn-alt-primary" id="save" value="save">保存</button>
                </div>
            </div>
        </form>
    </div>
    <!-- END Content -->

    @include('admin.Layouts.js_files')

@endsection

@include('admin.Layouts.footer')

@section('js')
    <script src="{{ asset('vendor/admin/assets/js/menuOption.js').'?'.time() }}"></script>
@endsection
