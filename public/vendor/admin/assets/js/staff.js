$(function () {
    $(document).on('change', '#settlement_company_id', function () {
        let id = $(this).val();
        if (id === undefined) {
            id = 0;
        }
        $.ajax({
            url: location.origin + '/admin/staff/storeList/' + id,
            type: 'GET',
            dataType: 'json',
            timeout: 5000,
        })
        .done(function(result) {
            $('#store_id > option').remove();
            $('#store_id').append($('<option>').html('選択してください').val(0));
            $.each(result.ret, function(key, item) {
                $('#store_id').append($('<option>').html(item.id + '.' + item.name).val(item.id));
            });
        })
        .fail(function() {
            alert('店舗の取得に失敗しました');
        });
    });

    $(document).on('change', '#staff_authority_id', function () {
        const staffAuthorityId = Number($(this).val());

        if (staffAuthorityId === 1 || staffAuthorityId === 2 || staffAuthorityId === 5) {
            $('#settlement_company_id').parent().parent().hide();
            $('#store_id').parent().parent().hide();
        } else if(staffAuthorityId === 6) {
            $('#settlement_company_id').parent().parent().show();
            $('#store_id').parent().parent().hide();
        } else {
            $('#settlement_company_id').parent().parent().show();
            $('#store_id').parent().parent().show();
        }

        if (staffAuthorityId === 0) {
            $('#settlement_company_id').parent().parent().hide();
            $('#store_id').parent().parent().hide();
            $("select[name=store_id]").val('');
            $("select[name=settlement_company_id]").val('');
        }
    });

    var staffAuthorityId = Number($('#this_staff_authority_id').val());
    if (staffAuthorityId === 1 || staffAuthorityId === 2 || staffAuthorityId === 5) {
        $('#settlement_company_id').parent().parent().hide();
        $('#store_id').parent().parent().hide();
    } else if (staffAuthorityId === 6) {
        $('#store_id').parent().parent().hide();
    }
})
