<?php
/**
 * Class helper_plugin_bureaucracy_fieldmultiselect
 *
 * Creates a multiselect box
 */
class helper_plugin_bureaucracy_fieldmultiselect extends helper_plugin_bureaucracy_fieldselect {

    /**
     * Arguments:
     *  - cmd
     *  - label
     *  - option1|option2|etc
     *  - ^ (optional)
     *
     * @param array $args The tokenized definition, only split at spaces
     */
    public function initialize($args) {
        $this->init($args);
        $this->opt['args'] = array_map('trim', explode('|',array_shift($args)));
        $this->standardArgs($args);
        if (isset($this->opt['value'])) {
            $this->opt['value'] = array_map('trim', explode('|', $this->opt['value']));
        } else {
            $this->opt['value'] = array();
        }
    }

    /**
     * Get the replacement pattern used by action
     *
     * @return string
     */
    public function getReplacementPattern() {
        $label = $this->opt['label'];
        $value = $this->opt['value'];

        return '/(@@|##)' . preg_quote($label, '/') .
            '(?:\((?P<delimiter>.*?)\))?' .//delimiter
            '(?:\|(?P<default>.*?))' . (count($value) == 0 ? '' : '?') .
            '\1/si';
    }

    /**
     * Used as an callback for preg_replace_callback
     *
     * @param $matches
     * @return string
     */
    public function replacementValueCallback($matches) {
        $value = $this->opt['value'];

        //default value
        if (is_null($value) || $value === false) {
            if (isset($matches['default']) && $matches['default'] != '') {
                return $matches['default'];
            }
            return $matches[0];
        }

        //check if matched string containts a pair of brackets
        $delimiter = preg_match('/\(.*\)/s', $matches[0]) ? $matches['delimiter'] : ', ';

        return implode($delimiter, $value);
    }

    /**
     * Return the callback for user replacement
     *
     * @return array
     */
    public function getReplacementValue() {
        return array($this, 'replacementValueCallback');
    }

    /**
     * Render the field as XHTML
     *
     * Outputs the represented field using the passed Doku_Form object.
     * Additional parameters (CSS class & HTML name) are passed in $params.
     *
     * @params array     $params Additional HTML specific parameters
     * @params Doku_Form $form   The target Doku_Form object
     * @params int       $formid unique identifier of the form which contains this field
     */
    public function renderfield($params, Doku_Form $form, $formid) {
        $this->_handlePreload();
        if(!$form->_infieldset){
            $form->startFieldset('');
        }
        if ($this->error) {
            $params['class'] = 'bureaucracy_error';
        }
        $params = array_merge($this->opt, $params);
        $form->addElement(call_user_func_array('form_makeListboxField',
                                               $this->_parse_tpl(
                                                   array(
                                                       '@@NAME@@[]',
                                                       $params['args'],
                                                       $this->opt['value'],
                                                       '@@DISPLAY@@',
                                                       '@@ID@@',
                                                       '@@CLASS@@',
                                                       array('multiple' => 'multiple')
                                                   ),
                                                   $params
                                               )));
    }
}