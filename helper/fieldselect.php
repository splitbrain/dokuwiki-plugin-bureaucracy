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
     * show1|show2|show3#real1|real2|real3
     * @param array $args The tokenized definition, only split at spaces
     */
    public function initialize($args) {
        $this->init($args);
        $total_params=array_map('trim', explode('#',array_shift($args)));//edit by Hanjiazhuang. 2015.1.15
		if (!isset($total_params[1])) 
		    {$this->opt['args'] = array_map('trim', explode('|',$total_params[0]));
			$this->opt['show_params'] = $this->opt['args'];
			}
     		else 
			{$this->opt['args'] = array_map('trim', explode('|',$total_params[1]));
			 $this->opt['show_params'] = array_map('trim', explode('|',$total_params[0]));
			}
			
		//$this->opt['args'] = array_map('trim', explode('|',array_shift($args)));edit by Hanjiazhuang. 2015.1.15
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
		$this->opt['show_params'] = array_combine($params['args'],$this->opt['show_params']);//edit by Hanjiazhuang. 2015.1.15
        $form->addElement(call_user_func_array('form_makeListboxField',
                                                $this->_parse_tpl(
                                                    array(
                                                        '@@NAME@@',
                                                        //$params['args'],
														$this->opt['show_params'],//edit by Hanjiazhuang. 2015.1.15
                                                        '@@VALUE@@|'.$this->opt['show_params'][0],//'@@VALUE|' . /*$params['args']*/$this->opt['show_params'][0] . '@@',//edit by Hanjiazhuang. 2015.1.15
                                                        '@@DISPLAY@@',
                                                        '@@ID@@',
                                                        '@@CLASS@@'
                                                    ),
                                                    $params
                                                )));
    }
}
