/**
 * Init datepicker for all date fields
 */

jQuery(function(){
    jQuery('.datepicker').datepicker({
        dateFormat: "yy-mm-dd",
        changeMonth: true,
        changeYear: true
    });
});