<?php
/**
 * Class helper_plugin_bureaucracy_fieldsubmit
 *
 * Creates a submit button
 */

class helper_plugin_bureaucracy_fieldsubmit extends helper_plugin_bureaucracy_field {
    protected $mandatory_args = 1;
    static $captcha_displayed = array();
    static $captcha_checked = array();

    /**
     * Arguments:
     *  - cmd
     *  - label (optional)
     *  - ^ (optional)
     *
     * @param array $args The tokenized definition, only split at spaces
     */
    public function initialize($args) {
        parent::initialize($args);
        // make always optional to prevent being marked as required
        $this->opt['optional'] = true;
    }

    function form_button($attrs)
    {
        $p = (!empty($attrs['_action'])) ? 'name="do[' . $attrs['_action'] . ']" ' : '';
        $label = $attrs['label'];
        unset($attrs['label']);
        return '<button ' . $p . buildAttributes($attrs, true) . '>' . $label . '</button>';
    }

    /**
     * Render the field as XHTML
     *
     * @params array     $params Additional HTML specific parameters
     * @params Doku_Form $form   The target Doku_Form object
     * @params int       $formid unique identifier of the form which contains this field
     */
    public function renderfield($params, Doku_Form $form, $formid) {
        if(!isset(helper_plugin_bureaucracy_fieldsubmit::$captcha_displayed[$formid])) {
            helper_plugin_bureaucracy_fieldsubmit::$captcha_displayed[$formid] = true;
            /** @var helper_plugin_captcha $helper */
            $helper = null;
            if(@is_dir(DOKU_PLUGIN.'captcha')) $helper = plugin_load('helper','captcha');
            if(!is_null($helper) && $helper->isEnabled()){
                $form->addElement($helper->getHTML());
            }
        }

        $attr = array();
        $attr['name'] = 'submit';
        if(isset($this->opt['value'])) {
            $attr['value'] = $this->opt['value'];
        }
        if(isset($this->opt['label'])) {
            $attr['label'] = $this->opt['label'];
        }
        if(isset($this->opt['id'])) {
            $attr['id'] = $this->opt['id'];
        }
        if(isset($this->opt['class'])) {
            $attr['class'] = $this->opt['class'];
        }

        $this->tpl = form_makeButton('submit','', '@@DISPLAY|' . $this->getLang('submit') . '@@', $attr);

        $this->_handlePreload();

        if(!$form->_infieldset){
            $form->startFieldset('');
        }
        if ($this->error) {
            $params['class'] = 'bureaucracy_error';
        }

        $params = array_merge($this->opt, $params);
        $element = $this->_parse_tpl($this->tpl, $params);
        $form->addElement($this->form_button($element));

    }

    /**
     * Handle a post to the field
     *
     * Accepts and validates a posted captcha value.
     *
     * @param string $value The passed value
     * @param helper_plugin_bureaucracy_field[] $fields (reference) form fields (POST handled upto $this field)
     * @param int    $index  index number of field in form
     * @param int    $formid unique identifier of the form which contains this field
     * @return bool Whether the posted f$_POSTorm has a valid captcha
     */
    public function handle_post($value, &$fields, $index, $formid) {

        // Set the value of the submit filed to the label of the button which was pressed
        $this->setVal($_POST['submit']);

        if ($this->hidden) {
            return true;
        }
        if(!isset(helper_plugin_bureaucracy_fieldsubmit::$captcha_checked[$formid])) {
            helper_plugin_bureaucracy_fieldsubmit::$captcha_checked[$formid] = true;
            // check CAPTCHA
            /** @var helper_plugin_captcha $helper */
            $helper = null;
            if(@is_dir(DOKU_PLUGIN.'captcha')) $helper = plugin_load('helper','captcha');
            if(!is_null($helper) && $helper->isEnabled()){
                return $helper->check();
            }
        }
        return true;
    }

    /**
     * Get an arbitrary parameter
     *
     * @param string $name
     * @return mixed|null
     */
    public function getParam($name) {
        return ($name === 'value') ? (($this->hidden)? null : parent::getParam($name)) : parent::getParam($name);
    }

}
