<?php
class syntax_plugin_bureaucracy_field_static extends syntax_plugin_bureaucracy_field {
    var $extraargs = 1;
    var $tpl = '<p>@@LABEL@@</p>';

    function handle_post($param) {
        return true;
    }

    function getValue() {
        return null;
    }
}
