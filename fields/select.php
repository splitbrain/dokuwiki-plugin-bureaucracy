<?php
class syntax_plugin_bureaucracy_field_select extends syntax_plugin_bureaucracy_field {
    var $mandatory_args = 3;

    function __construct($args) {
        $this->init($args);
        $this->opt['args'] = array_filter(array_map('trim', explode('|',array_shift($args))));
        $this->standardArgs($args);
        if (!isset($this->opt['value']) && isset($this->opt['optional'])) {
            array_unshift($this->opt['args'],' ');
        }
    }

    function render($params, $form) {
        $this->_handlePreload();
        if(!$form->_infieldset){
            $form->startFieldset('');
        }
        if ($this->error) {
            $params['class'] = 'bureaucracy_error';
        }
        $params = array_merge($this->opt, $params);
        $form->addElement(call_user_func_array('form_makeListboxField',
                                               $this->_parse_tpl(array('@@NAME@@',
                                                $params['args'], '@@VALUE|' . $params['args'][0] . '@@',
                                                '@@LABEL@@', '', '@@CLASS@@'),
                                                $params)));
    }
}
