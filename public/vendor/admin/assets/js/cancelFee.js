$.datetimepicker.setLocale('ja');
$(function () {
    $(document).on('change', '#cancel_fee_unit',function() {
        const value = $(this).val();
        if (value === 'FIXED_RATE') {
            $('#cancel_fee_value').html('%');
        }
        else {
            $('#cancel_fee_value').html('円');
        }
    });

    $(document).on('change', '#cancel_limit_unit',function() {
        const value = $(this).val();
        if (value === 'DAY') {
            $('#cancel_limit_value').html('日');
        }
        else {
            $('#cancel_limit_value').html('時間');
        }
    });

    $("#apply_term_from,#apply_term_from_sp").datetimepicker(
    {
        format: 'Y/m/d',
        timepicker: false,
    });
    $("#apply_term_to,#apply_term_to_sp").datetimepicker({
        format: 'Y/m/d',
        timepicker: false,
    });
});

// Delete
$(function (){
    $(document).on('click', '.delete-confirm', function() {
        const id = $(this).data('id');
        const cancel_fee_id = $(this).data('cancel_fee_id');
        const deleteUrl = '/admin/store/' + id + '/cancel_fee/' + cancel_fee_id + '/delete';

        if (confirm('キャンセル料を削除しますか？')) {
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
