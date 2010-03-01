<?php
class syntax_plugin_bureaucracy_field_yesno extends syntax_plugin_bureaucracy_field {
    function render($params, $form) {
        $params = array_merge(array('value' => false), $this->opt, $params);
        $check = $params['value'] ? 'checked="checked"' : '';
        $this->tpl = '<label class="@@CLASS@@"><span>@@LABEL@@</span>'.
                     '<input type="hidden" name="@@NAME@@" value="0" />' .
                     '<input type="checkbox" name="@@NAME@@" value="Yes" ' .
                     $check . ' /></label>';
        parent::render($params, $form);
    }
}
