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
            <h2 class="content-heading">{{ $store->name }} 画像設定</h2>
            <div class="block-options content-heading" style="padding-right: 9px;">
                <a href="{{ route('admin.store.image.addForm', ['id' => $store->id]) }}" class="btn btn-outline-primary" data-toggle="tooltip" title="Add">
                    追加
                </a>
            </div>
        </div>
        <!-- Floating Labels -->
        <form id="delete_price" action="{{ route('admin.store.image.edit', ['id' => $store->id]) }}" method="post" enctype="multipart/form-data">
            @csrf
            <div class="block col-md-9">
                <div class="block-content" id="dynamicForm" style="padding-bottom: 20px;">
                    <input type="hidden" name="store_id" value="{{ $store->id }}">
                    <input type="hidden" name="store_name" value="{{ $store->name }}">
                    <input type="hidden" name="store_code" value="{{ $store->code }}">
                    <input type="hidden" name="redirect_to" value="{{ old('redirect_to', url()->previous()) }}">
                    {{ config('const.messageBoard.worn_img_lineup') }}<br>
                    {{ config('const.messageBoard.img_file_desc') }}<br><br>
                    @if(!empty($storeImageExists))
                        <div class="addForm">
                            @foreach($storeImages as $storeImage)
                                <input type="hidden" name="storeImage[{{ $loop->index }}][id]" value="{{ $storeImage['id'] }}">
                                <input type="hidden" name="storeImage[{{ $loop->index }}][store_id]" value="{{ $storeImage['store_id'] }}">
                                <div class="form-group">
                                    <div class="form-material col-10 col-md-5 pl-0">
                                        <label for="image_cd">店舗画像コード</label>
                                        <select class="form-control" name="storeImage[{{ $loop->index }}][image_cd]">
                                            <option value="">選択してください</option>
                                            @foreach($storeImageCodes as $key => $value)
                                                <option value="{{ $key }}" {{ $key == old('storeImage.'.$loop->index.'.image_cd', $storeImage['image_cd']) ? 'selected' : '' }}>
                                                    {{ $value }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="form-material">
                                        @if(!empty($storeImage['url']))
                                            <img src="{{ asset($storeImage['url']) }}" width="300" alt="店舗画像"/>
                                        @endif
                                        <input type="file" class="form-control" id="storeImage[{{ $loop->index }}][image_path]" name="storeImage[{{ $loop->index }}][image_path]">
                                        <label for="image_path">店舗画像</label>
                                    </div>
                                </div>
                                <div>
                                    <label for="weight">優先度</label>
                                    <div class="form-group row">
                                        <div class="col-4">
                                            <input type="text" class="form-control" style="text-align: right; width:4rem;"
                                                   id="storeImage[{{ $loop->index }}][weight]" name="storeImage[{{ $loop->index }}][weight]"
                                                   value="{{ old('storeImage.'.$loop->index.'.weight', $storeImage['weight']) }}"
                                            >
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <div class="text-right">
                                        <button type="button" class="btn btn-sm btn-secondary btn-danger delete-confirm"
                                                data-id="{{ $storeImage['id'] }}" data-store_id="{{ $store->id }}"
                                                data-image_cd="{{ $storeImage['image_cd'] }}" data-toggle="tooltip" title="Delete"
                                        >
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div>
                            <div class="form-group">
                                店舗画像の登録がありません。
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="col-md-9 d-flex justify-content-between">
                <div>
                    <button type="button" class="btn btn-secondary" onclick="location.href='{{ session('storeImageRedirectTo', route('admin.store')) }}'">戻る</button>
                </div>
                @if(!empty($storeImageExists))
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
        $(function (){
            $(document).on('click', '.delete-confirm', function() {
                const image_id = $(this).data('id');
                const store_id = $(this).data('store_id');
                const image_cd = $(this).data('image_cd');
                const deleteUrl = '/admin/store/' + store_id + '/image/delete/' + image_id;

                if (confirm(image_cd + 'を削除しますか？')) {
                    $.ajax({
                        url: deleteUrl,
                        type: 'POST',
                        dataType: 'json',
                        data: {'_token': $('input[name="_token"]').val()},
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
