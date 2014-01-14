<?php
/**
 * Class syntax_plugin_bureaucracy_field_static
 *
 * Adds some static text to the form
 */
class syntax_plugin_bureaucracy_field_static extends syntax_plugin_bureaucracy_field {
    protected $tpl = '<p>@@DISPLAY@@</p>';

    /**
     * Arguments:
     *  - cmd
     *  - text
     *
     * @param array $args The tokenized definition, only split at spaces
     */
    public function __construct($args) {
        parent::__construct($args);
        // make always optional to prevent being marked as required
        $this->opt['optional'] = true;
    }

    /**
     * Handle a post to the field
     *
     * @param string $value The passed value
     * @return bool Whether the passed value is valid
     */
    public function handle_post(&$value) {
        return true;
    }

    /**
     * Get an arbitrary parameter
     *
     * @param string $name
     * @return mixed|null
     */
    public function getParam($name) {
        return ($name === 'value') ? null : parent::getParam($name);
    }

    /**
     * Render the field as XHTML
     *
     * @params array     $params Additional HTML specific parameters
     * @params Doku_Form $form   The target Doku_Form object
     */
    public function renderfield($params, Doku_Form $form) {
        if (!isset($this->opt['display'])) {
            $this->opt['display'] = $this->opt['label'];
        }
        parent::renderfield($params, $form);
    }

}
