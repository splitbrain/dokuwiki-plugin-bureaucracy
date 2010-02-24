<?php
require_once DOKU_PLUGIN . 'bureaucracy/fields/textbox.php';
class syntax_plugin_bureaucracy_field_number extends syntax_plugin_bureaucracy_field_textbox {
    function _validate() {
        $value = $this->getParam('value');
        if (!is_null($value) && !is_numeric($value)){
            throw new Exception(sprintf($this->getLang('e_numeric'),hsc($this->getParam('label'))));
        }

        parent::_validate();
    }
}
