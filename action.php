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

/**
 * Class action_plugin_bureaucracy
 */
class action_plugin_bureaucracy extends DokuWiki_Action_Plugin {

    /**
     * Registers a callback function for a given event
     */
    public function register(Doku_Event_Handler $controller) {
        $controller->register_hook('AJAX_CALL_UNKNOWN', 'BEFORE', $this, 'ajax');
    }

    /**
     * @param Doku_Event$event
     * @param $param
     */
    public function ajax(Doku_Event $event, $param) {
        if ($event->data !== 'bureaucracy_user_field') {
            return;
        }
        $event->stopPropagation();
        $event->preventDefault();

        $search = $_REQUEST['search'];

        /** @var DokuWiki_Auth_Plugin $auth */
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

/**
 * Load recognized classes
 *
 * @param string $name
 * @return bool
 */
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
