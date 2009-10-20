function update_status (val) {
    if (val.cur.checked) {
        var cur = true;
    } else {
        var cur = val.cur.value;
    }
    if (cur == val.target) {
        val.fieldset.style.display = 'block';
    } else {
        val.fieldset.style.display = 'none';
    }
}

function bind_val(func, val) {
    return function () {
        return func(val);
    }
}

addInitEvent(function () {
    var form = $('bureaucracy__plugin');
    if (!form) return;
    var depends = getElementsByClass('bureaucracy_depends', form, 'p');
    if (!depends) return;
    var labels = form.getElementsByTagName('label');
    var dependencies = Array();
    for (var i = 0; i < depends.length ; ++i) {
        var fname = getElementsByClass('bureaucracy_depends_fname', depends[i], 'span')[0].innerHTML;
        var spans = getElementsByClass('bureaucracy_depends_fvalue', depends[i], 'span');
        if (spans != '') {
            var fvalue = spans[0].innerHTML;
        } else {
            var fvalue = true;
        }
        for (var n = 0 ; n < labels.length ; ++n) {
            if (labels[n].firstChild.innerHTML === fname) {
                break;
            }
        }
        if (n == labels.length) return;
        var dvalue = labels[n].getElementsByTagName('input')[0];
        if (!dvalue) dvalue = labels[n].getElementsByTagName('select')[0];
        dependencies[i] = {target: fvalue, cur: dvalue, fieldset: depends[i].parentNode};
        update_status(dependencies[i]);
        dependencies[i].cur.onchange = bind_val(update_status, dependencies[i]);
    }
});
