$(function () {

    // エリアマスタ 大カテゴリ変更時
    $(document).on('change', '#big_area', function() {
        const value = $(this).val();
        ajax(value);
    });

    // プルダウン連動処理
    function ajax(value, isReady = null) {
        if (value.length === 0) {
            return;
        }
        
        // 中カテゴリ表示トグル
        if (value !== 'none') {
            $('#middle_area').show();
        } else {
            $('#middle_area').hide();
        }

        $.ajax({
            url: location.origin + '/admin/common/area/list',
            type: 'GET',
            dataType: 'json',
            data: {'area_cd': value, 'level':2},
            timeout: 5000,
        })
        .done(function(result) {
            $('#middle_area > option').remove();
            $('#middle_area').append($('<option>').html('- エリア(中) -').val(''));
            $.each(result.ret, function(key, item) {
                $('#middle_area').append($('<option>').html(item.name).val(item.area_cd));
            });
        })
        .fail(function() {
            alert('エリアカテゴリの取得に失敗しました');
        });
    }

    // バリデーションで返ってきたときに大エリアが選択されてた場合は、中エリアを表示
    $(document).ready(function(){
        if ($('#big_area').val() !== "none"){
            $('#middle_area').show();
        }
    });
});