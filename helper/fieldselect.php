<?php
/**
 * Class helper_plugin_bureaucracy_fieldselect
 *
 * Creates a dropdown list
 */
class helper_plugin_bureaucracy_fieldselect extends helper_plugin_bureaucracy_field {

    protected $mandatory_args = 3;

    /**
     * Arguments:
     *  - cmd
     *  - label
     *  - option1|option2|etc
     *  - ^ (optional)
     *  - selection1|selection2|etc#option1|option2|etc  
     * 
     * @param array $args The tokenized definition, only split at spaces
     */
    public function initialize($args) {
        $this->init($args);
        $total_params=array_map('trim', explode('#',array_shift($args)));
		if (!isset($total_params[1])) 
		    {$this->opt['args'] = array_map('trim', explode('|',$total_params[0]));
			$this->opt['show_params'] = $this->opt['args'];
			}
     		else 
			{$this->opt['args'] = array_map('trim', explode('|',$total_params[1]));
			 $this->opt['show_params'] = array_map('trim', explode('|',$total_params[0]));
			}
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
        $this->opt['show_params'] = array_combine($params['args'],$this->opt['show_params']);
        $form->addElement(call_user_func_array('form_makeListboxField',
                                                $this->_parse_tpl(
                                                    array(
                                                        '@@NAME@@',
                                                        $this->opt['show_params'],
                                                        '@@VALUE@@|' . $this->opt['show_params'][0],
                                                        '@@DISPLAY@@',
                                                        '@@ID@@',
                                                        '@@CLASS@@'
                                                    ),
                                                    $params
                                                )));
    }
}
