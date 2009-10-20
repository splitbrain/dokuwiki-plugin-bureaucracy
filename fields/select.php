<?php
class syntax_plugin_bureaucracy_field_select extends syntax_plugin_bureaucracy_field {
    var $extraargs = 2;

    function render($params, $form) {
        $params = array_merge($this->opt, $params);
        $vals = explode('|',$params['args'][0]);
        $vals = array_map('trim',$vals);
        $vals = array_filter($vals);
        if (!isset($params['value']) && isset($params['optional'])) {
            array_unshift($vals,' ');
        }
        $form->addElement(form_makeListboxField($params['name'], $vals,
                                                isset($params['value']) ?
                                                $params['value'] : $vals[0],
                                                $params['label'], '',
                                                $params['class']));
    }
}
