$(function () {
    // 読み込まれた時に料理ジャンル小2のoptionを取得
    $(document).ready(function(){
        const cooking_small_genres = $("[id*='cooking_small_genre_']");
        const cooking_middle_genres = $("[id*='cooking_middle_genre_']");

        if (cooking_small_genres.length === 0) {
            return;
        }

        for (let i = 0; i < cooking_small_genres.length; ++i) {
            let selected = $("option:selected", cooking_small_genres[i]).val();
            let parentValue = $("option:selected", cooking_middle_genres[i]).val();
            const appCd = "TORS";
            if (selected.length === 0) {
                return;
            }

            $.ajax({
                url: location.origin + '/admin/common/genre/list',
                type: 'GET',
                dataType: 'json',
                data: {'genre_cd': selected, 'app_cd': appCd, 'level':4, 'parent_value': parentValue},
                timeout: 5000,
                context: $(this).attr('id')
            })
            .done(function(result) {
                let small2_genre = $("[id*='cooking_small2_genre_']");
                let small = $(small2_genre[i]).attr('id');
                if ($('#' + small).val() === "") {
                    $('#' + small + ' > option').remove();
                    $('#' + small).append($('<option>').html('- ジャンル(小)2 -').val(''));

                    $.each(result.ret, function(key, item) {
                        $('#' + small).append($('<option>').html(item.name).val(item.genre_cd));
                    });
                }
            })
            .fail(function() {
                alert('カテゴリの取得に失敗しました');
            });
        }
    });

    // ジャンルマスタ 大カテゴリ変更時
    $(document).on('change', '#big_genre', function() {
        const value = $(this).val();
        const appCd = $('#app_cd').val();

        if (value.length === 0) {
            return;
        }

        if (value.toLowerCase() === 'b-cooking') {
            $('#small_genre').show();
        } else {
            $('#small_genre').hide();
        }

        $.ajax({
            url: location.origin + '/admin/common/genre/list',
            type: 'GET',
            dataType: 'json',
            data: {'genre_cd': value, 'app_cd': appCd, 'level':2},
            timeout: 5000,
        })
        .done(function(result) {
            $('#middle_genre > option').remove();
            $('#middle_genre').append($('<option>').html('- ジャンル(中) -').val(''));
            $('#small_genre > option').remove();
            $('#small_genre').append($('<option>').html('- ジャンル(小) -').val(''));
            $.each(result.ret, function(key, item) {
                $('#middle_genre').append($('<option>').html(item.name).val(item.genre_cd));
            });
        })
        .fail(function() {
            alert('カテゴリの取得に失敗しました');
        });
    });

    // ジャンルマスタ 中カテゴリ変更時
    $(document).on('change', '#middle_genre', function() {
        const value = $(this).val();
        const appCd = $('#app_cd').val();
        const parentValue = $('#big_genre').val();

        if (value.length === 0) {
            return;
        }

        $.ajax({
            url: location.origin + '/admin/common/genre/list',
            type: 'GET',
            dataType: 'json',
            data: {'genre_cd': value, 'app_cd': appCd, 'level':3, 'parent_value': parentValue},
            timeout: 5000,
        })
        .done(function(result) {
            $('#small_genre > option').remove();
            $('#small_genre').append($('<option>').html('- ジャンル(小) -').val(''));
            $('#small2_genre > option').remove();
            $('#small2_genre').append($('<option>').html('- ジャンル(小)2 -').val(''));
            $.each(result.ret, function(key, item) {
                $('#small_genre').append($('<option>').html(item.name).val(item.genre_cd));
            });
        })
        .fail(function() {
            alert('カテゴリの取得に失敗しました');
        });
    });

    // ジャンルマスタ 小カテゴリ変更時
    $(document).on('change', '#small_genre', function() {
        const value = $(this).val();
        const appCd = $('#app_cd').val();
        const parentValue = $('#middle_genre').val();

        if (value.length === 0) {
            return;
        }

        $('#small2_genre > option').remove();
        $('#small2_genre').append($('<option>').html('- ジャンル(小)2 -').val(''));

        $.ajax({
            url: location.origin + '/admin/common/genre/list',
            type: 'GET',
            dataType: 'json',
            data: {'genre_cd': value, 'app_cd': appCd, 'parent_value': parentValue, 'level':4},
            timeout: 5000,
        })
        .done(function(result) {
            $.each(result.ret, function(key, item) {
                $('#small2_genre').append($('<option>').html(item.name).val(item.genre_cd));
            });
        })
        .fail(function() {
            alert('カテゴリの取得に失敗しました');
        });
    });

    // 店舗ジャンル追加時にロードが起こった時（バリデーションエラーが起こった時）
    $(document).ready(function(){

        // 中ジャンルが変更された時
        const middleGenreValue = $('#middle_genre').val();
        const middleGenreAppCd = $('#app_cd').val();
        const middleGenreParentValue = $('#big_genre').val();

        // 中ジャンルがundefinedの場合
        if (typeof middleGenreValue === 'undefined') {
            return;
        }

        if (middleGenreValue.length === 0) {
            return;
        }
        $.when(
            $.ajax({
                url: location.origin + '/admin/common/genre/list',
                type: 'GET',
                dataType: 'json',
                data: {'genre_cd': middleGenreValue, 'app_cd': middleGenreAppCd, 'level':3, 'parent_value': middleGenreParentValue},
                timeout: 5000,
            })
            .done(function(result) {
                $('#small_genre > option').remove();
                $('#small_genre').append($('<option>').html('- ジャンル(小) -').val(''));
                $.each(result.ret, function(key, item) {
                    if(oldSmallGenre === item.genre_cd){
                        $('#small_genre').append($('<option>').html(item.name).prop('selected', true).val(item.genre_cd));
                    } else {
                        $('#small_genre').append($('<option>').html(item.name).val(item.genre_cd));
                    }
                });
            })
            .fail(function() {
                alert('カテゴリの取得に失敗しました');
            })

        // 少ジャンルが変更された時
        ).done(function(){
            const smallGenreValue = $('#small_genre').val();
            const smallGenreAppCd = $('#app_cd').val();
            const smallGenreParentValue = $('#big_genre').val();

            // 小ジャンルがundefinedの場合
            if (typeof smallGenreValue === 'undefined') {
                return;
            }

            if (smallGenreValue.length === 0) {
                return;
            }

            $('#small2_genre > option').remove();
            $('#small2_genre').append($('<option>').html('- ジャンル(小)2 -').val(''));

            $.ajax({
                url: location.origin + '/admin/common/genre/list',
                type: 'GET',
                dataType: 'json',
                data: {'genre_cd': smallGenreValue, 'app_cd': smallGenreAppCd, 'parent_smallGenreValue': smallGenreParentValue, 'level':4},
                timeout: 5000,
            })
            .done(function(result) {
                $.each(result.ret, function(key, item) {
                    if(oldSmall2Genre === item.genre_cd){
                        $('#small2_genre').append($('<option>').html(item.name).prop('selected', true).val(item.genre_cd));
                    } else {
                        $('#small2_genre').append($('<option>').html(item.name).val(item.genre_cd));
                    }
                });
            })
            .fail(function() {
                alert('カテゴリの取得に失敗しました');
            });
        });
    });


    // メニュー・店舗こだわりジャンル カテゴリ更新時(中カテゴリ)
    $(document).on('change', 'select[name="middle_genre\[\]"]', function() {
        const value = $(this).val();
        const appCd = $('#app_cd').val();
        const parentValue = $('#big_genre').val();

        if (value.length === 0) {
            return;
        }

        $.ajax({
            url: location.origin + '/admin/common/genre/list',
            type: 'GET',
            dataType: 'json',
            data: {'genre_cd': value, 'app_cd': appCd, 'level':3, 'parent_value': parentValue},
            timeout: 5000,
            context: $(this).attr('id')
        })
        .done(function(result) {
            const small = this.replace('middle', 'small');
            $('#' + small + ' > option').remove();
            $('#' + small).append($('<option>').html('- ジャンル(小) -').val(''));
            $.each(result.ret, function(key, item) {
                $('#' + small).append($('<option>').html(item.name).val(item.genre_cd));
            });
        })
        .fail(function() {
            alert('カテゴリの取得に失敗しました');
        });
    });

    // メニュー・店舗こだわりジャンル カテゴリ更新時(小カテゴリ)
    $(document).on('change', 'select[name="small_genre\[\]"]',function() {
        const value = $(this).val();
        const parentValue = $('#' + $(this).attr('id').replace('small', 'middle')).val();
        const appCd = $('#app_cd').val();

        if (value.length === 0) {
            return;
        }

        $.ajax({
            url: location.origin + '/admin/common/genre/list',
            type: 'GET',
            dataType: 'json',
            data: {'genre_cd': value, 'app_cd': appCd, 'parent_value': parentValue, 'level':4},
            timeout: 5000,
            context: $(this).attr('id')
        })
        .done(function(result) {
            const small = this.replace('small', 'small2');
            $('#' + small + ' > option').remove();
            $('#' + small).append($('<option>').html('- ジャンル(小)2 -').val(''));
            $.each(result.ret, function(key, item) {
                $('#' + small).append($('<option>').html(item.name).val(item.genre_cd));
            });
        })
        .fail(function() {
            alert('カテゴリの取得に失敗しました');
        });
    });

    // 店舗料理ジャンル カテゴリ更新時(中カテゴリ)
    $(document).on('change', 'select[name="cooking_middle_genre\[\]"]', function() {
        const value = $(this).val();
        const appCd = $('#app_cd').val();
        const parentValue = $('#cook_genre').val();

        if (value.length === 0) {
            return;
        }

        $.ajax({
            url: location.origin + '/admin/common/genre/list',
            type: 'GET',
            dataType: 'json',
            data: {'genre_cd': value, 'app_cd': appCd, 'level':3, 'parent_value': parentValue},
            timeout: 5000,
            context: $(this).attr('id')
        })
        .done(function(result) {
            const small = this.replace('middle', 'small');
            $('#' + small + ' > option').remove();
            $('#' + small).append($('<option>').html('- ジャンル(小) -').val(''));
            const small2 = this.replace('middle', 'small2');
            $('#' + small2 + ' > option').remove();
            $('#' + small2).append($('<option>').html('- ジャンル(小)2 -').val(''));
            $.each(result.ret, function(key, item) {
                $('#' + small).append($('<option>').html(item.name).val(item.genre_cd));
            });
        })
        .fail(function() {
            alert('カテゴリの取得に失敗しました');
        });
    });

    // 店舗料理ジャンル カテゴリ更新時(小カテゴリ)
    $(document).on('change', 'select[name="cooking_small_genre\[\]"]',function() {
        const value = $(this).val();
        const parentValue = $('#' + $(this).attr('id').replace('small', 'middle')).val();
        const appCd = $('#app_cd').val();

        if (value.length === 0) {
            return;
        }

        $.ajax({
            url: location.origin + '/admin/common/genre/list',
            type: 'GET',
            dataType: 'json',
            data: {'genre_cd': value, 'app_cd': appCd, 'parent_value': parentValue, 'level':4},
            timeout: 5000,
            context: $(this).attr('id')
        })
        .done(function(result) {
            const small = this.replace('small', 'small2');
            $('#' + small + ' > option').remove();
            $('#' + small).append($('<option>').html('- ジャンル(小)2 -').val(''));
            $.each(result.ret, function(key, item) {
                $('#' + small).append($('<option>').html(item.name).val(item.genre_cd));
            });
        })
        .fail(function() {
            alert('カテゴリの取得に失敗しました');
        });
    });

    $(document).on('click', '#delete', function() {
        const id = $(this).children().attr('id');
        const parentPage = ($('#big_genre').val().toLowerCase() === 'b-cooking') ? 'menu' : 'store';

        if (parentPage == 'menu' && ($('input[name="genre_group_id[]"]').length - $('.add_form-group').length) <= 1 && $('#menu_published').val() > 0) {
            alert('公開中のメニューは1つ以上のジャンル設定が必要なため削除できません');
        } else if (confirm('このジャンルの行を削除しますか？')) {
            $.ajax({
                url: location.origin + '/admin/' + parentPage + '/genre/delete/' + id,
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
