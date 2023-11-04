// Delete with modal
$(function() {
    $('#deleteModal').on('shown.bs.modal', function (event) {
        const button = $(event.relatedTarget);
        const title = button.data('title');
        const url = button.data('url');
        const text = "を削除しますか？";
        const modal = $(this);
        modal.find('.modal-body p').eq(0).text(title + text);
        modal.find('form').attr('action',url);
    });
})
