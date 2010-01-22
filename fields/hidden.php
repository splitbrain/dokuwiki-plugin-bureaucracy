<?php
class syntax_plugin_bureaucracy_field_hidden extends syntax_plugin_bureaucracy_field {
    var $extraargs = 2;

    function syntax_plugin_bureaucracy_field_hidden($syntax_plugin, $args) {
        parent::__construct($syntax_plugin, $args);
        $this->opt['value'] = $args[2];
    }

    function render($params, $form) {
        return;
    }

    function handle_post($value) {
        return true;
    }
}
