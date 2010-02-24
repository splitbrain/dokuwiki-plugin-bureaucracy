<?php
class syntax_plugin_bureaucracy_field_hidden extends syntax_plugin_bureaucracy_field {
    var $extraargs = 2;

    function render($params, $form) {
        return;
    }

    function handle_post($value) {
        return true;
    }
}
