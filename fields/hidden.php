<?php
class syntax_plugin_bureaucracy_field_hidden extends syntax_plugin_bureaucracy_field {
    function render($params, $form) {
        $this->_handlePreload();
        $form->addHidden($params['name'], $this->getParam('value') . '');
        return;
    }

    function getParam($name) {
        if (!isset($this->opt[$name]) ||
            in_array($name, array('pagename', 'value')) && $this->hidden) {
            return null;
        }
        if ($name === 'pagename') {
            // If $this->opt['pagename'] is set, return the value of the field,
            // UNESCAPED.
            $name = 'value';
        }
        return $this->opt[$name];
    }
}
