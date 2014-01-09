<?php
/**
 * Class syntax_plugin_bureaucracy_field_select
 *
 * Creates a dropdown list
 */
class syntax_plugin_bureaucracy_field_select extends syntax_plugin_bureaucracy_field {

    protected $mandatory_args = 3;

    /**
     * Arguments:
     *  - cmd
     *  - label
     *  - option1|option2|etc
     *
     * @param array $args The tokenized definition, only split at spaces
     */
    public function __construct($args) {
        $this->init($args);
        $this->opt['args'] = array_map('trim', explode('|',array_shift($args)));
        $this->standardArgs($args);
        if (!isset($this->opt['value']) && isset($this->opt['optional'])) {
            array_unshift($this->opt['args'],' ');
        }
    }

    /**
     * Render the field as XHTML
     *
     * Outputs the represented field using the passed Doku_Form object.
     * Additional parameters (CSS class & HTML name) are passed in $params.
     *
     * @params array     $params Additional HTML specific parameters
     * @params Doku_Form $form   The target Doku_Form object
     */
    public function renderfield($params, Doku_Form $form) {
        $this->_handlePreload();
        if(!$form->_infieldset){
            $form->startFieldset('');
        }
        if ($this->error) {
            $params['class'] = 'bureaucracy_error';
        }
        $params = array_merge($this->opt, $params);
        $form->addElement(call_user_func_array('form_makeListboxField',
                                               $this->_parse_tpl(array('@@NAME@@',
                                                $params['args'], '@@VALUE|' . $params['args'][0] . '@@',
                                                '@@DISPLAY@@', '', '@@CLASS@@'),
                                                $params)));
    }
}
