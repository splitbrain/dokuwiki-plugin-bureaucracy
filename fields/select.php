<?php
class syntax_plugin_bureaucracy_field_select extends syntax_plugin_bureaucracy_field {
    var $extraargs = 2;

    function render($params, $form) {
        $this->_handlePreload();
        if(!$form->_infieldset){
            $form->startFieldset('');
        }
        if ($this->error) {
            $params['class'] = 'bureaucracy_error';
        }

        $params = array_merge($this->opt, $params);
        $vals = explode('|',$params['args'][0]);
        $vals = array_map('trim',$vals);
        $vals = array_filter($vals);
        if (!isset($params['value']) && isset($params['optional'])) {
            array_unshift($vals,' ');
        }
        $form->addElement(call_user_func_array('form_makeListboxField',
                                               $this->_parse_tpl(array('@@NAME@@',
                                                $vals, '@@VALUE|' . $vals[0] . '@@',
                                                '@@LABEL@@', '', '@@CLASS@@'),
                                                $params)));
    }
}
