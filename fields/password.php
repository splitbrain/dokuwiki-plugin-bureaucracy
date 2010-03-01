<?php
class syntax_plugin_bureaucracy_field_password extends syntax_plugin_bureaucracy_field {
    function syntax_plugin_bureaucracy_field_password($syntax_plugin, $args) {
        parent::__construct($syntax_plugin, $args);
        $this->tpl = form_makePasswordField('@@NAME@@', '@@LABEL@@', '', '@@CLASS@@');
    }
}
