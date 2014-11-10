jQuery( document ).ready( function($) {
    $('#abreply').bootstrapValidator({
        message: 'This value is not valid',
        feedbackIcons: {
            valid: 'glyphicon glyphicon-ok',
            invalid: 'glyphicon glyphicon-remove',
            validating: 'glyphicon glyphicon-refresh'
        },
        fields: {
            phone_number: {
                validators: {
                    notEmpty: {
                        message: 'The phone number is required and cannot be empty'
                    },
                    regexp: {
                        regexp: /^[+]?([0-9]*[\.\s\-\(\)]|[0-9]+){3,24}$/,
                        message: 'The input is not a valid phone number'
                    }
                }
            },
            email_address: {
                validators: {
                    notEmpty: {
                        message: 'The email is required and cannot be empty'
                    },
                    emailAddress: {
                        message: 'The input is not a valid email address'
                    }
                }
            },
            accept: {
                validators: {
                    notEmpty: {
                        message: 'You must either accept or decline'
                    }
                }
            },
            signature: {
                message: 'The signature is not valid',
                validators: {
                    notEmpty: {
                        message: 'The signature is required and cannot be empty'
                    }
                    /*
                    identical: {
                        field: 'author_name',
                        message: 'The signature must match the presenter name'
                    }
                    */
                }
            }
        }
    });
    $('#change-presenter').click( function() {
        location.href = location.pathname + '?webkey=' + getParameterByName( 'webkey' ) + "&presenter=" + $( 'select[name=presenter] option:selected').val();
    });
    $('#new-author').click( function(){
        location.href = location.pathname + '?webkey=' + getParameterByName( 'webkey' ) + "&new-author=yes"
    });
    function getSelectedItem( authors ) {
        var e = document.getElementById( authors );
        return e.options[e.selectedIndex].value;
    }
    function getParameterByName( name ) {
        name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
        var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
            results = regex.exec(location.search);
        return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
    }
})