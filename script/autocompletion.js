/**
 * Provides an asynchronously loaded autocompletion list for an input box.
 * An action plugin should handle AJAX_CALL_UNKNOWN for the ajax call ajaxcall.
 *
 * @param input      DOMref                  The HTML input box
 * @param ajaxcall   string                  The ajax request ›call‹ parameter
 * @param multi      bool                    Whether the input can hold
 *                                           multiple values
 *
 * @author Adrian Lang <lang@cosmocode.de>
 */

function addAutoCompletion(input, ajaxcall, multi, prepareLi, styleList) {
    if (typeof Delay === 'undefined') return;
    var autoid = (input.name || input.id).replace(/[\[\]]/g, '') + '__auto';
    input.setAttribute('autocomplete', 'off');

    styleList = styleList || function (ul, input) {
        ul.style.top = (input.offsetTop + input.offsetHeight - 1) + 'px';
        ul.style.left = input.offsetLeft + 'px';
        ul.style.minWidth = input.offsetWidth - 10 + 'px';
    };

    prepareLi = prepareLi || function (li, value) {
        li.innerHTML = '<a href="#">' + value[1] + '</a>';
        li._value = value[1];
    };

    var regex = multi ? /^((?:.*,)?)\s*([^,]*)$/
                      : /^()(.*)$/;

    var delay = new Delay(function () {
        ajax.setVar('call', ajaxcall);
        ajax.setVar('search', this.value.match(regex)[2]);
        ajax.runAJAX();
    });

    function getCurSel() {
        var auto = $(autoid);
        if (!auto) return;
        var oldsel = getElementsByClass('auto_cur', auto, 'li');
        return (oldsel.length > 0) ? oldsel[0] : null;
    }

    function highlight(item) {
        var oldsel = getCurSel();
        if (oldsel) {
            oldsel.className = oldsel.className.replace(/\s+auto_cur\b/g, '');
        }
        item.className += ' auto_cur';
    }

    function handle_click() {
        this.parentNode._rm();
        input.value = (input.value.replace(regex, '$1 ' + this._value)).match(/^\s*(.*)\s*$/)[1];
        if (multi) {
            input.value += ', ';
        }
        input.focus();
        delay.delTimer();
        return false;
    }

    var ajax = new sack(DOKU_BASE + 'lib/exe/ajax.php');
    ajax.onCompletion = function () {
        var values = eval(this.response);
        var oldul = $(autoid);
        if (oldul) {
            oldul._rm();
        }
        if (!values) return;

        // Strip out already selected values
        var n_values = [];
        for (var value in values) {
            if (value === '') continue;
            var str = input.value.match(regex)[1];
            do {
                var index = str.indexOf(value);
                if (index === -1) {
                    n_values.push([value, values[value]]);
                    break;
                }
                if ((index === 0 || str.charAt(index - 1).match(/[\s,]/)) &&
                    (index + value.length === str.length || str.charAt(index + value.length).match(/[\s,]/))) {
                    break;
                }
                str = str.slice(index + value.length);
            } while (true);
        }
        if (n_values.length === 0) return;
        values = n_values;

        // Create list
        var ul = document.createElement('ul');
        ul.className = 'autocompletion ' + ajaxcall + '__auto';
        ul.id = autoid;
        ul._rm = function () {
            this.parentNode.parentNode.removeChild(this.parentNode);
            this._rm = function () {};
        };
        styleList(ul, input);

        for (var index in values) {
            var li = document.createElement('li');
            prepareLi(li, values[index]);
            addEvent(li, 'click', handle_click);
            addEvent(li, 'mouseover', function (e) {
                var p = e.relatedTarget || e.fromElement;
                while (p && p !== this) {
                    p = p.parentNode;
                }
                if (p === this) {
                    return;
                }
                highlight(this);
            });
            ul.appendChild(li);
        }

        var div = document.createElement('div');
        div.appendChild(ul);
        // Since the div has no dimensions, the ul inside flows over,
        // but JSpopup has overflow: hidden.
        div.style.overflow = 'visible';
        div.className = 'JSpopup';
        input.parentNode.appendChild(div);

        if (ul.currentStyle) {
            // IE fix: http://www.quirksmode.org/bugreports/archives/2006/01/Explorer_z_index_bug.html
            input.parentNode.style.zIndex = 1 + ul.currentStyle.zIndex;
        }
    };

    addEvent(input, 'keyup', function (e) {
        if (e.keyCode !== 40 && e.keyCode !== 38) delay.start(this, e);
    });

    addEvent(input, 'click', function (e) { delay.start(this, e); });

    addEvent(input,'keydown', function (e) {
        if (e.keyCode !== 40 && e.keyCode !== 38 && e.keyCode !== 39 &&
            e.keyCode !== 13) {
            return;
        }
        var oldsel = getCurSel();
        if (e.keyCode === 13 || e.keyCode === 39) {
            if (oldsel) {
                handle_click.call(oldsel);
                return false;
            }
            return;
        }
        var auto = $(autoid);
        if (!auto) {
            return;
        }
        var moves = e.keyCode === 40 ? ['firstChild', 'nextSibling'] :
                                       ['lastChild', 'previousSibling'];
        var target = auto[moves[0]];
        if (oldsel && oldsel[moves[1]]) {
            target = oldsel[moves[1]];
        }
        highlight(target);
    });
}
