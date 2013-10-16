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


    function register(Doku_Event_Handler $controller) {
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
        echo $json->encode($users);
    }
}

function syntax_plugin_bureaucracy_autoload($name) {
    if (!preg_match('/^syntax_plugin_bureaucracy_(field|action)(?:_(.*))?$/', $name, $matches)) {
        return false;
    }
    require_once DOKU_PLUGIN . "bureaucracy/syntax.php";

    if (!isset($matches[2])) {
        // Autoloading the field / action base class
        $matches[2] = $matches[1];
    }

    $filename = DOKU_PLUGIN . "bureaucracy/{$matches[1]}s/{$matches[2]}.php";
    if (!@file_exists($filename)) {
        $plg = new syntax_plugin_bureaucracy;
        msg(sprintf($plg->getLang($matches[1] === 'field' ? 'e_unknowntype' : 'e_noaction'),
                    hsc($matches[2])), -1);
        eval("class $name extends syntax_plugin_bureaucracy_{$matches[1]} { };");
        return true;
    }
    require_once $filename;
    return true;
}

spl_autoload_register('syntax_plugin_bureaucracy_autoload');
