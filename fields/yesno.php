<?php
class syntax_plugin_bureaucracy_field_yesno extends syntax_plugin_bureaucracy_field {
    public function __construct($args) {
        $this->init($args);
        $newargs = array();
        foreach ($args as $arg) {
            switch ($arg[0]) {
            case '=':
                $this->opt['true_value'] = substr($arg, 1);
                break;
            case '!':
                $this->opt['false_value'] = substr($arg, 1);
                break;
            default:
                $newargs[] = $arg;
            }
        }
        $this->standardArgs($newargs);
        $this->opt['optional'] = true;
    }

    public function getParam($key) {
        if ($key === 'value') {
            if ($this->opt['value'] === '1') {
                return isset($this->opt['true_value']) ?
                       $this->opt['true_value'] :
                       null;
            } elseif ($this->opt['value'] === '0') {
                return isset($this->opt['false_value']) ?
                       $this->opt['false_value'] :
                       null;
            }
        }
        return parent::getParam($key);
    }

    public function isSet_() {
        return $this->opt['value'] === '1';
    }

    function render($params, $form) {
        $id = 'bureaucracy__'.md5(rand());
        $params = array_merge(array('value' => false), $this->opt, $params);
        $check = $params['value'] ? 'checked="checked"' : '';
        $this->tpl = '<label class="@@CLASS@@" for="'.$id.'"><span>@@LABEL@@</span>'.
                     '<input type="hidden" name="@@NAME@@" value="0" />' .
                     '<input type="checkbox" name="@@NAME@@" value="1" id="'.$id.'" ' .
                     $check . ' /></label>';
        parent::render($params, $form);
    }
}
