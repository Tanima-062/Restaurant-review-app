$.datetimepicker.setLocale('ja');
$(function () {
    $('#dynamicForm,#dynamicForm-sp').on('click','.price_start_date',function(){
        $(this).datetimepicker(
            {
                format: 'Y-m-d',
                timepicker: false,
            });
        $(this).datetimepicker("show");
    });
    $('#dynamicForm,#dynamicForm-sp').on('click','.price_end_date',function(){
        $(this).datetimepicker(
            {
                format: 'Y-m-d',
                timepicker: false,
            });
        $(this).datetimepicker("show");
    });
})

// Delete menuPrice
$(function (){
    $(document).on('click', '.delete-confirm', function() {
        const price_id = $(this).data('id');
        const menu_id = $(this).data('menu_id');
        const title = $(this).data('title');
        const deleteUrl = '/admin/menu/' + menu_id + '/price/delete/' + price_id;
        console.log(location.origin);

        if (((($('.delete-confirm').length) - $('.add_form-group').length) <= 1) && ($('#menu_published').val() > 0)) {
            alert('公開中のメニューは１つ以上の料金設定が必要なため削除できません');
        } else if (confirm(title + 'を削除しますか？')) {
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
