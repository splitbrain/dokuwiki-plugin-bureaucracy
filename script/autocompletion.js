/**
 * Provides an asynchronously loaded autocompletion list for an input box.
 * An action plugin should handle AJAX_CALL_UNKNOWN for the ajax call ajaxcall.
 *
 * @param input      DOMref                  The HTML input box
 * @param ajaxcall   string                  The ajax request ›call‹ parameter
 * @param multi      bool                    Whether the input can hold
 *                                           multiple values
 * @param prepare_li DOMref (DOMref, mixed)  Callback for styling the li from a
 *                                           backend response list item; this
 *                                           function should set the _value
 *                                           property of the DOMref parameter
 *
 * @author Adrian Lang <lang@cosmocode.de>
 */


function AutoCompletion(input, ajaxcall, multi, prepare_li) {
    if (typeof Delay === 'undefined') return;

    var regex = multi ? /^((?:.*,)?)\s*([^,]*)$/
                      : /^()(.*)$/;

    function handle_click () {
                this.parentNode._rm();
                input.value = (input.value.replace(regex, '$1 ' + this._value)).match(/^\s*(.*)\s*$/)[1];
                if (multi) {
                    input.value += ', ';
                }
                input.focus();
                return false;
    }

    var ajax = new sack(DOKU_BASE + 'lib/exe/ajax.php');
    ajax.onCompletion = function () {
        var autoid = input.name.replace(/[\[\]]/g, '') + '__auto';
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
        ul.style.top = (findPosY(input) + input.offsetHeight - 1) + 'px';
        ul.style.left = findPosX(input) + 'px';
        ul.style.width = (input.offsetWidth - 10) + 'px';
        ul._rm = function () { this.parentNode.removeChild(this); };

        for (var index in values) {
            var li = prepare_li(document.createElement('li'), values[index]);
            addEvent(li, 'click', handle_click);
            ul.appendChild(li);
        }

        input.parentNode.insertBefore(ul, input.nextSibling);
    };

    var delay = new Delay(function () {
        ajax.setVar('call', ajaxcall);
        ajax.setVar('search', this.value.match(regex)[2]);
        ajax.runAJAX();
    });

    addEvent(input, 'keyup', function (e) { delay.start(this, e); });
}
