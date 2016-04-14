<?php
/**
 * Class helper_plugin_bureaucracy_fieldhidden
 *
 * Creates an invisible field with static data
 */
class helper_plugin_bureaucracy_fieldhidden extends helper_plugin_bureaucracy_field {

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
     * @param array     $params Additional HTML specific parameters
     * @param Doku_Form $form   The target Doku_Form object
     * @param int       $formid unique identifier of the form which contains this field
     */
    function renderfield($params, Doku_Form $form, $formid) {
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
