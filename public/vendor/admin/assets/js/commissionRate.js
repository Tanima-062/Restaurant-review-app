$(function () {
    $(document).on('change', '#accounting_condition',function() {
        const value = $(this).val();
        if (value === 'FIXED_RATE') {
            $('#fee_value').html('%');
        }
        else {
            $('#fee_value').html('円');
        }
    });

    $(document).ready(function(){
        const appCd = $('#app_cd').val();
        if (appCd == 'TO') {
            $('#only_seat').hide();
            $('input:radio[id="only_seat0"]').val(["0"]);
        }
    })

    $(document).on('change', '#app_cd', function() {
        const appCd = $('#app_cd').val();
        if (appCd == 'RS') {
            $('#only_seat').show();
        } else {
            $('#only_seat').hide();
            $('input:radio[id="only_seat0"]').val(["0"]);
        }
    })

    $(document).on('click', '.fa-trash', function() {
        const id = $(this).parent().attr('id');
        const deleteUrl = location.pathname + '/delete/' + id;

        if (confirm('この販売手数料の行を削除しますか？')) {
            location.href = deleteUrl;
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
