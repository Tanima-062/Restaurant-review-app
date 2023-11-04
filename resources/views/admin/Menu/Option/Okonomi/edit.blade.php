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
                <h3 class="content-heading" style="margin-bottom: 0; padding-top: 0;">お好み編集</h3>
            </div>
        </div>
        <!-- Floating Labels -->
        <form id="update_form" action="{{ route('admin.menu.option.okonomi.edit') }}" method="post">
            @csrf
            <div class="block col-md-9">
                <div class="block-content" id="dynamicForm">
                    <input type="hidden" name="menu_id" value="{{ $menu->id }}">
                    <input type="hidden" name="menu_name" value="{{ $menu->name }}">
                    <input type="hidden" name="redirect_to" value="{{ old('redirect_to', url()->previous()) }}">
                    <div id="menuOption_form">
                        <div class="add_form-group">
                            <input type="hidden" name="option_cd" value="OKONOMI">
                            <input type="hidden" name="keyword_id" value="{{ $menuOptions[0]['keyword_id'] }}">
                            <input type="hidden" name="contents_id" value="1">
                            <div class="form-group">
                                <div class="form-material col-6 col-md-4 pl-0">
                                    <label for="option_required">必須/任意</label>
                                    <select class="form-control" name="menuOkonomi[0][required]">
                                        <option value="">選択してください</option>
                                        @foreach($menuOptionRequired as $key => $value)
                                            <option value="{{ $key }}" {{ $key == old('menuOkonomi.0.required', $menuOptions[0]['required']) ? 'selected' : '' }}>{{ $value }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="form-material">
                                    <label for="option_keyword">項目</label>
                                    <input type="text" class="form-control" id="option_keyword" name="menuOkonomi[0][keyword]" value="{{ old('menuOkonomi.0.keyword', $menuOptions[0]['keyword']) }}" placeholder="例. 麺の量、麺の種類 など">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="block col-md-9">
                <div class="block-content">
                    <div>
                        <div class="add_form-group">
                            @foreach($menuOptions as $key => $menuOption)
                                <input type="hidden" name="menuOption[{{ $key }}][id]" value="{{ $menuOption['id'] }}" >
                                <div class="form-group">
                                    <div class="form-material">
                                        <label for="option_contents">内容</label>
                                        <input type="text" class="form-control" name="menuOption[{{ $key }}][contents]" value="{{ old('menuOption.'. $key .'.contents', $menuOption['contents']) }}" >
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="form-material">
                                        <label for="price">金額（税込）</label>
                                        <div class="d-flex pl-0 justify-content-between">
                                            <div class="d-flex">
                                                <input type="text" class="form-control" name="menuOption[{{ $key }}][price]" value="{{ old('menuOption.'. $key .'.price', $menuOption['price']) }}">
                                                <span class="m-3">円</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-9 d-flex justify-content-between">
                <div>
                    <button type="button" class="btn btn-secondary" onclick="location.href='{{ route('admin.menu.option', ['id' => $menu->id]) }}'">戻る</button>
                </div>
                <div style="padding-right: 20px;">
                    <button type="submit" class="btn btn-alt-primary" id="save" value="save">更新</button>
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
