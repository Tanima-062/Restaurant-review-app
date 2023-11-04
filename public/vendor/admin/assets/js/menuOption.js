$.datetimepicker.setLocale('ja');
$(function () {
    $('#dynamicForm,#dynamicForm-sp').on('click','.price_date_from',function(){
        $(this).datetimepicker(
            {
                format: 'Y-m-d',
                timepicker: false,
            });
        $(this).datetimepicker("show");
    });
    $('#dynamicForm,#dynamicForm-sp').on('click','.price_date_to',function(){
        $(this).datetimepicker(
            {
                format: 'Y-m-d',
                timepicker: false,
            });
        $(this).datetimepicker("show");
    });
})

// Clear data when hide modal
$(function () {
    $('#addContentsModal').on('hidden.bs.modal', function (e) {
        $(this).children()[0].reset();
    });
});

// Show 'addContents' form
$(function() {
    $('#addContentsModal').on('shown.bs.modal', function (event) {
        const button = $(event.relatedTarget);
        const text = "項目：";
        const title = button.data('title');
        $(this).find('.block-header h3').eq(0).text(text + title);
        const url = button.data('url');
        const i = $(this).find('form');
        i.attr('data-url', url); // generate 'data-url' into form
    });
});

// Create new addContents
$(function() {
    $('.add_contents_form').on('submit', function(event) {
        const url = $('.add_contents_form')[0].dataset.url;
        const modal = $('#addContentsModal');
        modal.find('form').attr('action', url);

        event.preventDefault();
        $.ajax({
            url: url,
            method: 'post',
            data: $(this).serialize(),
            dataType: 'json',
            beforeSend: function() {
                $('#save').attr('disabled','disabled');
            },
            success: function(data) {
                if (data.error) {
                    let error_html = '';
                    for (let count = 0; count < data.error.length; count++) {
                        error_html += '<ul style="margin-bottom: 0"><li>'+data.error[count]+'</li></ul>';
                    }
                    $('#result').html('<div class="alert alert-danger">'+error_html+'</div>');
                } else {
                    $('#result').html('<div class="alert alert-success">'+data.success+'</div>');
                    $("#result").fadeIn(300).delay(1000).fadeOut(300).queue(function()
                    {
                        location.reload();
                    });
                }
                $('#save').attr('disabled', false);
            }
        })
    });
});

// Delete Contents
$(function (){
    $(document).on('click', '.delete-confirm', function() {
        const option_id = $(this).children().data('id');
        const option_cd = $(this).children().data('cd');
        const option_keyword = $(this).children().data('keyword');
        const option_contents = $(this).children().data('contents');
        const deleteUrl = location.pathname + '/delete/' + option_id;
        let category = option_cd === 'OKONOMI' ? 'お好み：' : 'トッピング：';

        if (confirm(category + option_keyword + ' ' + option_contents + 'を削除しますか？')) {
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
})



