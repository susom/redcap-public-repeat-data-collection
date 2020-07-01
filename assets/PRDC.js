$(document).ready(function() {

    // Get the div around the modal and reposition it into the visible body
    // since the pagecontent is currently hidden by css
    let e = $('#PRDC').detach().appendTo('body');

    // Bind the cancel button to close the modal and reveal the default public survey
    $('.btn-cancel', e).bind('click', function() {
        $(".modal", e).hide();
        $('#container').show();
    });

    // Bind the search button
    $('button[name="search"]', e).bind('click', function() {

        // Get the input field
        let i = $('input', e).val();

        if (i.length === 0) {
            // console.log('The input is empty');
            return;
        }

        // Do an ajax validation

        $('#search-spinner').css({opacity: 1});

        $.ajax({
            url: PRDC.lookupUrl,
            type: "POST",
            data: {search: i},
            dataType: "json",
            success: function(data) {

                console.log(data);
                // result = found or not-found

                let container = $('div.lookup-result').empty();

                // Add a comment
                if (data.comment) {
                    var comment = $('<p class="text-secondary text-center"></p>')
                        .html(data.comment)
                        .appendTo(container);
                }

                // Add a button
                if (data.buttonText) {
                    let btnClass = data.btnClass || "btn-primary";

                    var btn = $('<button></button>')
                        .addClass('btn')
                        .addClass(btnClass)
                        .html(data.buttonText);

                    if (data.buttonAction === "close") {
                        btn.on('click', function() {
                            var field = $('input[name="' + data.field + '"]');
                            //if (field.length === 1) field.val(i).trigger('blur');
                            if (field.length === 1) field.val(i);
                            $('.btn-cancel').trigger('click');
                        });
                    } else if (data.buttonUrl) {
                        btn.on('click', function(){
                            window.location.replace(data.buttonUrl)
                        });
                    }

                    btn.appendTo(container);
                }
                $('#search-spinner').css({opacity: 0});
            }
        });

    });

    $(".modal", e).show();
    $("input", e).focus();

});
