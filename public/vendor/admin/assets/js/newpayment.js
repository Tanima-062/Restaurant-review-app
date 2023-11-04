function yoshinCancel(orderCode, reservationId, target) {
    target.disabled = true;
    $.ajax({
        type: 'POST',
        dataType: 'json',
        timeout: 10000,
        url: 'v1/newpayment/yoshin_cancel',
        data: {
            orderCode: orderCode,
            reservationId: reservationId
        }
    })
    .done(function(res){
        if (res && res.ret === 'ok') {
            alert('与信をキャンセルしました');
            location.reload();
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

function cardCapture(orderCode, reservationId, target) {
    target.disabled = true;
    $.ajax({
        type: 'POST',
        dataType: 'json',
        timeout: 10000,
        url: 'v1/newpayment/card_capture',
        data: {
            orderCode: orderCode,
            reservationId: reservationId
        }
    })
    .done(function(res){
        if (res && res.ret === 'ok') {
            alert('計上にしました');
            location.reload();
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
