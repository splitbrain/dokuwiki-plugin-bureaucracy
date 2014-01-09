<?php
require_once DOKU_PLUGIN . 'bureaucracy/fields/textbox.php';

/**
 * Class syntax_plugin_bureaucracy_field_date
 *
 * A date in the format YYYY-MM-DD, provides a date picker
 */
class syntax_plugin_bureaucracy_field_date extends syntax_plugin_bureaucracy_field_textbox {
    /**
     * Arguments:
     *  - cmd
     *  - label
     *
     * @param array $args The tokenized definition, only split at spaces
     */
    public function __construct($args) {
        parent::__construct($args);
        $this->tpl = form_makeTextField('@@NAME@@', '@@VALUE@@', '@@DISPLAY@@', '', '@@CLASS@@', array('class' => 'datepicker edit','maxlength'=>'10'));
    }

    /**
     * Validate field input
     *
     * @throws Exception when empty or wrong date format
     */
    protected function _validate() {
        parent::_validate();

        $value = $this->getParam('value');
        if (!is_null($value) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            throw new Exception(sprintf($this->getLang('e_date'),hsc($this->getParam('display'))));
        }
    }
}
