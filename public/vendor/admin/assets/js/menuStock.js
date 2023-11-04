$(document).ready(function () {
    let url = "/admin/menu/stock/";
    let menu_id = $('.menu_id').data('url'); // menu_idを取得

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    let calendar = $('.js-calendar').fullCalendar({
        editable: false,
        events: url + menu_id,
        eventLimit: 2,
        dayMaxEvents: 2,
        displayEventTime: false,
        fixedWeekCount: false,
        longPressDelay: 1,
        showNonCurrentDates: false,
        eventAfterAllRender:function (event) {
            console.log('eventAfterAllRender');
            if (navigator.userAgent.match(/(iPhone|iPad|iPod|Android)/i)) {
                // SPサイズで「在庫個数」表示を折り返す処理
                stockNumWrap();
                selectAreaAdj();
            }
        },
        eventRender: function (event) {
            if (event.allDay === 'true') {
                event.allDay = true;
            } else {
                event.allDay = false;
            }
        },
        selectable: true,
        selectHelper: true,
        eventMouseover: function() {
            console.log('eventMouseover ');
            // Add 「stock-event」class
            let hasStock = $(".fc-content:contains('在庫：')");
            $(hasStock).each(function() {
                $(this).closest('.fc-event-container').addClass('stock-event');
            });
        },
        select: function (start) {
            var date = $.fullCalendar.formatDate(start, "YYYY-MM-DD");
            console.log('select ' + date);
            $('#update-form').removeAttr("id");
            $('.stock-contents').hide();
            $('#add_stock_btn').val("add-btn").text('追加');
            $('#add_stock_date').val(date);
            $('.add-stock-form').attr({ id: 'add-form' });
            $('.add-stock-contents').show();
            $('#display_add_date').text(date);
            calendar.fullCalendar('unselect');
        },
        selectAllow: function(selectInfo) {
            selectInfo.start.startOf("day");
            var evts = $(".js-calendar").fullCalendar("clientEvents", function(evt) {
                var st = evt.start.clone().startOf("day");

                //　当日 編集不可 (false)
                return (selectInfo.start.isSame(st));
            });

            var title = '在庫：';
            if (evts.length !== 0) {
                // titleが含む場合「0」, 含まない場合「-1」が返ってくる
                return evts[0].title.indexOf(title) === -1;
            } else {
                // evtsが「0」の場合
                return true;
            }
        },
        eventClick: function (calEvent) {
            console.log('eventClick ');
            if (calEvent.title >= '在庫：') {
                var stock_id = (calEvent.id);
                var stock_date = $.fullCalendar.formatDate(calEvent.start, "YYYY-MM-DD");
                console.log('eventClick ' + stock_date);
                $('.stock-form').attr({ id: 'update-form' });
                $('.add-stock-contents').hide();
                $.get(url + "get_data/" + stock_id, function (data) {
                    $('#stock_id').val(data.id);
                    $('#stock_date').val(stock_date);
                    $('#stock_number').val(data.stock_number);
                    $('#stock_btn').val("update").text('更新');
                    $('#display_update_date').text(stock_date);
                })
                $('.stock-contents').show()
            }
        },
        contentHeight: 390,
    });

    // Bulk Update Stock
    $(function() {
        $('.js-form-add-event').on('submit', function(event) {
            let Date = moment($('.fc-left').find('h2')[0].textContent, "YYYY-MM");
            let year = Date.format('YYYY');
            let month = Date.format('MM');

            event.preventDefault();
            $.ajax({
                url: url + "bulk_update",
                method: 'POST',
                data: {
                    'stock_number_all': $('input[name="stock_number_all"]').val(),
                    'year': year,
                    'month': month,
                    'menu_id': menu_id,
                    'menu_name': $('input[name="menu_name"]').val(),
                },
                type: 'POST',
                dataType: 'json',
                beforeSend: function() {
                    $('#bulk-update').attr('disabled','disabled');
                },
                success: function(data) {
                    if (data.error) {
                        let error_html = '';
                        for (let count = 0; count < data.error.length; count++) {
                            error_html += '<ul style="margin-bottom: 0"><li>'+data.error[count]+'</li></ul>';
                        }
                        $('#result').html('<div class="alert alert-danger">'+error_html+'</div>');
                    } else {
                        displayMessage("まとめて更新しました");
                        $('.js-calendar').fullCalendar('refetchEvents');
                    }
                    $('#bulk-update').attr('disabled', false);
                }
            })
        });
    });

    // Update Stock
    $(function() {
        let updateForm = $('#update-form');

        $(updateForm).on('submit', function (event) {
            event.preventDefault();
            $.ajax({
                url: url + "edit",
                method: 'POST',
                data: {
                    stock_id: $('input[name="stock_id"]').val(),
                    stock_number: $('input[name="stock_number"]').val(),
                    stock_date: $('input[name="stock_date"]').val(),
                    menu_name: $('input[name="menu_name"]').val(),
                },
                type: 'POST',
                dataType: 'json',
                beforeSend: function() {
                    $('#stock_btn').attr('disabled','disabled');
                },
                success: function(data) {
                    if (data.error) {
                        let error_html = '';
                        for (let count = 0; count < data.error.length; count++) {
                            error_html += '<ul style="margin-bottom: 0"><li>'+data.error[count]+'</li></ul>';
                        }
                        $('#result').html('<div class="alert alert-danger">'+error_html+'</div>');
                    } else {
                        displayMessage("更新しました");
                        $('.js-calendar').fullCalendar('refetchEvents');

                    }
                    $('#stock_btn').attr('disabled', false);
                }
            });

        });
    });

    // Add Stock
    $(function() {
        let addForm = $('#add-form');

        $(addForm).on('submit', function(event) {
            event.preventDefault();
            $.ajax({
                url: url + "add",
                method: 'POST',
                data: {
                    'add_stock_number': $('input[name="add_stock_number"]').val(),
                    'date': $('input[name="add_stock_date"]').val(),
                    'menu_id': menu_id,
                    'menu_name': $('input[name="menu_name"]').val(),
                },
                type: 'POST',
                dataType: 'json',
                beforeSend: function() {
                    $('#add_stock_btn').attr('disabled','disabled');
                },
                success: function(data) {
                    if (data.error) {
                        let error_html = '';
                        for (let count = 0; count < data.error.length; count++) {
                            error_html += '<ul style="margin-bottom: 0"><li>'+data.error[count]+'</li></ul>';
                        }
                        $('#result').html('<div class="alert alert-danger">'+error_html+'</div>');
                    } else {
                        displayMessage("追加しました");
                        $('.js-calendar').fullCalendar('refetchEvents');
                        $(addForm)[0].reset();
                    }
                    $('#add_stock_btn').attr('disabled', false);
                }
            })
        });
    });

    // Delete Event
    $(".event-delete").on("click", function () {
        let stock_id = $('input[name="stock_id"]').val();
        let deleteMsg = confirm("本当に削除しますか?");

        if (deleteMsg) {
            $.ajax({
                type: "POST",
                url: '/admin/menu/stock/delete',
                data:{ id: stock_id },
                success: function (response) {
                    if (response.error) {
                        let error_html = '';
                        for (let count = 0; count < response.error.length; count++) {
                            error_html += '<ul style="margin-bottom: 0"><li>'+response.error[count]+'</li></ul>';
                        }
                        $('#result').html('<div class="alert alert-danger">'+error_html+'</div>');
                    }else if (parseInt(response) > 0) {
                        displayMessage("削除しました");
                        $('.js-calendar').fullCalendar('refetchEvents');
                        $('.stock-contents').hide()
                        $('#update-form')[0].reset();
                    }
                }
            });
        }
    });

    // Close Form Contents
    $(function(){
        console.log('Close Form Contents');

        $(".close-event").on("click", function () {
            $('.stock-contents').hide()
            $('.add-stock-contents').hide()
            $('#update-form').removeAttr("id")
            $('#add-form').removeAttr("id")
            $("input[name=add_stock_number]").val('');
        })
    });
});

