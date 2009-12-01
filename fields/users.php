<?php
require_once DOKU_PLUGIN . 'bureaucracy/fields/textbox.php';
class syntax_plugin_bureaucracy_field_users extends syntax_plugin_bureaucracy_field_textbox {
    function __construct($syntax_plugin, $args) {
        parent::__construct($syntax_plugin, $args);
        $this->tpl['class'] .= ' userspicker';
    }

    function handle_post($value) {
        if (!parent::handle_post($value)) {
            return false;
        }

        global $auth;
        $users = preg_split('/\s*,\s*/', $value);
        foreach ($users as $user) {
            if ($auth->getUserData($user) === false) {
                msg(sprintf($this->getLang('e_user'),hsc($this->getParam('label'))),-1);
                return false;
            }
        }

        return true;
    }
}
