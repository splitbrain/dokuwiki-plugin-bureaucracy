<?php
class syntax_plugin_bureaucracy_field_password extends syntax_plugin_bureaucracy_field {
    function syntax_plugin_bureaucracy_field_password($args) {
        parent::__construct($args);
        $this->tpl = form_makePasswordField('@@NAME@@', '@@LABEL@@', '', '@@CLASS@@');
    }
}
