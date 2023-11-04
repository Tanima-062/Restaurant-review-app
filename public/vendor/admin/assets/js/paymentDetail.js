$(function () {
    $(document).on('click', '#savePaymentDetail', function () {
        alert('差分決済はこのversionでは出来ません');
    });

    function disableFormBtn() {
        $('#saveReservation').prop('disabled', true);
        $('#savePaymentDetail').prop('disabled', true);
        $('#saveCancelDetail').prop('disabled', true);
        $('#execRefund').prop('disabled', true);
    }

    function releaseFormBtn() {
        $('#saveReservation').prop('disabled', false);
        $('#savePaymentDetail').prop('disabled', false);
        $('#saveCancelDetail').prop('disabled', false);
        $('#execRefund').prop('disabled', false);
    }

    /*
     * 予約情報 - 入金ステータス
     */
    $(document).on('click', '#saveReservation', function(){
        disableFormBtn();
        var data = {
            id: $('#reservation_id').val(),
            payment_status: $('#reservationPaymentStatus').val()
        };

        $.ajax({
            type: 'POST',
            dataType: 'json',
            timeout: 10000,
            url: '../status_payment',
            data: data
        })
        .done(function(res){
            if (res && res.ret === 'ok') {
                alert('登録しました');
                location.reload();
            }
            else {
                alert('Error:登録に失敗しました。' + res.message);
            }
        })
        .fail(function(){
            alert('Fail:登録に失敗しました');
        })
        .always(function(){
            releaseFormBtn();
        });
    });

    /*
    * キャンセル明細 - 科目入力
    */
    $(document).on('keyup', '#cancelDetailPrice, #cancelDetailCount', function () {
        const cancel_detail_price = $('#cancelDetailPrice').val();
        const cancel_detail_count = $('#cancelDetailCount').val();
        if ((cancel_detail_price.length > 0 && !$.isNumeric(cancel_detail_price)) || (cancel_detail_count.length > 0 && !$.isNumeric(cancel_detail_count))) {
            console.log('数値を入力してください');
            return;
        }

        const adjust_difference = cancel_detail_price * cancel_detail_count;
        const cancel_detail_price_sum = adjust_difference + parseInt($('#cancelDetailPriceSum').html().replace(/,/g, ''));
        $('#cancelPriceSum').html(Number(adjust_difference).toLocaleString(undefined, {maximunFractionDigits: 20 }));

        if (adjust_difference === 0) {
            $('#cancelDetailSumRemarks').html('');
        }
        else {
            $('#cancelDetailSumRemarks').html('※調整後 ' + Number(cancel_detail_price_sum).toLocaleString(undefined, {maximunFractionDigits: 20 }));
        }
    });

    /*
    * キャンセル明細 - 科目保存
    */
    $(document).on('click', '#saveCancelDetail', function(){
        if (Number($('#cancelDetailPrice').val()) == 0) {
            alert('0円は登録出来ません');
            return false;
        }

        disableFormBtn();

        $.ajax({
            type: 'POST',
            dataType: 'json',
            timeout: 10000,
            url: '../../payment/detail/cancel_detail_add',
            data: {
                reservation_id: $('#reservation_id').val(),
                account_code: $('#cancelDetailAccountCode').val(),
                price: $('#cancelDetailPrice').val(),
                count: $('#cancelDetailCount').val(),
                remarks: $('#cancelDetailRemarks').val()
            }
        })
        .done(function(res){
            if (res && res.ret === 'ok') {
                $('#cancelDetailAccountCode').val('--');
                $('#cancelDetailPrice').val('');
                $('#cancelDetailCount').val('');
                $('#cancelDetailRemarks').val('');
                alert('登録しました');
                location.reload();
            }
            else {
                alert('Error:登録に失敗しました。' + res.message);
            }
        })
        .fail(function(){
            alert('Fail:登録に失敗しました');
        })
        .always(function(){
            releaseFormBtn();
        });
    });

    /*
      * 決済状況 - 返金実行
      */
    $(document).on('click', '#execRefund', function(){
        if (!confirm('返金処理を実行しますか?')) {
            return false;
        }

        disableFormBtn();
        $.ajax({
            type: 'POST',
            dataType: 'json',
            timeout: 10000,
            url: '../../payment/detail/exec_refund',
            data: {
                id: $('#reservation_id').val(),
                pfPaid: $('#pfPaid').val(),
                pfRefund: $('#pfRefund').val(),
                pfRefunded: $('#pfRefunded').val()
            }
        })
        .done(function(res){
            if (res && res.ret === 'ok') {
                alert('返金しました');
                location.reload();
            }
            else {
                alert('Error:返金に失敗しました。' + res.msg);
            }
        })
        .fail(function(){
            alert('Fail:返金に失敗しました');
        })
        .always(function(){
            releaseFormBtn();
        });
    });
});

function reviewPayment(reservationId, target) {
    target.disabled = true;
    $.ajax({
        type: 'POST',
        dataType: 'json',
        timeout: 10000,
        url: '/admin/v1/newpayment/review',
        data: {
            reservationId: reservationId
        }
    })
    .done(function(res){
        if (res && res.ret === 'ok') {
            console.log(res.data);
            const service_cd = Object.keys(res.data.list.data[0].cm_application_ids)[0];
            var balance = 0;
            for(i=0; i<res.data.list.data.length; i++){
                console.log(res.data.list.data[i]);
                if (res.data.list.data[i].progress == 4) { // 与信キャンセル時
                    continue
                }
                balance = balance + res.data.list.data[i].payment_price
            }
            $('.popup').addClass('show').fadeIn();
            //location.reload();
            $("#popupSkyticketApplicationNumber").text("skyticket申込番号 : " + res.data.list.data[0].cm_application_ids[service_cd][0]);
            $("#popupPaymentId").text("決済ID : " + res.data.list.data[0].id);
            $("#popupCartId").text("カートID : " + res.data.list.data[0].cart_id);
            $("#popupUserId").text("ユーザID : " + res.data.list.data[0].user_id);
            $("#popupProgressName").text("決済処理状況 : " + res.data.list.data[0].progress_name);
            $("#popupPaymentMethod").text("決済方法 : " + res.data.payment_methods[res.data.list.data[0].payment_method_id]);
            $("#popupPaymentPrice").text("支払金額 : " + balance);
            $("#popupPaidAt").text("支払日時 : " + res.data.list.data[0].created_at);
            $("#popupReceivedAt").text("入金日時 : " + res.data.list.data[0].created_at);
        }
        else {
            alert('Error:失敗しました。' + res.message);
        }
    })
    .fail(function(){
        alert('Fail:失敗しました');
    })
    .always(function(){
        target.disabled = false;
    });
}

$('#popupClose').on('click',function(){
    $('.popup').fadeOut();
});
