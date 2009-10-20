<?php
class syntax_plugin_bureaucracy_field_fieldset extends syntax_plugin_bureaucracy_field {
    var $extraargs = 0;

    function syntax_plugin_bureaucracy_field_fieldset($syntax_plugin, $args) {
        $this->syntax_plugin = $syntax_plugin;
        // get standard arguments
        $this->cmd = array_shift($args);

        if (count($args) > 0) {
            $this->opt = array('label' => array_shift($args));
        }

        if (count($args) > 0) {
            $this->depends_on = $args;
        }
    }

    function render($params, $form) {
        $form->startFieldset($this->getParam('label'));
        if (isset($this->depends_on)) {
            if (count($this->depends_on) > 1) {
                $msg = 'Only edit this fieldset if ' .
                       '“<span class="bureaucracy_depends_fname">%s</span>” '.
                       'is set to “<span class="bureaucracy_depends_fvalue">%s</span>”.';
            } else {
                $msg = 'Only edit this fieldset if ' .
                       '“<span class="bureaucracy_depends_fname">%s</span>” is set.';
            }
           $form->addElement('<p class="bureaucracy_depends">' . vsprintf($msg, $this->depends_on) . '</p>');
        }
    }

    function handle_post($param) {
        return isset($this->depends_on) ? $this->depends_on : true;
    }

    function getParam($name) {
        return ($name === 'value') ? null : parent::getParam($name);
    }
}
