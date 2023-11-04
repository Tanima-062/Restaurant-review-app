$(function(){
    $('#visit').change(function() {
        let selectVisit = $('#visit').val();
        let hundredPercent = 100;
        let blank = '';
        if (selectVisit === 'AFTER') {
            $('#cancel_fee_input').val(hundredPercent);
            $('#cancel_fee_input').attr('readonly',true);
            $('.visit-delete').hide();
        }

        if (selectVisit === 'BEFORE') {
            $('#cancel_fee_input').val(blank);
            $('#cancel_fee_input').attr('readonly',false);
            console.log($('#cancel_limit').val());
            $('.visit-delete').show();
        }
    });
});