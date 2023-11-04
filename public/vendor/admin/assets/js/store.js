// Delete Store
$(function (){
    $(document).on('click', '.delete-confirm', function() {
        const store_id = $(this).data('id');
        const store_name = $(this).data('title');
        const deleteUrl = '/admin/store/' + store_id + '/delete';

        if (confirm(store_name + 'を削除しますか？')) {
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

    if ($('#app_cd').val() === 'RS' || $('#app_cd').val() === '') {
        $('.show_takeout').hide();
    } else {
        $('.show_takeout').show();
    }

    $('#app_cd').on('change', function () {
        console.log($('#app_cd').val());
        if ($('#app_cd').val() === 'RS') {
            $('.show_takeout').hide();
        } else {
            $('.show_takeout').show();
        }
    });
});

function inputCheck(name) {
    var inputValue = document.getElementById(name).value;
    result = inputValue.match(/^([a-z|A-Z|0-9]){1}([a-zA-Z0-9-._])*[@]+([a-zA-Z0-9-._])+$/);
    checkResultName = name + 'check';
    if(result === null){
        document.getElementById(checkResultName).innerHTML = '正しいメールアドレスの形式を入力して下さい：　';
    }else{
        document.getElementById(checkResultName).innerHTML = 'Eメールアドレスチェック OK';
    }
    
}

function extractXY() {

    var URL = document.getElementById('geourl').value;
    var splitUrl = URL.split('!3d');
    var latLong = splitUrl[splitUrl.length-1].split('!4d');
    var longitude;
    if (latLong.indexOf('?') !== -1) {
        longitude = latLong[1].split('\\?')[0];
    } else {
        longitude = latLong[1];
    }
    if(typeof longitude !== "undefined"){
        logitude = longitude.split('?');
        document.getElementById('longitude').value = logitude[0];
        document.getElementById('latitude').value = latLong[0];
    }
  }
