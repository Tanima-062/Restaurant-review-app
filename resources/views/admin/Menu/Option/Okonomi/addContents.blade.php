<!-- Normal Modal -->
<div class="modal" id="addContentsModal" tabindex="-1" role="dialog" aria-labelledby="addContentsModal" aria-hidden="true">
    <form role="form" id="form" class="add_contents_form form-inline" method="POST" action="">
    @csrf
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="block block-themed block-transparent mb-0">
                <div class="block-header bg-primary-dark">
                    <h3 class="block-title"></h3>
                    <div class="block-options">
                        <button type="button" class="btn-block-option" data-dismiss="modal" aria-label="Close">
                            <i class="si si-close"></i>
                        </button>
                    </div>
                </div>
                <div id="form_id" class="form_class"></div>
                <!-- 内容追加フォーム -->
                <div class="block-content" style="padding-bottom: 20px;">
                    <!-- Validation Message -->
                    <span id="result"></span>
                    <input type="hidden" name="menuOption[menu_id]" value="{{ $menu->id }}">
                    <input type="hidden" name="menuOption[keyword_id]" value="{{ $menu->name }}">
                    <div class="form-group">
                        <div class="form-material">
                            <label for="option_contents">内容<span class="badge bg-danger ml-2 text-white">必須</span></label>
                            <input type="text" class="form-control" name="menuOption[contents]" value="{{ old('menuOption.contents') }}" >
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-material">
                            <label for="price">金額（税込）<span class="badge bg-danger ml-2 text-white">必須</span></label>
                            <div class="d-flex pl-0 justify-content-between">
                                <div class="d-flex">
                                    <input type="text" class="form-control" name="menuOption[price]" value="{{ old('menuOption.price') }}">
                                    <span class="m-3">円</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-alt-secondary closeModal" data-dismiss="modal">閉じる</button>
                <button type="submit" class="btn btn-alt-primary save-addContents">保存</button>
            </div>
        </div>
    </div>
    </form>
</div>
<!-- END Normal Modal -->
