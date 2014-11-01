<?php
/**
 * Class syntax_plugin_bureaucracy_field_submit
 *
 * Creates a submit button
 */
class syntax_plugin_bureaucracy_field_submit extends syntax_plugin_bureaucracy_field {
    protected $mandatory_args = 1;
    static $captcha_displayed = array();
    static $captcha_checked = array();

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
     * @params int       $formid
     */
    public function renderfield($params, Doku_Form $form, $formid) {
        if(!isset(syntax_plugin_bureaucracy_field_submit::$captcha_displayed[$formid])) {
            syntax_plugin_bureaucracy_field_submit::$captcha_displayed[$formid] = true;
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
     * @param int    $formid
     * @return bool Whether the posted form has a valid captcha
     */
    public function handle_post(&$value, $formid) {
        if ($this->hidden) {
            return true;
        }
        if(!isset(syntax_plugin_bureaucracy_field_submit::$captcha_checked[$formid])) {
            syntax_plugin_bureaucracy_field_submit::$captcha_checked[$formid] = true;
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
