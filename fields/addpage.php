<?php
class syntax_plugin_bureaucracy_field_addpage extends syntax_plugin_bureaucracy_field {
    function __construct($args) {
        if(count($args) < 3){
            msg(sprintf($this->getLang('e_missingargs'), hsc($args[0]),
                        hsc($args[1])), -1);
            return;
        }

        // get standard arguments
        $this->opt = array_combine(array('cmd', 'page_tpl', 'page_tgt'), $args);
    }

    function render($params, $form) {
    }

    function handle_post($param) {
        return true;
    }

    function getParam($name) {
        return ($name === 'value' ||
                (in_array($name, array('page_tpl', 'page_tgt')) && $this->hidden)) ?
               null :
               parent::getParam($name);
    }
}
