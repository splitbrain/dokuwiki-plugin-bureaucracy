<?php
class syntax_plugin_bureaucracy_field_wiki extends syntax_plugin_bureaucracy_field {
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

    function _parse_tpl($tpl, $params) {
        $ins = array_slice(p_get_instructions($params['display']), 2, -2);
        $tpl = p_render('xhtml', $ins, $byref_ignore);
        return '<p>'.$tpl.'</p>';
    }
}
