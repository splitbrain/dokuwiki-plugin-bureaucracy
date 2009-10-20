<?php
class syntax_plugin_bureaucracy_field_fieldset extends syntax_plugin_bureaucracy_field {
    var $extraargs = 1;

    function render($params, $form) {
        $form->startFieldset($this->opt['label']);
    }

    function handle_post($param) {
        return true;
    }

    function getValue() {
        return null;
    }
}
