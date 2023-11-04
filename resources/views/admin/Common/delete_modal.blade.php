<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="basicModal" aria-hidden="true">
    <form role="form" class="form-inline" method="POST" action="">
        @csrf
        @method('delete')
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4>削除確認</h4>
                </div>
                <div class="modal-body">
                    <p></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">閉じる</button>
                    <button type="submit" class="btn btn-danger">削除</button>
                </div>
            </div>
        </div>
    </form>
</div>
