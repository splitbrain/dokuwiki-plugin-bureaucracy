<?php
/**
 * Class helper_plugin_bureaucracy_fielduser
 *
 * Create single user input, with autocompletion
 */
class helper_plugin_bureaucracy_fielduser extends helper_plugin_bureaucracy_fieldtextbox {

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
