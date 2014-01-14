<?php
/**
 * Class syntax_plugin_bureaucracy_field_hidden
 *
 * Creates an invisible field with static data
 */
class syntax_plugin_bureaucracy_field_hidden extends syntax_plugin_bureaucracy_field {

    /**
     * Arguments:
     *  - cmd
     *  - label
     *  - =default value
     */

    /**
     * Render the field as XHTML
     *
     * Outputs the represented field using the passed Doku_Form object.
     *
     * @params array     $params Additional HTML specific parameters
     * @params Doku_Form $form   The target Doku_Form object
     */
    function renderfield($params, $form) {
        $this->_handlePreload();
        $form->addHidden($params['name'], $this->getParam('value') . '');
    }

    /**
     * Get an arbitrary parameter
     *
     * @param string $name
     * @return mixed|null
     */
    function getParam($name) {
        if (!isset($this->opt[$name]) || in_array($name, array('pagename', 'value')) && $this->hidden) {
            return null;
        }
        if ($name === 'pagename') {
            // If $this->opt['pagename'] is set, return the value of the field,
            // UNESCAPED.
            $name = 'value';
        }
        return $this->opt[$name];
    }
}
