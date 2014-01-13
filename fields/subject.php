<?php
/**
 * Class syntax_plugin_bureaucracy_field_subject
 *
 * Defines own subject for mail action
 */
class syntax_plugin_bureaucracy_field_subject extends syntax_plugin_bureaucracy_field {
    /**
     * Arguments:
     *  - cmd
     *  - subjecttext
     */

    /**
     * Render the field as XHTML
     *
     * @params array     $params Additional HTML specific parameters
     * @params Doku_Form $form   The target Doku_Form object
     */
    public function renderfield($params, Doku_Form $form) {
        $this->_handlePreload();
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
}