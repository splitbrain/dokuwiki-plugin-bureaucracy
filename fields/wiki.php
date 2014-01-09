<?php
/**
 * Class syntax_plugin_bureaucracy_field_wiki
 *
 * Adds some static text to the form, but parses the input as Wiki syntax (computationally expensive)
 */
class syntax_plugin_bureaucracy_field_wiki extends syntax_plugin_bureaucracy_field {

    protected $tpl = '<p>@@LABEL@@</p>';

    /**
     * Arguments:
     *  - cmd
     *  - wiki text
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
     * @param null $value empty
     * @return bool|array Whether the passed value is valid
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
     * Returns parsed wiki instructions
     *
     * @param string|array $tpl    The template as string
     * @param array        $params A hash mapping parameters to values
     *
     * @return string The parsed template
     */
    protected function _parse_tpl($tpl, $params) {
        $ins = array_slice(p_get_instructions($params['display']), 2, -2);
        $tpl = p_render('xhtml', $ins, $byref_ignore);
        return '<p>'.$tpl.'</p>';
    }
}
