<?php
class syntax_plugin_bureaucracy_field_static extends syntax_plugin_bureaucracy_field {
    var $tpl = '<p>@@LABEL@@</p>';

    function __construct($args) {
        parent::__construct($args);
        // make always optional to prevent being marked as required
        $this->opt['optional'] = true;
    }

    function handle_post($param) {
        return true;
    }

    function getParam($name) {
        return ($name === 'value') ? null : parent::getParam($name);
    }
}
