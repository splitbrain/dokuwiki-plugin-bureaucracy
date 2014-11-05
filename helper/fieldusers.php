<?php
/**
 * Class helper_plugin_bureaucracy_fieldusers
 *
 * Create multi-user input, with autocompletion
 */
class helper_plugin_bureaucracy_fieldusers extends helper_plugin_bureaucracy_fieldtextbox {

    /**
     * Arguments:
     *  - cmd
     *  - label
     *  - ^ (optional)
     *
     * @param array $args The tokenized definition, only split at spaces
     */
    public function initialize($args) {
        parent::initialize($args);
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
