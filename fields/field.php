<?php

/**
 * Base class for form fields
 *
 * This class provides basic functionality for many form fields. It supports
 * labels, basic validation and template-based XHTML output.
 *
 * @author Adrian Lang <lang@cosmocode.de>
 **/

class syntax_plugin_bureaucracy_field {
    var $extraargs = 0;
    var $opt = array();
    var $checks = array();
    var $checktypes = array('/' => 'match', '<' => 'max', '>' => 'min');
    var $syntax_plugin = null;
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
     * @param syntax_plugin_bureaucracy $syntax_plugin A syntax plugin; used
     *                                                 for getLang
     * @param array                     $args          The tokenized definition
     **/
    function syntax_plugin_bureaucracy_field($syntax_plugin, $args) {
        $this->syntax_plugin = $syntax_plugin;
        if(count($args) < $this->extraargs + 1){
            msg(sprintf($this->getLang('e_missingargs'), hsc($args[0]),
                        hsc($args[1])), -1);
            return;
        }

        // get standard arguments
        $this->opt = array('cmd'   => array_shift($args),
                           'label' => array_shift($args));

        // save additional minimum args here
        $keep = $this->extraargs - 1;
        if($keep > 0){
            $this->opt['args'] = array_slice($args,0,$keep);
        }
        $add_args = array_slice($args, $keep);
        // parse additional arguments
        foreach($add_args as $arg){
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
        if(!$form->_infieldset){
            $form->startFieldset('');
        }
        if ($this->error) {
            $params['class'] = 'bureaucracy_error';
        }

        $params = array_merge($this->opt, $params);
        $form->addElement($this->_parse_tpl($this->tpl, $params));
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
        if (trim($value) === '') {
            if(isset($this->opt['optional'])) return true;
            msg(sprintf($this->getLang('e_required'),hsc($this->opt['label'])),-1);
            $this->error = true;
            return false;
        }
        $this->opt['value'] = $value;

        foreach ($this->checks as $check) {
            $checktype = $this->checktypes[$check['t']];
            if (!call_user_func(array($this, 'validate_' . $checktype), $check['d'], $value)) {
                msg(sprintf($this->getLang('e_' . $checktype),
                            hsc($this->opt['label']), hsc($check['d'])), -1);
                $this->error = true;
                return false;
            }
        }

        return true;
    }

    /**
     * Get an arbitrary parameter
     **/
    function getParam($name) {
        if (!isset($this->opt[$name]) ||
            $name === 'value' && $this->hidden) {
            return null;
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
            preg_match_all('/@@([A-Z]+)(?:\|((?:[^@]|@$|@[^@])+))?@@/', $val, &$pregs);
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

    function getLang($param) {
        return $this->syntax_plugin->getLang($param);
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
}
