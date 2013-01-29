<?php
class syntax_plugin_bureaucracy_field_textbox extends syntax_plugin_bureaucracy_field {
    function syntax_plugin_bureaucracy_field_textbox($args) {
        parent::__construct($args);
        $this->tpl = form_makeTextField('@@NAME@@', '@@VALUE@@', '@@DISPLAY@@', '', '@@CLASS@@');
        if(isset($this->opt['class'])){
            $this->tpl['class'] .= ' '.$this->opt['class'];
        }
    }
}
