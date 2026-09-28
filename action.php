<?php

use dokuwiki\Extension\ActionPlugin;
use dokuwiki\Extension\EventHandler;
use dokuwiki\Extension\Event;

/**
 * Bureaucracy Plugin: Allows flexible creation of forms
 *
 * This plugin allows definition of forms in wiki pages. The forms can be
 * submitted via email or used to create new pages from templates.
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <andi@splitbrain.org>
 * @author     Adrian Lang <dokuwiki@cosmocode.de>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

/**
 * Class action_plugin_bureaucracy
 */
class action_plugin_bureaucracy extends ActionPlugin
{
    /**
     * Registers a callback function for a given event
     */
    public function register(EventHandler $controller)
    {
        $controller->register_hook('AJAX_CALL_UNKNOWN', 'BEFORE', $this, 'ajax');
    }

    /**
     * @param Event $event
     * @param $param
     */
    public function ajax(Event $event, $param)
    {
        if ($event->data !== 'bureaucracy_user_field') {
            return;
        }
        $event->stopPropagation();
        $event->preventDefault();

        $search = $_REQUEST['search'];

        /** @var DokuWiki_Auth_Plugin $auth */
        global $auth;
        $users = [];
        foreach ($auth->retrieveUsers() as $username => $data) {
            if (
                $search === '' || // No search
                stripos($username, (string) $search) === 0 || // Username (prefix)
                stripos($data['name'], (string) $search) !== false
            ) { // Full name
                $users[$username] = $data['name'];
            }
            if (count($users) === 10) {
                break;
            }
        }

        if (count($users) === 1 && key($users) === $search) {
            $users = [];
        }

        echo json_encode($users);
    }
}
