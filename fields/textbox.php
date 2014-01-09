<?php
/**
 * Class syntax_plugin_bureaucracy_field_textbox
 *
 * Creates a single line input field
 */
class syntax_plugin_bureaucracy_field_textbox extends syntax_plugin_bureaucracy_field {

    /**
     * Arguments:
     *  - cmd
     *  - label
     *  - =default (optional)
     *
     * @param array $args The tokenized definition, only split at spaces
     */
    function __construct($args) {
        parent::__construct($args);
        $this->tpl = form_makeTextField('@@NAME@@', '@@VALUE@@', '@@DISPLAY@@', '', '@@CLASS@@');
        if(isset($this->opt['class'])){
            $this->tpl['class'] .= ' '.$this->opt['class'];
        }
    }
}
