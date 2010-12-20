<?php
require_once DOKU_PLUGIN . 'bureaucracy/fields/textbox.php';
class syntax_plugin_bureaucracy_field_date extends syntax_plugin_bureaucracy_field_textbox {
    function __construct($args) {
        parent::__construct($args);
        $this->tpl = form_makeTextField('@@NAME@@', '@@VALUE@@', '@@LABEL@@', '', '@@CLASS@@', array('class' => 'datepicker edit','maxlength'=>'10'));
    }

    function _validate() {
        parent::_validate();

        $value = $this->getParam('value');
        if (!is_null($value) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            throw new Exception(sprintf($this->getLang('e_date'),hsc($this->getParam('label'))));
        }
    }
}
