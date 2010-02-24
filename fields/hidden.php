<?php
class syntax_plugin_bureaucracy_field_hidden extends syntax_plugin_bureaucracy_field {
    function render($params, $form) {
        $this->_handlePreload();
        $form->addHidden($params['name'], $this->getParam('value') . '');
        return;
    }
}
