<?php

function validate_match($d, $value) {
    return @preg_match('/' . $d . '/i', $value);
}

function validate_min($d, $value) {
    return $value > $d;
}

function validate_max($d, $value) {
    return $value < $d;
}

class syntax_plugin_bureaucracy_field {
    var $extraargs = 0;
    var $opt = array();
    var $checks = array();
    var $checktypes = array('/' => 'match', '<' => 'max', '>' => 'min');

    function syntax_plugin_bureaucracy_field($syntax_plugin, $args) {
        $this->syntax_plugin = $syntax_plugin;
        if(count($args) < $this->extraargs + 1){
            msg(sprintf($this->getLang('e_missingargs'),hsc($args[0]),hsc($args[1])),-1);
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
                $this->opt['value'] = substr($arg,1);
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
                $this->checks[] = array('t' => $t, 'd' => $d);
            }
        }
    }

    function render($params, $form) {
        if(!$form->_infieldset){
            $form->startFieldset('');
        }
        $params = array_merge($this->opt, $params);
        $form->addElement($this->_parse_tpl($this->tpl, $params));
    }

    function handle_post($value) {
        if (trim($value) === '') {
            if(isset($this->opt['optional'])) return true;
            msg(sprintf($this->getLang('e_required'),hsc($this->opt['label'])),-1);
            return false;
        }

        $this->opt['value'] = $value;

        foreach ($this->checks as $check) {
            $checktype = $this->checktypes[$check['t']];
            if (!call_user_func('validate_' . $checktype, $check['d'], $value)) {
                msg(sprintf($this->getLang('e_' . $checktype),
                            hsc($this->opt['label']), hsc($check['d'])), -1);
                return false;
            }
        }

        return true;
    }

    function _parse_tpl($tpl, $params) {
        if (is_array($tpl)) {
            /* addElement supports a special array format as well. */
            foreach ($tpl as $key => &$val) {
                $val = $this->_parse_tpl($val, $params);
            }
            return $tpl;
        }
        preg_match_all('/@@([A-Z]+)(?:\|((?:[^@]|@$|@[^@])+))?@@/', $tpl, &$pregs);
        for ($i = 0 ; $i < count($pregs[2]) ; ++$i) {
            if (isset($params[strtolower($pregs[1][$i])])) {
                $pregs[2][$i] = $params[strtolower($pregs[1][$i])];
            }
            $pregs[2][$i] = hsc($pregs[2][$i]);
        }
        return str_replace($pregs[0], $pregs[2], $tpl);
    }

    function getLang($param) {
        return $this->syntax_plugin->getLang($param);
    }

    function getFieldType() {
        return $this->opt['cmd'];
    }

    function getParam($name) {
        if (!isset($this->opt[$name])) {
            return null;
        }
        return $this->opt[$name];
    }
}
