<?php
require_once DOKU_PLUGIN . 'bureaucracy/fields/textbox.php';
class syntax_plugin_bureaucracy_field_users extends syntax_plugin_bureaucracy_field_textbox {
    function __construct($args) {
        parent::__construct($args);
        $this->tpl['class'] .= ' userspicker';
    }

    function _validate() {
        parent::_validate();

        global $auth;
        $users = array_filter(preg_split('/\s*,\s*/', $this->getParam('value')));
        foreach ($users as $user) {
            if ($auth->getUserData($user) === false) {
                throw new Exception(sprintf($this->getLang('e_users'), hsc($this->getParam('label'))));
            }
        }
    }
}
