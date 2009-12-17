<?php
class syntax_plugin_bureaucracy_field_addpage extends syntax_plugin_bureaucracy_field {
    function __construct($syntax_plugin, $args) {
        $this->use = false;
        $this->syntax_plugin = $syntax_plugin;
        if(count($args) < $this->extraargs + 1){
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
        $this->use = true;
        return true;
    }

    function getParam($name) {
        return ($name === 'value' || !$this->use) ? null : parent::getParam($name);
    }

}
