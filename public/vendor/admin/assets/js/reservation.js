$.datetimepicker.setLocale('ja');
$(function () {
    $("#pick_up_datetime_from, #pick_up_datetime_from-sp").datetimepicker({format:'Y-m-d H:i'});
    $("#pick_up_datetime_to, #pick_up_datetime_to-sp").datetimepicker({format:'Y-m-d H:i'});
    $("#created_at_from, #created_at_from-sp").datetimepicker({format:'Y-m-d H:i'});
    $("#created_at_from, #created_at_from-sp").datetimepicker({format:'Y-m-d H:i'});
    $("#created_at_to, #created_at_to-sp").datetimepicker({format:'Y-m-d H:i'});
    $(document).on("click", "#reset", function () {
        $(".condition").val("");
        $(".condition").trigger("change");
        $(this).blur();
		let $form = $(this).closest('form');
        $form.find("textarea, :text, select").val("").end().find(":checked").prop("checked", false);
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
