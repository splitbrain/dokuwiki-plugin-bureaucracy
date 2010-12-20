<?php
class syntax_plugin_bureaucracy_field_textbox extends syntax_plugin_bureaucracy_field {
    function syntax_plugin_bureaucracy_field_textbox($args) {
        parent::__construct($args);
        $this->tpl = form_makeTextField('@@NAME@@', '@@VALUE@@', '@@LABEL@@', '', '@@CLASS@@');
    }
}
