$(document).ready(function () {

    let url = "/admin/store/vacancy/";
    let menu_id = $('.store_id').data('url'); // menu_idを取得
    let today = moment().format('YYYY-MM-DD');

    let edit_url = "/admin/store/vacancy/" + menu_id + "/edit?date=";

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    let calendar = $('.js-calendar').fullCalendar({
        editable: false,
        events: url + menu_id,
        eventLimit: 4,
        dayMaxEvents: 2,
        displayEventTime: false,
        fixedWeekCount: false,
        longPressDelay: 1,
        showNonCurrentDates: false,
        eventAfterAllRender:function (event) {
            console.log('eventAfterAllRender');
            if (navigator.userAgent.match(/(iPhone|iPad|iPod|Android)/i) && $('div.item-list-pc').css('display') == 'none') {
                // SPサイズで「省略形タイトル」表示処理
                abbreviateTitle();
            } else {
                unAbbreviateTitle();
            }
        },
        eventRender: function (event, element) {
            // alert("The view's title is " + "view.currentStart");

            // console.log('eventRender!!!');
            if (navigator.userAgent.match(/(iPhone|iPad|iPod|Android)/i)) {
                // SPサイズで「凡例」表示処理
                dispLegend();
            }

            if (event.allDay === 'true') {
                event.allDay = true;
            } else {
                event.allDay = false;
            }

            if (event.title == '予約あり') {
                $(element).find(".fc-title").css("font-weight", "800");
            } else {
                $(element).find(".fc-title").css("font-weight", "400");
            }
        },

        selectable: true,
        selectHelper: true,
        eventMouseover: function() {
            // Add 「stock-event」class
            let hasStock = $(".fc-content:contains('在庫：')");
            $(hasStock).each(function(){
                $(this).closest('.fc-event-container').addClass('stock-event')
            });
        },
        select: function (start) {
            var date = $.fullCalendar.formatDate(start, "YYYY-MM-DD");
            if (date >= today){
                location.href = edit_url + date;
            } else {
                pastDay();
            }
        },
        selectAllow: function(selectInfo) {
            const loader = document.getElementById('js-loader');
            window.addEventListener('load', () => {
            const ms = 400;
            loader.style.transition = 'opacity ' + ms + 'ms';

            const loaderOpacity = function(){
                loader.style.opacity = 0;
            }
            const loaderDisplay = function(){
                loader.style.display = "none";
            }
            // setTimeout(loaderOpacity, 1);
            // setTimeout(loaderDisplay, ms);
            // デモ用
            setTimeout(loaderOpacity, 1000);
            setTimeout(loaderDisplay, 1000 + ms);
            });
        },
        eventClick: function (calEvent) {
            var date = $.fullCalendar.formatDate(calEvent.start, "YYYY-MM-DD");
            if (date >= today){
                location.href = edit_url + date;
            } else {
                pastDay();
            }
        },
        viewDidMount: function (view) {
            // var view = calendar.view;
            alert("The view's title is " + view.currentStart);
        },
        contentHeight: 390,

    });
});

// toastr Message
function displayMessage(message) {
    toastr.success(message, '在庫');
}

function pastDay() {
    alert('過ぎた日の在庫は設定できません。');
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

    $('#copy, #copy_sp').submit(function() {
        if (!confirm('1週目と同じデータを登録します。本当に登録して宜しいですか？')) {
            return false;
        }else{
            return true;
        }
    });

    var maxEnd = new Date;
    var minEnd = new Date;
    minEnd.setDate(minEnd.getDate() + 1);
    //maxEnd.setDate(maxEnd.getDate() + 90);
    maxEnd.setMonth(maxEnd.getMonth() + 4, 0);
    $("#start, #start_sp").datetimepicker({
        format: 'Y-m-d',
        timepicker: false,
        maxDate: maxEnd,
        minDate: minEnd,
    });

    $("#end, #end_sp").datetimepicker({
        format: 'Y-m-d',
        timepicker: false,
        maxDate: maxEnd,
        minDate: minEnd,
    });

});

