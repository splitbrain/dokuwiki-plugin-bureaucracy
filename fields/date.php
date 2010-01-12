<?php
require_once DOKU_PLUGIN . 'bureaucracy/fields/textbox.php';
class syntax_plugin_bureaucracy_field_date extends syntax_plugin_bureaucracy_field_textbox {
    function __construct($syntax_plugin, $args) {
        parent::__construct($syntax_plugin, $args);
        $this->tpl = form_makeTextField('@@NAME@@', '@@VALUE@@', '@@LABEL@@', '', '@@CLASS@@', array('class' => 'datepicker edit','maxlength'=>'10'));
    }

    function handle_post($value) {
        if (!parent::handle_post($value)) {
            return false;
        }

        if (!preg_match('/d{4}-\d{2}-\d{2}/', $value)) {
            msg(sprintf($this->getLang('e_date'),hsc($this->getParam('label'))),-1);
            return false;
        }

        return true;
    }
}
