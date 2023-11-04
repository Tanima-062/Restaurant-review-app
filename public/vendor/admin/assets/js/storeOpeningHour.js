$.datetimepicker.setLocale('ja');
$(function () {
    $('body').on('click','.start_at',function(){
        $(this).datetimepicker(
            {
                format: 'H:i',
                datepicker: false,
            });
        $(this).datetimepicker("show");
    });
    $('body').on('click','.end_at',function(){
        $(this).datetimepicker(
            {
                format: 'H:i',
                datepicker: false,
                allowTimes:[
                    '00:00', '01:00', '02:00', '03:00', '04:00', '05:00',
                    '06:00', '07:00', '08:00', '09:00', '10:00', '11:00',
                    '12:00', '13:00', '14:00', '15:00', '16:00', '17:00',
                    '18:00', '19:00', '20:00', '21:00', '22:00', '23:00',
                    '23:59'
                ]
            });
        $(this).datetimepicker("show");
    });
    $('body').on('click','.last_order_time',function(){
        $(this).datetimepicker(
            {
                format: 'H:i',
                datepicker: false,
                step: 30,
            });
        $(this).datetimepicker("show");
    });
});

// Add OpeningHour
$(function () {
    $('#add_opening_hour').on('submit', function(event) {
        event.preventDefault();
        $.ajax({
            url:'/admin/store/opening_hour/add',
            method:'post',
            data:$(this).serialize(),
            dataType:'json',
            beforeSend:function() {
                $('#save').attr('disabled','disabled');
            },
            success:function(data) {
                if (data.error) {
                    let error_html = '';
                    for (let count = 0; count < data.error.length; count++) {
                        error_html += '<ul style="margin-bottom: 0"><li>'+data.error[count]+'</li></ul>';
                    }
                    $('#result').html('<div class="alert alert-danger col-md-9">'+error_html+'</div>');
                } else {
                    $('#result').html('<div class="alert alert-success col-md-9">'+data.success+'</div>');
                    $("#result").fadeIn(300).delay(1000).fadeOut(300).queue(function()
                    {
                        window.location=data.url;
                    });
                }
                $('#save').attr('disabled', false);
            }
        })
    });
});

// Delete OpeningHour
$(function (){
    $(document).on('click', '.delete-confirm', function() {
        const open_id = $(this).data('id');
        const store_id = $(this).data('store_id');
        const open_cd = $(this).data('image_cd');
        const deleteUrl = '/admin/store/' + store_id + '/opening_hour/delete/' + open_id;

        if (confirm(open_cd + 'を削除しますか？')) {
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
