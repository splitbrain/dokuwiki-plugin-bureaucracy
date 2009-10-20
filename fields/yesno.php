<?php
class syntax_plugin_bureaucracy_field_yesno extends syntax_plugin_bureaucracy_field {
    var $extraargs = 1;

    function render($params, $form) {
        $params = array_merge(array('value' => false), $this->opt, $params);
        $params['check'] = $params['value'] ? 'checked="checked"' : '';
        $this->tpl = '<label class="@@CLASS@@"><span>@@LABEL@@</span>'.
                     '<input type="hidden" name="@@NAME@@" value="0" />' .
                     '<input type="checkbox" name="@@NAME@@" value="Yes" @@CHECK@@ /></label>';
        parent::render($params, $form);
    }
}
