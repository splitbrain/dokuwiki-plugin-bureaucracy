<?php
require_once DOKU_PLUGIN . 'bureaucracy/fields/textbox.php';
class syntax_plugin_bureaucracy_field_email extends syntax_plugin_bureaucracy_field_textbox {

    function _validate() {
        parent::_validate();

        $value = $this->getParam('value');
        if(!is_null($value) && !mail_isvalid($value)){
            throw new Exception(sprintf($this->getLang('e_email'),hsc($this->getParam('label'))));
        }
    }
}
