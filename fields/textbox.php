<?php
class syntax_plugin_bureaucracy_field_textbox extends syntax_plugin_bureaucracy_field {
    var $extraargs = 1;

    function syntax_plugin_bureaucracy_field_textbox($syntax_plugin, $args) {
        parent::__construct($syntax_plugin, $args);
        $this->tpl = form_makeTextField('@@NAME@@', '@@VALUE@@', '@@LABEL@@', '', '@@CLASS@@');
    }
}
