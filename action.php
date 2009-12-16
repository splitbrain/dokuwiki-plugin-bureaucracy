<?php
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

if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'action.php');

class action_plugin_bureaucracy extends DokuWiki_Action_Plugin {
    /**
     * return some info
     */
    function getInfo(){
        return array(
            'author' => 'Andreas Gohr',
            'email'  => 'andi@splitbrain.org',
            'date'   => '2009-08-16',
            'name'   => 'Bureaucracy Plugin',
            'desc'   => 'A simple form generator/emailer',
            'url'    => 'http://dokuwiki.org/plugin:bureaucracy',
        );
    }

    function register(&$controller) {
        $controller->register_hook('AJAX_CALL_UNKNOWN', 'BEFORE', $this,
                                   'ajax');
    }

    function ajax(&$event, $param) {
        if ($event->data !== 'bureaucracy_user_field') {
            return;
        }
        $event->stopPropagation();
        $event->preventDefault();

        $search = $_REQUEST['search'];

        global $auth;
        $users = array();
        foreach($auth->retrieveUsers() as $username => $data) {
            if ($search === '' || // No search
                stripos($username, $search) === 0 || // Username (prefix)
                stripos($data['name'], $search) !== false) { // Full name
                $users[$username] = $data['name'];
            }
            if (count($users) === 10) {
                break;
            }
        }

        if (count($users) === 1 && key($users) === $search) {
            $users = array();
        }

        require_once DOKU_INC . 'inc/JSON.php';
        $json = new JSON();
        echo '(' . $json->encode($users) . ')';
    }
}
