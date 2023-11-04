function yoshinCancel(orderId, target) {
    target.disabled = true;
    $.ajax({
        type: 'POST',
        dataType: 'json',
        timeout: 10000,
        url: 'payment/yoshin_cancel',
        data: {
            order_id: orderId
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

function cardCapture(orderId, target) {
    target.disabled = true;
    $.ajax({
        type: 'POST',
        dataType: 'json',
        timeout: 10000,
        url: 'payment/card_capture',
        data: {
            order_id: orderId
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
