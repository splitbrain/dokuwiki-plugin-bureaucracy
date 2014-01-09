<?php
require_once DOKU_PLUGIN . 'bureaucracy/fields/textbox.php';
/**
 * Class syntax_plugin_bureaucracy_field_users
 *
 * Create multi-user input, with autocompletion
 */
class syntax_plugin_bureaucracy_field_users extends syntax_plugin_bureaucracy_field_textbox {

    /**
     * Arguments:
     *  - cmd
     *  - label
     *
     * @param array $args The tokenized definition, only split at spaces
     */
    public function __construct($args) {
        parent::__construct($args);
        $this->tpl['class'] .= ' userspicker';
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
        $users = array_filter(preg_split('/\s*,\s*/', $this->getParam('value')));
        foreach ($users as $user) {
            if ($auth->getUserData($user) === false) {
                throw new Exception(sprintf($this->getLang('e_users'), hsc($this->getParam('display'))));
            }
        }
    }
}
