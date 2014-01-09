<?php
/**
 * Class syntax_plugin_bureaucracy_field_addpage
 *
 * Adds another page page_tgt based on a template page page_tpl only for use with the template action
 */
class syntax_plugin_bureaucracy_field_addpage extends syntax_plugin_bureaucracy_field {

    /**
     * Arguments:
     *  - cmd
     *  - page_tpl
     *  - page_tgt
     *
     * @param array $args The tokenized definition, only split at spaces
     */
    function __construct($args) {
        if(count($args) < 3){
            msg(sprintf($this->getLang('e_missingargs'), hsc($args[0]),
                        hsc($args[1])), -1);
            return;
        }

        // get standard arguments
        $this->opt = array_combine(array('cmd', 'page_tpl', 'page_tgt'), $args);
    }

    /**
     * Nothing displayed
     *
     * @params array     $params Additional HTML specific parameters
     * @params Doku_Form $form   The target Doku_Form object
     */
    function renderfield($params, Doku_Form $form) {
    }

    /**
     * Handle a post to the field
     *
     * @param string $value null
     * @return bool Whether the passed value is valid
     */
    function handle_post(&$value) {
        return true;
    }

    /**
     * Get an arbitrary parameter
     *
     * @param string $name
     * @return mixed|null
     */
    function getParam($name) {
        return ($name === 'value' ||
                (in_array($name, array('page_tpl', 'page_tgt')) && $this->hidden)) ?
               null :
               parent::getParam($name);
    }
}
