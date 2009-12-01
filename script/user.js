addInitEvent(function () {
    var userpickers = getElementsByClass('userpicker', document, 'input');

    function handler () {
        var input = this;
        var ajax = new sack(DOKU_BASE + 'lib/exe/ajax.php');
        var autoid = input.name.replace(/[\[\]]/g, '') + '__auto';

        ajax.setVar('call', 'bureaucracy_user_field');
        ajax.setVar('search', this.value);
        ajax.onCompletion = function () {
            var users = eval(this.response);
            var oldul = $(autoid);
            if (oldul) {
                oldul.parentNode.removeChild(oldul);
            }
            if (!users || users.length === 0) return;
            var ul = document.createElement('ul');
            ul.className = 'bureaucracy_user_auto';
            ul.id = autoid;
            ul.style.top = (findPosY(input) + input.offsetHeight - 1) + 'px';
            ul.style.left = findPosX(input) + 'px';
            ul.style.width = (input.offsetWidth - 10) + 'px';
            for (var name in users) {
                var li = document.createElement('li');
                li.innerHTML = '<a href="#">' + users[name] + ' (' + name + ')' + '</a>';
                li.id = 'bureaucracy__user__' + name.replace(/\W/g, '_');
                li._user = name;
                ul.appendChild(li);
                addEvent(li, 'click', function () {
                    input.value = this._user;
                    this.parentNode.parentNode.removeChild(this.parentNode);
                    return false;
                });
            }
            input.parentNode.insertBefore(ul, input.nextChild);
        };
        ajax.runAJAX();
    }

    function event_handler (delay) {
        return function (e) {
            delay.start.call(delay, this, e);
        };
    }

    for (var i = 0 ; i < userpickers.length ; ++i) {
        var delay = event_handler(new Delay(handler));
        addEvent(userpickers[i], 'keyup', delay);
    }
});