function dispLegend() {
    // console.log('dispLegend');
    if ($(document).find('#legend').length > 0) {
        $(document).find('#legend').css('display', 'block');
        return;
    }
    let testTimer2 = setInterval(function() {
        if ($("div.fc-toolbar").length > 0) {
            if ($("#legend").length>0) {
                return;
            }
            clearInterval(testTimer2);
            let t = $('div.fc-toolbar');
            console.log("div.fc-clear found!! " + t.attr('class'));
            $(`<div id="legend" style="display:flex;padding-bottom:8px;">
                <div style="width:18px;height:18px;background-color:#FFCCFF;margin-right:4px;display: inline-block;">　
                </div>
                <div style="margin-right:16px;font-size:12px;display: inline-block;">予約状況</div>
                <div style="width:18px;height:18px;background-color:#e3f4fc;margin-right:4px;display: inline-block;">　
                </div>
                <div style="margin-right:16px;font-size:12px;display: inline-block;">販売状況</div>
                <div style="width:18px;height:18px;background-color:#FF0000;margin-right:4px;display: inline-block; color:#FFFFFF;text-align:center;font-size:11px;">☆
                </div>
                <div style="margin-right:16px;font-size:12px;display: inline-block;">祝日</div>
                </div>`).insertAfter($('div.fc-toolbar'));
        }
    }, 10);
}

function dismissLegend() {
    console.log('dismissLegend');
    if ($(document).find('#legend').length > 0) {
        $(document).find('#legend').css('display', 'none');
    }
}
function abbreviateTitle() {
    console.log('abbreviateTitle');
    $(document).find('span').each(function(index, element) {

        if ($(element).text() == '予約あり') {
            $(element).closest('div.fc-content').css('text-align', 'center');
            $(element).text('あり');
        }
        if ($(element).text() == '予約なし') {
            $(element).closest('div.fc-content').css('text-align', 'center');
            $(element).text('なし');
        }
        if ($(element).text() == '販売中') {
            $(element).closest('div.fc-content').css('text-align', 'center');
            $(element).text('販売中');
        }
        if ($(element).text() == '在庫データ未登録') {
            $(element).closest('div.fc-content').css('text-align', 'center');
            $(element).text('未登録');
        }
        if ($(element).text().indexOf('☆') >= 0) {
            console.log('$(element).text().indexOf(!!!!!!!    ' + $(element).closest('div.fc-content').find('.org_title').val());
            if ($(element).closest('div.fc-content').find('.org_title').val() == undefined) {
                $(element).after('<input type="hidden" class="org_title" value="' + $(element).text() + '">');
            }
            $(element).closest('div.fc-content').css('text-align', 'center');
            $(element).text('☆');
        }
    });
}
function unAbbreviateTitle() {
    console.log('abbreviateTitle');
    $(document).find('span').each(function(index, element) {
        // console.log($(element).text());

        if ($(element).text() == 'あり') {
            $(element).closest('div.fc-content').css('text-align', 'center');
            $(element).text('予約あり');
        }
        if ($(element).text() == 'なし') {
            $(element).closest('div.fc-content').css('text-align', 'center');
            $(element).text('予約なし');
        }
        if ($(element).text() == '販売中') {
            $(element).closest('div.fc-content').css('text-align', 'center');
            $(element).text('販売中');
        }
        if ($(element).text() == '未登録') {
            $(element).closest('div.fc-content').css('text-align', 'center');
            $(element).text('在庫データ未登録');
        }
        if ($(element).text() == '☆') {
            $(element).closest('div.fc-content').css('text-align', 'center');
            if ($(element).closest('div.fc-content').find('.org_title')) {
                let org_title = $(element).closest('div.fc-content').find('.org_title').val();
                $(element).text(org_title);
            }
        }
    });
}
$(window).resize(function() {
    //リサイズされたときの処理
    if (navigator.userAgent.match(/(iPhone|iPad|iPod|Android)/i) && $('div.item-list-pc').css('display') == 'none') {
        // SPサイズで「凡例」表示処理
        abbreviateTitle();
        dispLegend();
    } else {
        unAbbreviateTitle();
        dismissLegend();
    }
  });

