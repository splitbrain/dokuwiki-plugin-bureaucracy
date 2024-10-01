/**
 * Detecting data changes in forms using jQuery
 *
 * @author Eduardo Mozart de Oliveira <eduardomozart182@gmail.com>
 */
jQuery(function () {
    var initdata = jQuery('form.bureaucracy__plugin').serialize();
    var submitted = false;
    
    jQuery(window).on('beforeunload', function (event) {
        var nowdata = jQuery('form.bureaucracy__plugin').serialize();
        
        if (initdata !== nowdata && !submitted) {
            event.stopPropagation();
            event.preventDefault();
            
            event.returnValue = true;
            return true;
        }
    });
    
    jQuery("form.bureaucracy__plugin").submit(function() {
        submitted = true;
    });
});
