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
/*    .content-heading { border-bottom: 0; } */
</style>
@endsection

@section('content')
    <!-- Content -->
    <div class="content pb-3">
    @include('admin.Layouts.flash_message')

    <!-- Default Table Style -->
        <div class="col-md-9">
            <h2 class="content-heading">{{ $store->name }} API設定</h2>
        </div>

        <div class="block-header block-header-default col-md-9">
            <h3 class="block-title">API接続設定</h3>
        </div>
        <div class="block col-md-9">
            <div class="block-content" id="dynamicForm">
                <form id="delete_price" action="{{ route('admin.store.api.edit',['id'=>$store->id]) }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <div class="form-material">
                            <label for="api_cd">接続先</label>
                            <select class="form-control api_cd" id="api_cd" name="api_cd">
                                <option value="">選択してください</option>
                                @foreach($externalApiCd as $key => $value)
                                    <option value="{{ $value }}"
                                    @if(old('api_cd')){{ old('api_cd') == $value ? 'selected' : '' }}@elseif(!empty($externalApi->api_cd)){{ $externalApi->api_cd === $value ? 'selected' : '' }}@endif
                                    >{{ $key }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <label for="api_store_id">接続先のID</label>
                            <input type="text" class="form-control" id="api_store_id" name="api_store_id" value="@if (old('api_store_id')){{ old('api_store_id') }}@elseif (!empty($externalApi->api_store_id)){{ $externalApi->api_store_id }}@endif">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="text-right">
                            @if (!empty($externalApi))
                                <button type="button" class="btn btn-alt-danger delete-confirm" data-id={{ $store->id }}>削除</button>
                            @endif
                            <button type="submit" class="btn btn-alt-primary" value="update">@if (!empty($externalApi)) 更新 @else 登録@endif</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="block-header block-header-default col-md-9">
            <h3 class="block-title">電話予約(コールトラッカー)設定</h3>
        </div>
        <div class="block col-md-9">
            <div class="block-content" id="callTrackerForm">
                <form id="callTracker" action="{{ route('admin.store.call_tracker.edit',['id'=>$store->id]) }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <div class="form-material">
                            <label for="advertiser_id">広告主ID</label>
                            <input type="text" class="form-control" id="advertiser_id" name="advertiser_id" value="@if (old('advertiser_id')){{ old('advertiser_id') }}@elseif (!empty($callTracker->advertiser_id)){{ $callTracker->advertiser_id }}@endif">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="text-right">
                            @if (!empty($callTracker))
                                <button type="button" class="btn btn-alt-danger delete-callTracker" data-call_tracker_id={{ $store->id }}>削除</button>
                            @endif
                            <button type="submit" class="btn btn-alt-primary" value="update">@if (!empty($callTracker)) 更新 @else 登録@endif</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="block-header block-header-default col-md-9">
            <h3 class="block-title">電話通知(コールリーチ)設定</h3>
        </div>
        <div class="block col-md-9">
            <div class="block-content" id="telSupportForm">
                <form id="telSupport" action="{{ route('admin.store.tel_support.edit',['id'=>$store->id]) }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <div class="form-material">
                            <label for="advertiser_id">電話通知（要/不要）</label>
                            @foreach($hasTelSupport as $key => $value)
                                <div class="custom-control custom-radio custom-control-inline">
                                    <input class="custom-control-input" type="radio"
                                            name="tel_support" id="tel_support{{ $key }}" value="{{ $key }}"
                                        {{ $key == old('tel_support', $telSupport?$telSupport->is_tel_support:'') ? 'checked' : '' }}
                                    >
                                    <label class="custom-control-label" for="tel_support{{ $key }}" >{{ $value }}</label>
                                </div>
                            @endforeach
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

        <div class="col-md-9">
            <div>
                <button type="button" class="btn btn-secondary" onclick="location.href='{{ session('storeApiRedirectTo', route('admin.store')) }}'">戻る</button>
            </div>
        </div>
    </div>
    <!-- END Content -->

    @include('admin.Layouts.js_files')

@endsection

@section('js')
<script>
$(function (){
    $(document).on('click', '.delete-confirm', function() {
        const id = $(this).data('id');
        const deleteUrl = '/admin/store/' + id + '/api/delete';

        if (confirm('API設定を削除しますか？')) {
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

    $(document).on('click', '.delete-callTracker', function() {
        const id = $(this).data('call_tracker_id');
        const deleteUrl = '/admin/store/' + id + '/call_tracker/delete';

        if (confirm('広告主IDを削除しますか？')) {
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
});

</script>
@endsection

@include('admin.Layouts.footer')
