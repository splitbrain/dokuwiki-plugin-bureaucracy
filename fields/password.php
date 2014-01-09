<?php
/**
 * Class syntax_plugin_bureaucracy_field_password
 *
 * Creates a single line password input field
 */
class syntax_plugin_bureaucracy_field_password extends syntax_plugin_bureaucracy_field {
    /**
     * Arguments:
     *  - cmd
     *  - label
     *
     * @param array $args The tokenized definition, only split at spaces
     */
    function __construct($args) {
        parent::__construct($args);
        $this->tpl = form_makePasswordField('@@NAME@@', '@@DISPLAY@@', '', '@@CLASS@@');
    }
}
