<?php

/**
 * Base class for form fields
 *
 * This class provides basic functionality for many form fields. It supports
 * labels, basic validation and template-based XHTML output.
 *
 * @author Adrian Lang <lang@cosmocode.de>
 **/

require_once DOKU_PLUGIN.'bureaucracy/syntax.php';

class syntax_plugin_bureaucracy_field extends syntax_plugin_bureaucracy {
    var $mandatory_args = 2;
    var $opt = array();
    var $checks = array();
    var $checktypes = array('/' => 'match', '<' => 'max', '>' => 'min');
    var $hidden = false;
    var $error = false;

    /**
     * Construct a syntax_plugin_bureaucracy_field object
     *
     * This constructor initializes a syntax_plugin_bureaucracy_field object
     * based on a given definition. The first two items represent the type of
     * the field and the label the field has been given. Additional arguments
     * are type-specific mandatory extra arguments and optional arguments. The
     * optional arguments may add constraints to the field value, provide a
     * default value, mark the field as optional or define that the field is
     * part of a pagename (when using the template action).
     *
     * Since the field objects are cached, this constructor may not reference
     * request data.
     *
     * @param array                     $args          The tokenized definition
     **/
    function syntax_plugin_bureaucracy_field($args) {
        $this->init($args);
        $this->standardArgs($args);
    }

    function init(&$args) {
        if(count($args) < $this->mandatory_args){
            msg(sprintf($this->getLang('e_missingargs'), hsc($args[0]),
                        hsc($args[1])), -1);
            return;
        }

        // get standard arguments
        $this->opt = array();
        foreach (array('cmd', 'label') as $key) {
            if (count($args) === 0) break;
            $this->opt[$key] = array_shift($args);
        }
    }

    function standardArgs($args) {
        // parse additional arguments
        foreach($args as $arg){
            if ($arg[0] == '=') {
                $this->setVal(substr($arg,1));
            } elseif ($arg == '!') {
                $this->opt['optional'] = true;
            } elseif($arg == '@') {
                $this->opt['pagename'] = true;
            } elseif(preg_match('/x\d/', $arg)) {
                $this->opt['rows'] = substr($arg,1);
            } else {
                $t = $arg[0];
                $d = substr($arg,1);
                if (in_array($t, array('>', '<')) && !is_numeric($d)) {
                    break;
                }
                if ($t == '/') {
                    if (substr($d, -1) !== '/') {
                        break;
                    }
                    $d = substr($d, 0, -1);
                }
                if (!isset($this->checktypes[$t]) || !method_exists($this, 'validate_' . $this->checktypes[$t])) {
                    msg(sprintf($this->getLang('e_unknownconstraint'), hsc($t)), -1);
                    return;
                }
                $this->checks[] = array('t' => $t, 'd' => $d);
            }
        }
    }

    /**
     * Render the field as XHTML
     *
     * Outputs the represented field using the passed Doku_Form object.
     * Additional parameters (CSS class & HTML name) are passed in $params.
     * HTML output is created by passing the template $this->tpl to the simple
     * template engine _parse_tpl.
     *
     * @params array     $params Additional HTML specific parameters
     * @params Doku_Form $form   The target Doku_Form object
     **/
    function render($params, $form) {
        $this->_handlePreload();
        if(!$form->_infieldset){
            $form->startFieldset('');
        }
        if ($this->error) {
            $params['class'] = 'bureaucracy_error';
        }

        $params = array_merge($this->opt, $params);
        if(!isset($this->opt['optional'])) {
            $params["label"].=" *";
        }
        $form->addElement($this->_parse_tpl($this->tpl, $params));
    }


    /**
     * Check for preload value in the request
     */
    function _handlePreload() {
        $preload_name = '@' . strtr($this->getParam('label'),' .','__') . '@';
        if (isset($_GET[$preload_name])) {
            $this->setVal($_GET[$preload_name]);
        }
    }

    /**
     * Handle a post to the field
     *
     * Accepts and validates a posted value.
     *
     * @param string $value The passed value or null if none given
     *
     * @return bool|array Wether the passed value is valid; Fieldsets return
     *                    an array specifying their dependency state.
     **/
    function handle_post($value) {
        return $this->hidden || $this->setVal($value);
    }

    /**
     * Get the field type
     **/
    function getFieldType() {
        return $this->opt['cmd'];
    }

    function setVal($value) {
        if ($value === '') {
            $value = null;
        }
        $this->opt['value'] = $value;
        try {
            $this->_validate();
            $this->error = false;
        } catch (Exception $e) {
            msg($e->getMessage(), -1);
            $this->error = true;
        }
        return !$this->error;
    }

    /**
     * Whether the field is true (used for depending fieldsets)
     */
    public function isSet_() {
        return !is_null($this->getParam('value'));
    }

    protected function _validate() {
        $value = $this->getParam('value');
        if (is_null($value)) {
            if(!isset($this->opt['optional'])) {
                throw new Exception(sprintf($this->getLang('e_required'),hsc($this->opt['label'])));
            }
            return;
        }

        foreach ($this->checks as $check) {
            $checktype = $this->checktypes[$check['t']];
            if (!call_user_func(array($this, 'validate_' . $checktype), $check['d'], $value)) {
                throw new Exception(sprintf($this->getLang('e_' . $checktype),
                                            hsc($this->opt['label']), hsc($check['d'])));
            }
        }
    }

    /**
     * Get an arbitrary parameter
     **/
    function getParam($name) {
        if (!isset($this->opt[$name]) ||
            $name === 'value' && $this->hidden) {
            return null;
        }
        if ($name === 'pagename') {
            // If $this->opt['pagename'] is set, return the escaped value of
            // the field.
            $value = $this->getParam('value');
            if (is_null($value)) {
                return null;
            }
            global $conf;
            if($conf['useslash']) $value = str_replace('/',' ',$value);
            return str_replace(':',' ',$value);
        }
        return $this->opt[$name];
    }

    /**
     * Parse a template with given parameters
     *
     * Replaces variables specified like @@VARNAME|default@@ using the passed
     * value map.
     *
     * @param string|array $tpl    The template as string or array
     * @param array        $params A hash mapping parameters to values
     *
     * @return string|array The parsed template
     **/
    function _parse_tpl($tpl, $params) {
        /* addElement supports a special array format as well. In this case
           elements should not be escaped. */
        $esc = !is_array($tpl);
        if ($esc) {
            $tpl = array($tpl);
        }
        foreach ($tpl as &$val) {
            /* Select box passes options as an array. We do not escape those. */
            if (is_array($val)) continue;
            preg_match_all('/@@([A-Z]+)(?:\|((?:[^@]|@$|@[^@])+))?@@/', $val, $pregs);
            for ($i = 0 ; $i < count($pregs[2]) ; ++$i) {
                if (isset($params[strtolower($pregs[1][$i])])) {
                    $pregs[2][$i] = $params[strtolower($pregs[1][$i])];
                }
            }
            if ($esc) {
                $pregs[2] = array_map('hsc', $pregs[2]);
            }
            $val = str_replace($pregs[0], $pregs[2], $val);
        }
        return $esc ? $tpl[0] : $tpl;
    }

    function validate_match($d, $value) {
        return @preg_match('/' . $d . '/i', $value);
    }

    function validate_min($d, $value) {
        return $value > $d;
    }

    function validate_max($d, $value) {
        return $value < $d;
    }

    function after_action() {
    }
}
