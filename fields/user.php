<?php
require_once DOKU_PLUGIN . 'bureaucracy/fields/textbox.php';
class syntax_plugin_bureaucracy_field_user extends syntax_plugin_bureaucracy_field_textbox {
    function __construct($syntax_plugin, $args) {
        parent::__construct($syntax_plugin, $args);
        $this->tpl['class'] .= ' userpicker';
    }

    function setVal($value) {
        if (!parent::setVal($value)) {
            return false;
        }

        global $auth;

        if ($value !== '' && $auth->getUserData($value) === false) {
            msg(sprintf($this->getLang('e_user'),hsc($this->getParam('label'))),-1);
            return false;
        }

        return true;
    }
}
