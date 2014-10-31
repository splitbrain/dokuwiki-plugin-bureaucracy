<?php
class syntax_plugin_bureaucracy_field_file extends syntax_plugin_bureaucracy_field {
    function __construct($args) {
        parent::__construct($args);
        $this->tpl = form_makeFileField('@@NAME@@', '@@DISPLAY@@', '', '@@CLASS@@');
    }

    protected function _validate() {
        global $lang;
        parent::_validate();
        
        $value = $this->getParam('value');
        if($value['error'] == 1 || $value['error'] == 2) {   
            throw new Exception(sprintf($lang['uploadsize'],filesize_h(php_to_byte(ini_get('upload_max_filesize')))));
        } else if($value['error'] == 4) {
            if(!isset($this->opt['optional'])) {
                throw new Exception(sprintf($this->getLang('e_required'),hsc($this->opt['label'])));
            }
        } else if( $value['error'] || !is_uploaded_file($value['tmp_name'])) {
            throw new Exception(hsc($this->opt['label']) .' '. $lang['uploadfail'] . ' (' .$value['error'] . ')' );
        }
    }
    
    //validate against filename
    function validate_match($d, $value) {
        return @preg_match('/' . $d . '/i', $value['name']);
    }
}
