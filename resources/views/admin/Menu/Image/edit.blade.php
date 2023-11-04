@extends('admin.Layouts.base')
@include('admin.Layouts.head')

@include('admin.Layouts.side_overlay')
@include('admin.Layouts.sidebar')
@include('admin.Layouts.page_header')

@section('css')
<style>
    /* pc 既存の表示への影響をしないよう */
    @media screen and (min-width: 961px) {
        .img_pc {
            display: block;
        }

        .img_sp {
            display: none;
        }

        .form-material>div .form-control {
            padding-left: 0;
            padding-right: 0;
            border-color: transparent;
            border-radius: 0;
            background-color: transparent;
            box-shadow: 0 1px 0 #d4dae3;
            transition: box-shadow .3s ease-out;
        }

        .modal-dialog {
            width: 500px !important;
        }

        .content-heading {
            border-bottom: 0;
        }

    }

    /* sp 表示設定 */
    @media screen and (max-width: 960px) {
        .img_pc {
            display: none;
        }

        .img_sp {
            display: block;
        }
        .block-content {
            padding: 20px 0 1px !important;
        }
    }
</style>
@endsection

@section('content')
<!-- Content -->
<div class="content pb-3">
    @include('admin.Layouts.flash_message')

    <!-- Default Table Style -->
    <div class="d-flex col-md-9 justify-content-between">
        <h2 class="content-heading">{{ $menu->name }} 画像設定</h2>
        @if(empty($menuImageExists))
        <div class="block-options content-heading" style="padding-right: 9px;">
            <a href="{{ route('admin.menu.image.addForm', ['id' => $menu->id]) }}" class="btn btn-outline-primary" data-toggle="tooltip" title="Add">
                追加
            </a>
        </div>
        @endif
    </div>
    <!-- Floating Labels -->
    <form id="delete_price" action="{{ route('admin.menu.image.edit', ['id' => $menu->id]) }}" method="post" enctype="multipart/form-data">
        @csrf
        <div class="block col-md-9">
            <div class="block-content" id="dynamicForm" style="padding-bottom: 20px;">
                <input type="hidden" name="menu_id" value="{{ $menu->id }}">
                <input type="hidden" name="menu_name" value="{{ $menu->name }}">
                <input type="hidden" name="menu_app_cd" value="{{ $menu->app_cd }}">
                <input type="hidden" name="redirect_to" value="{{ old('redirect_to', url()->previous()) }}">
                <span>{{ config('const.messageBoard.worn_img_lineup') }}<br>
                    {{ config('const.messageBoard.img_file_desc') }}<br><br></span>
                    <span class="img_sp"></span>
                @if(!empty($menuImageExists))
                <div class="addForm">
                    @foreach($menuImages as $menuImage)
                    <input type="hidden" name="menu[{{ $loop->index }}][id]" value="{{ $menuImage['id'] }}">
                    <input type="hidden" name="menu[{{ $loop->index }}][menu_id]" value="{{ $menuImage['menu_id'] }}">
                    <div class="form-group">
                        <div class="form-material col-10 col-md-5 pl-0">
                            <label for="image_cd">メニュー画像コード</label>
                            <select class="form-control" name="menu[{{ $loop->index }}][image_cd]">
                                <option value="">選択してください</option>
                                @foreach($menuImageCodes as $key => $value)
                                <option value="{{ $key }}" {{ $key == old('menu.'.$loop->index.'.image_cd', $menuImage['image_cd']) ? 'selected' : '' }}>
                                    {{ $value }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            @if(! empty($menuImage['url']))
                            <img class="img_pc" src="{{ asset($menuImage['url']) }}" width="300" alt="メニュー画像" />
                            <img class="img_sp" src="{{ asset($menuImage['url']) }}" width="100%" alt="メニュー画像" />
                            @endif
                            <input type="file" class="form-control" id="menu[{{ $loop->index }}][image_path]" name="menu[{{ $loop->index }}][image_path]">
                            <label for="image_path">メニュー画像</label>
                        </div>
                    </div>
                    <div>
                        <label for="weight">優先度</label>
                        <div class="form-group row">
                            <div class="col-3">
                                <input type="text" class="form-control" style="text-align: right;" id="menu[{{ $loop->index }}][weight]" name="menu[{{ $loop->index }}][weight]" value="{{ old('menu.'.$loop->index.'.weight', $menuImage['weight']) }}">
                            </div>
                        </div>
                    </div>
                    <div>
                        <div class="text-right">
                            <button type="button" class="btn btn-sm btn-secondary btn-danger delete-confirm" data-id="{{ $menuImage['id'] }}" data-menu_id="{{ $menu->id }}" data-image_cd="{{ $menuImage['image_cd'] }}" data-toggle="tooltip" title="Delete">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div>
                    <div class="form-group">
                        メニュー画像の登録がありません。
                    </div>
                </div>
                @endif
            </div>
        </div>

        <div class="col-md-9 d-flex justify-content-between">
            <div>
                <button type="button" class="btn btn-secondary" onclick="location.href='{{ session('menuImageRedirectTo', route('admin.menu')) }}'">戻る</button>
            </div>
            @if(!empty($menuImageExists))
            <div style="padding-right: 20px;">
                <button type="submit" class="btn btn-alt-primary" value="update">保存</button>
            </div>
            @endif
        </div>
    </form>
</div>
<!-- END Content -->

@include('admin.Layouts.js_files')

@endsection

@include('admin.Layouts.footer')


@section('js')
<script>
    $(function() {
        $(document).on('click', '.delete-confirm', function() {
            const image_id = $(this).data('id');
            const menu_id = $(this).data('menu_id');
            const menu_published = Number('{{ $menu->published }}');
            const image_cd = $(this).data('image_cd');
            const deleteUrl = '/admin/menu/' + menu_id + '/image/delete/' + image_id;

            if (menu_published) {
                alert('公開中のメニューの画像は削除できません');
            } else if (confirm(image_cd + 'を削除しますか？')) {
                $.ajax({
                        url: deleteUrl,
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            '_token': $('input[name="_token"]').val()
                        },
                    })
                    .done(function(result) {
                        alert('削除しました');
                        location.reload();
                    })
                    .fail(function() {
                        alert('削除に失敗しました');
                        location.reload();
                    });
            }
        });
    })
</script>
@endsection