// toastr Message
function displayMessage(message) {
    toastr.success(message, '在庫');
}

$.datetimepicker.setLocale('ja');
$(function () {
    $('body').on('click','#jump',function(){
        $(this).datetimepicker(
            {
                format: 'Y-m-d',
                timepicker: false,
            });
        $(this).datetimepicker("show");
    });

    // Jump to a specific date
    $('.jump-button').click(function(){
        const jump = $('#jump').val();
        if (jump.length > 0) {
            $('.js-calendar').fullCalendar('gotoDate', jump)
        }
    });
});

// SPサイズで「在庫個数」表示を折り返す処理
function stockNumWrap() {
    console.log('stockNumWrap');
    let testTimer = setInterval(function() {
        if ($("span:contains('在庫：')").length > 0 || $("div:contains('在庫：')").length > 0) {
            // console.log('clearInterval!! ' + $(document).find('span.fc-title').length + ' ' + $('div.fc-title').length
            // + ' ' + $("span:contains('在庫：')").length + ' ' + $("div:contains('在庫：')").length
            // );
            clearInterval(testTimer);
            $('span.fc-title').each(function(i, e) {
                // console.log('span.fc-title ' + $(e).closest('div.fc-content').attr('class'));
                // if ($(e).text().indexOf('在庫：') >= 0) {
                $(e).closest('div.fc-content').addClass("fc-content-sp");
                $(e).closest('div.fc-content').text($(e).text());
                $(e).closest('div.fc-content').text('');
                // }
            });
            $('div.fc-title').each(function(i, e) {
                // console.log('div.fc-title ' + $(e).closest('div.fc-title').attr('class'));
                // if ($(e).text().indexOf('在庫：') >= 0) {
                $(e).addClass("fc-content-sp");
                // $(e).closest('div.fc-content').text($(e).text());
                // }
            });
        }
    }, 10);

}
// 在庫が無い日のクリック感知エリアが狭い不具合への対応
function selectAreaAdj() {
    $('div.js-calendar td').each(function(i, e) {
        // console.log($(e).attr('class'));
        if ($(e).attr('class') == undefined) {
            $(e).on('click',function(){
                console.log('td clicked!!');
            });
        }
    });
}
