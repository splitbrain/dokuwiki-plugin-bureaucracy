<?php
require_once DOKU_PLUGIN . 'bureaucracy/fields/textbox.php';
class syntax_plugin_bureaucracy_field_user extends syntax_plugin_bureaucracy_field_textbox {
    function __construct($args) {
        parent::__construct($args);
        $this->tpl['class'] .= ' userpicker';
    }

    function _validate() {
        parent::_validate();

        global $auth;
        $value = $this->getParam('value');
        if (!is_null($value) && $auth->getUserData($value) === false) {
            throw new Exception(sprintf($this->getLang('e_user'),hsc($this->getParam('label'))));
        }
    }
}
