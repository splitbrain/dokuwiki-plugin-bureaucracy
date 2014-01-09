<?php
require_once DOKU_PLUGIN . 'bureaucracy/fields/textbox.php';
/**
 * Class syntax_plugin_bureaucracy_field_number
 *
 * Creates a single line input field, where input is validated to be numeric
 */
class syntax_plugin_bureaucracy_field_number extends syntax_plugin_bureaucracy_field_textbox {

    private $autoinc = false;

    /**
     * Arguments:
     *  - cmd
     *  - label
     *  - ++ (optional)
     *
     * @param array $args The tokenized definition, only split at spaces
     */
    public function __construct($args) {
        $pp = array_search('++', $args, true);
        if ($pp !== false) {
            unset($args[$pp]);
            $this->autoinc = true;
        }

        parent::__construct($args);

        if ($this->autoinc) {
            global $ID;
            $key = $this->get_key();
            $c_val = p_get_metadata($ID, 'bureaucracy ' . $key);
            if (is_null($c_val)) {
                if (!isset($this->opt['value'])) {
                    $this->opt['value'] = 0;
                }
                p_set_metadata($ID, array('bureaucracy' => array($key => $this->opt['value'])));
            } else {
                $this->opt['value'] = $c_val;
            }
        }
    }

    /**
     * Validate field value
     *
     * @throws Exception when not a number
     */
    protected function _validate() {
        $value = $this->getParam('value');
        if (!is_null($value) && !is_numeric($value)){
            throw new Exception(sprintf($this->getLang('e_numeric'),hsc($this->getParam('display'))));
        }

        parent::_validate();
    }

    /**
     * Returns the cleaned key for this field required for metadata
     *
     * @return string key
     */
    private function get_key() {
        return preg_replace('/\W/', '', $this->opt['label']) . '_autoinc';
    }

    /**
     * Executed after performing the action hooks
     *
     * Increases counter and purge cache
     */
    public function after_action() {
        if ($this->autoinc) {
            global $ID;
            p_set_metadata($ID, array('bureaucracy' => array($this->get_key() => $this->opt['value'] + 1)));
            // Force rerendering by removing the instructions cache file
            $cache_fn = getCacheName(wikiFN($ID).$_SERVER['HTTP_HOST'].$_SERVER['SERVER_PORT'],'.'.'i');
            if (file_exists($cache_fn)) {
                unlink($cache_fn);
            }
        }
    }
}
