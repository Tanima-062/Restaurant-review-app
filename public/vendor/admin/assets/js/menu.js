$.datetimepicker.setLocale('ja');
$(function () {
    $('body').on('click','.sales_lunch_time_from',function(){
        $(this).datetimepicker(
            {
                format: 'H:i',
                datepicker: false,
            });
        $(this).datetimepicker("show");
    });
    $('body').on('click','.sales_lunch_time_to',function(){
        $(this).datetimepicker(
            {
                format: 'H:i',
                datepicker: false,
            });
        $(this).datetimepicker("show");
    });
    $('body').on('click','.sales_dinner_time_from',function(){
        $(this).datetimepicker(
            {
                format: 'H:i',
                datepicker: false,
            });
        $(this).datetimepicker("show");
    });
    $('body').on('click','.sales_dinner_time_to',function(){
        $(this).datetimepicker(
            {
                format: 'H:i',
                datepicker: false,
            });
        $(this).datetimepicker("show");
    });
})

// Add/Edit
// When the access
$(document).ready(function(){
    var app_cd = $('#app_cd').children(':selected').text();
    // Number of orders same time show/hide
    if(app_cd !== "テイクアウト") {
        $("#number_of_orders_same_time").hide();
        $("input[name=number_of_orders_same_time]").val('');
    } else {
        $("#number_of_orders_same_time").show();
    }
    // Free_drinks show/hide
    if(app_cd === "レストラン") {
        $("#free_drinks").show();
    } else {
        $("#free_drinks").hide();
        $("input[name=free_drinks]").prop('checked', false);
    }

    // lower_orders_time show/hide
    if(app_cd === "レストラン") {
        $("#lower_orders_time").show();
    } else {
        $("#lower_orders_time").hide();
    }

        // show/hide
    // Number of course && Provided time
    // available_number_of_lower_limit && available_number_of_upper_limit
    if(app_cd !== "レストラン") {
        $("#number_of_course").hide();
        $("input[name=number_of_course]").val('');
        $("#provided_time").hide();
        $("input[name=provided_time]").val('');
        $("#available_number_of_lower_limit").hide();
        $("input[name=available_number_of_lower_limit]").val('');
        $("#available_number_of_upper_limit").hide();
        $("input[name=available_number_of_upper_limit]").val('');
    } else {
        $("#number_of_course").show();
        $("#provided_time").show();
        $("#available_number_of_lower_limit").show();
        $("#available_number_of_upper_limit").show();
    }

    // show/hide
    // content_plan && content_menu_notes
    if(app_cd === "レストラン") {
        $("#content_plan").show();
        $("#content_menu_notes").show();
    } else {
        $("#content_plan").hide();
        $("#content_menu_notes").hide();
    }
})

// When the change
$(function () {
    $("#app_cd").on("change", function () {
        var app_cd = $(this).children(':selected').text();
        // Number of orders same time show/hide
        if(app_cd !== "テイクアウト") {
            $("#number_of_orders_same_time").hide();
            $("input[name=number_of_orders_same_time]").val('');
        } else {
            $("#number_of_orders_same_time").show();
        }
        // Free_drinks show/hide
        if(app_cd === "レストラン") {
            $("#free_drinks").show();
        } else {
            $("#free_drinks").hide();
            $("input[name=free_drinks]").prop('checked', false);
        }

        // lower_orders_time show/hide
        if(app_cd === "レストラン") {
            $("#lower_orders_time").show();
        } else {
            $("#lower_orders_time").hide();
        }

        // show/hide
        // Number of course && Provided time
        // available_number_of_lower_limit && available_number_of_upper_limit
        if(app_cd !== "レストラン") {
            $("#number_of_course").hide();
            $("input[name=number_of_course]").val('');
            $("#provided_time").hide();
            $("input[name=provided_time]").val('');
            $("#available_number_of_lower_limit").hide();
            $("input[name=available_number_of_lower_limit]").val('');
            $("#available_number_of_upper_limit").hide();
            $("input[name=available_number_of_upper_limit]").val('');
        } else {
            $("#number_of_course").show();
            $("#provided_time").show();
            $("#available_number_of_lower_limit").show();
            $("#available_number_of_upper_limit").show();
        }

        // show/hide
        // content_plan && content_menu_notes
        if(app_cd === "レストラン") {
            $("#content_plan").show();
            $("#content_menu_notes").show();
        } else {
            $("#content_plan").hide();
            $("#content_menu_notes").hide();
        }
    })
});

// Delete Menu
$(function (){
    $(document).on('click', '.delete-confirm', function() {
        const menu_id = $(this).data('id');
        const menu_name = $(this).data('name');
        const deleteUrl = '/admin/menu/' + menu_id + '/delete';

        if (confirm(menu_name + 'を削除しますか？')) {
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
