<?php
require_once DOKU_PLUGIN . 'bureaucracy/fields/textbox.php';
/**
 * Class syntax_plugin_bureaucracy_field_email
 *
 * Creates a single line input field where the input is validated to be a valid email address
 */
class syntax_plugin_bureaucracy_field_email extends syntax_plugin_bureaucracy_field_textbox {

    /**
     * Validate field value
     *
     * @throws Exception when empty or not valid email address
     */
    function _validate() {
        parent::_validate();

        $value = $this->getParam('value');
        if(!is_null($value) && $value !== '@MAIL@' && !mail_isvalid($value)){
            throw new Exception(sprintf($this->getLang('e_email'),hsc($this->getParam('display'))));
        }
    }
}
