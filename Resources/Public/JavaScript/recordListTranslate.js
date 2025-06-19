import $ from "jquery";
$( ".ai-button" ).on( "click", function() {
    var uid = $(this).data('uid');
    var mode = $(this).data('mode');
    var defaultlanguage = $(this).data('defaultlanguage');
    var parentDiv = $('.ai-button').closest('.btn-group');
    // Find all occurrences of ai-button except 
    var aiButtons = parentDiv.find('.ai-button').not(this);

    aiButtons.each(function(index, element) {
        $(element).prop('checked', false);
    });


 $(this).parent().parent().siblings().each(function() {
        if ($(this).data('state') === undefined || $(this).data('state') === null) {
            var url = $(this).attr('href');
            var lastIndex = url.lastIndexOf('&cmd[localization]');
            var urlPart = (lastIndex > 0) ? url.substring(0, lastIndex) : url;
            if (document.getElementById(mode + '-translation-enable-' + uid).checked == true) {
                var newUrl = $(this).attr('href', urlPart + '&cmd[tt_content][1][localizeConfiguration][mode]=' + mode + '&cmd[tt_content][1][localizeConfiguration][srcLanguageId]= '+defaultlanguage);
            }
        }

    });

});