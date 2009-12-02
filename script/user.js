/**
 * Provides a list of matching user names while user inputs into a userpicker
 *
 * @author Adrian Lang <lang@cosmocode.de>
 */
addInitEvent(function () {
    var regexes = {
        'userpicker': /^()(.*)$/,
        'userspicker': /^((?:.*,)?)\s*([^,]*)$/
    };
    var ajax = new sack(DOKU_BASE + 'lib/exe/ajax.php');
    function manageResponse (input, autoid) {
        var regex = regexes[input.className.match(/users?picker/)[0]];

        var users = eval(this.response);
        var oldul = $(autoid);
        if (oldul) {
            oldul.parentNode.removeChild(oldul);
        }
        if (!users) return;

        // Strip out already selected user names
        n_users = [];
        for (var name in users) {
            var str = input.value.match(regex)[1];
            do {
                var index = str.indexOf(name);
                if (index === -1) {
                    n_users.push([name, users[name]]);
                    break;
                }
                if ((index === 0 || str.charAt(index - 1).match(/[\s,]/)) &&
                    (index + name.length === str.length || str.charAt(index + name.length).match(/[\s,]/))) {
                    break;
                }
                str = str.slice(index + name.length);
            } while (true);
        }
        users = n_users;

        if (users.length === 0) return;
        var ul = document.createElement('ul');
        ul.className = 'bureaucracy_user_auto';
        ul.id = autoid;
        ul.style.top = (findPosY(input) + input.offsetHeight - 1) + 'px';
        ul.style.left = findPosX(input) + 'px';
        ul.style.width = (input.offsetWidth - 10) + 'px';
        for (var index in users) {
            var name = users[index][0];
            var fullname = users[index][1];
            var li = document.createElement('li');
            li.innerHTML = '<a href="#">' + fullname + ' (' + name + ')' + '</a>';
            li.id = 'bureaucracy__user__' + name.replace(/\W/g, '_');
            li._user = name;
            ul.appendChild(li);
            addEvent(li, 'click', function () {
                input.value = (input.value.replace(regex, '$1 ' + this._user)).match(/\s*(.*)\s*/)[1];
                this.parentNode.parentNode.removeChild(this.parentNode);
                input.focus();
                return false;
            });
        }
        input.parentNode.insertBefore(ul, input.nextSibling);
    };

    function handler () {
        ajax.setVar('call', 'bureaucracy_user_field');
        ajax.setVar('search', this.value.match(regexes[this.className.match(/users?picker/)[0]])[2]);
        ajax.onCompletion = bind(manageResponse, this, this.name.replace(/[\[\]]/g, '') + '__auto');
        ajax.runAJAX();
    }

    function event_handler (delay) {
        return function (e) {
            delay.start.call(delay, this, e);
        };
    }

    var userpickers = getElementsByClass('userpicker', document, 'input');
    for (var i = 0 ; i < userpickers.length ; ++i) {
        var delay = event_handler(new Delay(handler));
        addEvent(userpickers[i], 'keyup', delay);
    }

    userpickers = getElementsByClass('userspicker', document, 'input');
    for (var i = 0 ; i < userpickers.length ; ++i) {
        var delay = event_handler(new Delay(handler));
        addEvent(userpickers[i], 'keyup', delay);
    }
});
