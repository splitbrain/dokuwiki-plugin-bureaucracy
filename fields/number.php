<?php
require_once DOKU_PLUGIN . 'bureaucracy/fields/textbox.php';
class syntax_plugin_bureaucracy_field_number extends syntax_plugin_bureaucracy_field_textbox {
    function handle_post($value) {
        if (!parent::handle_post($value)) {
            return false;
        }

        if(!is_numeric($value)){
            msg(sprintf($this->getLang('e_numeric'),hsc($opt['label'])),-1);
            return false;
        }
        return true;
    }
}
