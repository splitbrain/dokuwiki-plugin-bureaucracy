<?php
class syntax_plugin_bureaucracy_field_fieldset extends syntax_plugin_bureaucracy_field {
    var $extraargs = 1;

    function render($params, $form) {
        $form->startFieldset($this->getParam('label'));
    }

    function handle_post($param) {
        return true;
    }

    function getParam($name) {
        return ($name === 'value') ? null : parent::getParam($name);
    }
}
