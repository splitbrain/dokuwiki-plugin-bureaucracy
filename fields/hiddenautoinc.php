<?php
class syntax_plugin_bureaucracy_field_hiddenautoinc extends syntax_plugin_bureaucracy_field_number {

    function renderfield($params, $form) {
        $this->_handlePreload();
        $form->addHidden($params['name'], $this->getParam('value') . '');
    }

    function __construct($args) {
        $args[] = '++';
        parent::__construct($args);
    }
}
