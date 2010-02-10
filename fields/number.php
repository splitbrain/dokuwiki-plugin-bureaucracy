<?php
require_once DOKU_PLUGIN . 'bureaucracy/fields/textbox.php';
class syntax_plugin_bureaucracy_field_number extends syntax_plugin_bureaucracy_field_textbox {
    function setVal($value) {
        if (!parent::setVal($value)) {
            return false;
        }

        if($value !== '' && !is_numeric($value)){
            msg(sprintf($this->getLang('e_numeric'),hsc($this->getParam('label'))),-1);
            return false;
        }
        return true;
    }
}
