/**
 * Handle display of dependent, i. e. optional fieldsets
 *
 * Fieldsets may be defined as dependent on the value of a certain input. In
 * this case they contain a p element with the CSS class “bureaucracy_depends”.
 * This p element holds a span with the class “bureaucracy_depends_fname”
 * and optionally another span with “bureaucracy_depends_fvalue”. They
 * specify the target input (fname) and the target value for which the fieldset
 * is to be shown.
 *
 * This function adds onchange handlers to the relevant inputs for showing and
 * heading the respective fieldsets.
 *
 * @author Adrian Lang <dokuwiki@cosmocode.de>
 **/

addInitEvent(function () {
    var form = $('bureaucracy__plugin');
    if (!form) {
        /* No form – gtfo. */
        return;
    }
    var depends = getElementsByClass('bureaucracy_depends', form, 'p');
    if (depends.length === 0) {
        /* No fieldset with dependencies – gtfo. */
        return;
    }

    /**
     * onchange event handler
     *
     * This function changes the visibility of a fieldset depending on the
     * value of the input specified by “this”. this.dpar contains the target
     * value and the depending fieldset.
     **/
    function handle_update() {
        this.dpar.fset.style.display = (this.parentNode.parentNode.style.display !== 'none' &&
                                        (this.checked || this.type !== 'checkbox' &&
                                         (this.dpar.tval === true && this.value !== '') ||
                                         this.value === this.dpar.tval)) ? 'block' : 'none';
    }
    /* All labels in the form. */
    var labels = form.getElementsByTagName('label');

    for (var i = 0; i < depends.length ; ++i) {
        var fname = getElementsByClass('bureaucracy_depends_fname',
                                       depends[i], 'span')[0].innerHTML;
        var spans = getElementsByClass('bureaucracy_depends_fvalue',
                                       depends[i], 'span');
        var fvalue = (spans.length > 0) ? spans[0].innerHTML : true;

        for (var n = 0 ; n < labels.length ; ++n) {
            if (labels[n].firstChild.innerHTML === fname) {
                break;
            }
        }
        if (n === labels.length) return;

        var tvalues = labels[n].getElementsByTagName('input');
        if (tvalues.length === 0) tvalues = labels[n].getElementsByTagName('select');
        if (tvalues.length === 0) return;

        /* Get the input or select determining the visibility of this
           fieldset. Take the last one to ignore the hidden checkbox input. */
        var dvalue = tvalues[tvalues.length - 1];
        dvalue.dpar = {fset: depends[i].parentNode, tval: fvalue};
        dvalue.addEventListener('change', handle_update, false);
        handle_update.apply(dvalue);
        depends[i].style.display = 'none';
    }
});
