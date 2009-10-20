<?php
class syntax_plugin_bureaucracy_field_submit extends syntax_plugin_bureaucracy_field {
    static $captcha_displayed = false;
    static $captcha_checked = false;

    function render($params, $form) {
        if(!syntax_plugin_bureaucracy_field_submit::$captcha_displayed){
            syntax_plugin_bureaucracy_field_submit::$captcha_displayed = true;
            $helper = null;
            if(@is_dir(DOKU_PLUGIN.'captcha')) $helper = plugin_load('helper','captcha');
            if(!is_null($helper) && $helper->isEnabled()){
                $form->addElement($helper->getHTML());
            }
        }
        $this->tpl = form_makeButton('submit','', '@@LABEL@@');
        parent::render($params, $form);
    }

    function handle_post($param) {
        if(!syntax_plugin_bureaucracy_field_submit::$captcha_checked){
            syntax_plugin_bureaucracy_field_submit::$captcha_checked = true;
            // check CAPTCHA
            $helper = null;
            if(@is_dir(DOKU_PLUGIN.'captcha')) $helper = plugin_load('helper','captcha');
            if(!is_null($helper) && $helper->isEnabled()){
                return $helper->check();
            }
        }
        return true;
    }

    function getParam($name) {
        return ($name === 'value') ? null : parent::getParam($name);
    }

}
