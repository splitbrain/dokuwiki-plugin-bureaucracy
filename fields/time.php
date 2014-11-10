<?php
require_once DOKU_PLUGIN . 'bureaucracy/fields/textbox.php';

/**
 * Class syntax_plugin_bureaucracy_field_time
 *
 * A time in the format (h)h:mm(:ss)
 */
class syntax_plugin_bureaucracy_field_time extends syntax_plugin_bureaucracy_field_textbox {
    /**
     * Arguments:
     *  - cmd
     *  - label
     *  - ^ (optional)
     *
     * @param array $args The tokenized definition, only split at spaces
     */
    public function __construct($args) {
        parent::__construct($args);
        $attr = array(
            'class' => 'timefield edit',
            'maxlength'=>'8'
        );
        if(!isset($this->opt['optional'])) {
            $attr['required'] = 'required';
        }
        $this->tpl = form_makeTextField('@@NAME@@', '@@VALUE@@', '@@DISPLAY@@', '@@ID@@', '@@CLASS@@', $attr);
    }

    /**
     * Validate field input
     *
     * @throws Exception when empty or wrong time format
     */
    protected function _validate() {
        parent::_validate();

        $value = $this->getParam('value');
        if (!is_null($value) && !preg_match('/^\d{1,2}:\d{2}(?::\d{2})?$/', $value)) {
            throw new Exception(sprintf($this->getLang('e_time'),hsc($this->getParam('display'))));
        }
    }
}
