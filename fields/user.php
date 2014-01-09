<?php
require_once DOKU_PLUGIN . 'bureaucracy/fields/textbox.php';
/**
 * Class syntax_plugin_bureaucracy_field_user
 *
 * Create single user input, with autocompletion
 */
class syntax_plugin_bureaucracy_field_user extends syntax_plugin_bureaucracy_field_textbox {

    /**
     * Arguments:
     *  - cmd
     *  - label
     *
     * @param array $args The tokenized definition, only split at spaces
     */
    public function __construct($args) {
        parent::__construct($args);
        $this->tpl['class'] .= ' userpicker';
    }

    /**
     * Validate value of field
     *
     * @throws Exception when user not exists
     */
    protected function _validate() {
        parent::_validate();

        /** @var DokuWiki_Auth_Plugin $auth */
        global $auth;
        $value = $this->getParam('value');
        if (!is_null($value) && $auth->getUserData($value) === false) {
            throw new Exception(sprintf($this->getLang('e_user'),hsc($this->getParam('display'))));
        }
    }
}
