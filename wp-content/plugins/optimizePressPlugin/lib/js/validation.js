(function($){
    $(document).ready(function(){
        var $forms = $('form.op-optin-validation');
        var emailExp = /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*$/;
        $forms.submit(function() {
            var returnValue = true;
            $.each($(this).find('input[required="required"]'), function(i, field) {
                if ($(field).attr('name').indexOf('email') > -1 && false === emailExp.test($(field).val())) {
                    alert(OPValidation.labels.email);
                    returnValue = false;
                } else if ($(field).val().length == 0) {
                    alert(OPValidation.labels.text);
                    returnValue = false;
                }
            });
            return returnValue;
        });
    });
}(opjq));