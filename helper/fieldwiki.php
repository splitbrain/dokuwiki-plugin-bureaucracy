<?php
/**
 * Class helper_plugin_bureaucracy_fieldwiki
 *
 * Adds some static text to the form, but parses the input as Wiki syntax (computationally expensive)
 */
class helper_plugin_bureaucracy_fieldwiki extends helper_plugin_bureaucracy_field {

    protected $tpl = '<p>@@LABEL@@</p>';

    /**
     * Arguments:
     *  - cmd
     *  - wiki text
     *
     * @param array $args The tokenized definition, only split at spaces
     */
    public function initialize($args) {
        parent::initialize($args);
        // make always optional to prevent being marked as required
        $this->opt['optional'] = true;
    }

    /**
     * Handle a post to the field
     *
     * @param null $value empty
     * @param helper_plugin_bureaucracy_field[] $fields (reference) form fields (POST handled upto $this field)
     * @param int    $index  index number of field in form
     * @param int    $formid unique identifier of the form which contains this field
     * @return bool Whether the passed value is valid
     */
    public function handle_post($value, &$fields, $index, $formid) {
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
        $ins = p_get_instructions($params['display']);
        // remove document and p instructions (opening and closing)
        $start = 2;
        $end = 2;
        // check if struct instructions have to be removed as well from the beginning or end
        if (isset($ins[1][1][0]) && $ins[1][1][0] === 'struct_output') {
            $start = 3;
        } elseif (isset($ins[count($ins) - 2][1][0]) && $ins[count($ins) - 2][1][0] === 'struct_output') {
            $end = 3;
        }
        $ins = array_slice($ins, $start, -$end);
        $tpl = p_render('xhtml', $ins, $byref_ignore);
        return '<p>'.$tpl.'</p>';
    }
}
