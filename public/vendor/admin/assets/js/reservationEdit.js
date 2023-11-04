$.datetimepicker.setLocale('ja');
$(function () {
    $("#pick_up_datetime").datetimepicker({format:'Y-m-d H:i'});
    $("#reset").on("click", function () {
        $(".condition").val("");
        $(".condition").trigger("change");
        $(this).blur();
    });
    $(".condition").on("change", function () {
        if ($(this).val() === "") {
            $(this).removeClass("is-valid");
        } else {
            $(this).addClass("is-valid");
        }
    });
    $(".condition").trigger("change");
});

$(function () {
    $(document).on('click', '#saveMessageBoard', function () {
        const messageBoardMessage = $('#messageBoardMessage').val().trim();
        if (messageBoardMessage.length === 0) {
            return;
        }

        $('#saveMessageBoard').prop('disabled', true);

        $.ajax({
            url: location.origin + '/admin/reservation/save_message_board',
            type: 'POST',
            dataType: 'json',
            data: {
                reservation_id : $('#reservation_id').val(),
                message: messageBoardMessage
            },
            timeout: 5000,
        })
        .done(function(res) {
            if (res && res.ret === 'ok') {
                alert('登録しました');
                location.reload(true);
            } else {
                alert('Error:登録に失敗しました。' + res.message);
            }
        })
        .fail(function() {
            alert('登録に失敗しました');
        })
        .always(function () {
            $('#saveMessageBoard').prop('disabled', false);
        });
    });

    // 予約情報変更
    $(document).on('click', '#updateReservationInfo', function () {
        const reservationStatus = $('#reservation_status').val();
        const pickUpDatetime = $('#pick_up_datetime').val().trim();
        const persons = $('#persons').val();

        if (!window.confirm('予約情報を更新してもよろしいですか？')) {
            return;
        }

        $('#updateReservationInfo').prop('disabled', true);

        $.ajax({
            type: 'POST',
            dataType: 'json',
            timeout: 10000,
            url: location.origin + '/admin/reservation/update_reservation_info',
            data: {
                reservation_id : $('#reservation_id').val(),
                reservation_status : reservationStatus,
                pick_up_datetime : pickUpDatetime,
                persons : persons,
            }
        })
        .done(function (res) {
            if (res && res.ret === 'ok') {
                alert('更新しました');
                location.reload(true);
            } else {
                alert('Error:更新に失敗しました。' + res.message);
            }
        })
        .fail(function () {
            alert('Fail:更新に失敗しました');
        })
        .always(function () {
            $('#updateReservationInfo').prop('disabled', false);
        });
    });

    $(document).on('click', '#clearAdminChangeInfo', function () {
        if (!window.confirm('予約変更を取り消してもよろしいですか？')) {
            return;
        }

        $('#clearAdminChangeInfo').prop('disabled', true);

        $.ajax({
            type: 'POST',
            dataType: 'json',
            timeout: 10000,
            url: location.origin + '/admin/reservation/clear_admin_change_info',
            data: {
                reservationId: $('#reservation_id').val()
            }
        })
        .done(function(res) {
            if (res && res.ret === 'ok') {
                alert('予約変更を取り消しました。');
                location.reload(true);
            } else {
                alert('Error:更新に失敗しました。' + res.message);
            }
        })
        .fail(function () {
            alert('Fail:更新に失敗しました');
        })
        .always(function () {
            $('#clearAdminChangeInfo').prop('disabled', false);
        });
    })

    // 予約キャンセル
    $(document).on('click', '#reservationCancel', function () {
        if (!window.confirm('予約をキャンセルしてもよろしいですか？')) {
            return;
        }

        $('#reservationCancel').prop('disabled', true);

        $.ajax({
            type: 'POST',
            dataType: 'json',
            timeout: 10000,
            url: location.origin + '/admin/reservation/cancel_reservation',
            data: {
                reservation_id : $('#reservation_id').val()
            }
        })
            .done(function (res) {
                if (res && res.ret === 'ok') {
                    alert('更新しました');
                    location.reload(true);
                } else {
                    alert('Error:更新に失敗しました。' + res.message);
                }
            })
            .fail(function () {
                alert('Fail:更新に失敗しました');
            })
            .always(function () {
                $('#reservationCancel').prop('disabled', false);
            });
    });

    // お客様都合の予約キャンセル
    $(document).on('click', '#reservationCancelForUser', function () {
        if (!window.confirm('お客様都合の予約キャンセルを実行してもよろしいですか？')) {
            return;
        }

        $('#reservationCancelForUser').prop('disabled', true);
        $('#reservationCancelForAdmin').prop('disabled', true);

        $.ajax({
            type: 'POST',
            dataType: 'json',
            timeout: 10000,
            url: location.origin + '/admin/reservation/cancel_reservation_for_user',
            data: {
                reservation_id : $('#reservation_id').val()
            }
        })
            .done(function (res) {
                if (res && res.ret === 'ok') {
                    alert('更新しました');
                    location.reload(true);
                } else {
                    alert('Error:更新に失敗しました。' + res.message);
                }
            })
            .fail(function () {
                alert('Fail:更新に失敗しました');
            })
            .always(function () {
                $('#reservationCancelForUser').prop('disabled', false);
                $('#reservationCancelForAdmin').prop('disabled', false);
            });
    });

    // お店都合の予約キャンセル
    $(document).on('click', '#reservationCancelForAdmin', function () {
        if (!window.confirm('お店都合の予約キャンセルを実行してもよろしいですか？')) {
            return;
        }

        $('#reservationCancelForUser').prop('disabled', true);
        $('#reservationCancelForAdmin').prop('disabled', true);

        $.ajax({
            type: 'POST',
            dataType: 'json',
            timeout: 10000,
            url: location.origin + '/admin/reservation/cancel_reservation_for_admin',
            data: {
                reservation_id : $('#reservation_id').val()
            }
        })
            .done(function (res) {
                if (res && res.ret === 'ok') {
                    alert('更新しました');
                    location.reload(true);
                } else {
                    alert('Error:更新に失敗しました。' + res.message);
                }
            })
            .fail(function () {
                alert('Fail:更新に失敗しました');
            })
            .always(function () {
                $('#reservationCancelForUser').prop('disabled', false);
                $('#reservationCancelForAdmin').prop('disabled', false);
            });
    });

    $(document).on('click', '#updateDelegateInfo', function() {
        const lastName = $('#last_name').val().trim();
        const firstName = $('#first_name').val().trim();
        const tel = $('#tel').val().trim();
        const email = $('#email').val().trim();

        if (!tel.match(/^(0[5-9]0[0-9]{8}|0[1-9][1-9][0-9]{7})$/)) {
            alert('電話番号を正しく入力してください');
            return;
        }

        if (!email.match(/^[A-Za-z0-9]{1}[A-Za-z0-9_.-]*@{1}[A-Za-z0-9_.-]{1,}\.[A-Za-z0-9]{1,}$/)) {
            alert('メールアドレスを正しく入力してください');
            return;
        }

        $('#updateDelegateInfo').prop('disabled', true);

        $.ajax({
            type: 'POST',
            dataType: 'json',
            timeout: 10000,
            url: location.origin + '/admin/reservation/update_delegate_info',
            data: {
                reservation_id : $('#reservation_id').val(),
                last_name : lastName,
                first_name : firstName,
                tel : tel,
                email : email,
            }
        })
        .done(function (res) {
            if (res && res.ret === 'ok') {
                alert('変更しました');
                location.reload(true);
            } else {
                alert('Error:変更に失敗しました。' + res.message);
            }
        })
        .fail(function () {
            alert('Fail:変更に失敗しました');
        })
        .always(function () {
            $('#updateDelegateInfo').prop('disabled', false);
        });
    });

    $(document).on('click', '#sendReservationMail', function () {

        $('#sendReservationMail').prop('disabled', true);

        $.ajax({
            type: 'POST',
            dataType: 'json',
            timeout: 10000,
            url: location.origin + '/admin/reservation/send_reservation_mail',
            data: {
                reservation_id : $('#reservation_id').val()
            }
        })
        .done(function (res) {
            if (res && res.ret === 'ok') {
                alert('メールを送信しました');
                location.reload(true);
            } else {
                alert('Error:送信に失敗しました。' + res.message);
            }
        })
        .fail(function () {
            alert('Fail:送信に失敗しました');
        })
        .always(function () {
            $('#sendReservationMail').prop('disabled', false);
        });
    });
});
