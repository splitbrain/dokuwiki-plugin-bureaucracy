<?php
/**
 * Class syntax_plugin_bureaucracy_field_submit
 *
 * Creates a submit button
 */
class syntax_plugin_bureaucracy_field_submit extends syntax_plugin_bureaucracy_field {
    protected $mandatory_args = 1;
    static $captcha_displayed = false;
    static $captcha_checked = false;

    /**
     * Arguments:
     *  - cmd
     *  - label (optional)
     *
     * @param array $args The tokenized definition, only split at spaces
     */
    public function __construct($args) {
        parent::__construct($args);
        // make always optional to prevent being marked as required
        $this->opt['optional'] = true;
    }

    /**
     * Render the field as XHTML
     *
     * @params array     $params Additional HTML specific parameters
     * @params Doku_Form $form   The target Doku_Form object
     */
    public function renderfield($params, Doku_Form $form) {
        if(!syntax_plugin_bureaucracy_field_submit::$captcha_displayed){
            syntax_plugin_bureaucracy_field_submit::$captcha_displayed = true;
            /** @var helper_plugin_captcha $helper */
            $helper = null;
            if(@is_dir(DOKU_PLUGIN.'captcha')) $helper = plugin_load('helper','captcha');
            if(!is_null($helper) && $helper->isEnabled()){
                $form->addElement($helper->getHTML());
            }
        }
        $this->tpl = form_makeButton('submit','', '@@DISPLAY|' . $this->getLang('submit') . '@@');
        parent::renderfield($params, $form);
    }

    /**
     * Handle a post to the field
     *
     * Accepts and validates a posted captcha value.
     *
     * @param string $value The passed value
     * @return bool|array Whether the passed value is valid
     */
    public function handle_post(&$value) {
        if ($this->hidden) {
            return true;
        }
        if(!syntax_plugin_bureaucracy_field_submit::$captcha_checked){
            syntax_plugin_bureaucracy_field_submit::$captcha_checked = true;
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
        return ($name === 'value') ? null : parent::getParam($name);
    }

}
