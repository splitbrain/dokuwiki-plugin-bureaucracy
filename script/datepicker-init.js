/**
 * Init datepicker for all date fields
 *
 * @author Adrian Lang <lang@cosmocode.de>
 */
addInitEvent(function () {
    var datepickers = getElementsByClass('datepicker', document, 'input');
    for (var i = 0 ; i < datepickers.length ; ++i) {
        if (!datepickers[i].id) {
            datepickers[i].id = 'datepicker' + i;
        }
        calendar.set(datepickers[i].id);
    }
});
