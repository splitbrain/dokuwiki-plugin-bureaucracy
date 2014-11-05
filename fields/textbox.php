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
     *  - ^ (optional)
     *
     * @param array $args The tokenized definition, only split at spaces
     */
    function __construct($args) {
        parent::__construct($args);

        $attr = array();
        if(!isset($this->opt['optional'])) {
            $attr['required'] = 'required';
        }

        $this->tpl = form_makeTextField('@@NAME@@', '@@VALUE@@', '@@DISPLAY@@', '@@ID@@', '@@CLASS@@', $attr);
        if(isset($this->opt['class'])){
            $this->tpl['class'] .= ' '.$this->opt['class'];
        }
    }
}
