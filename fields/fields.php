<?php
class syntax_plugin_bureaucracy_field {
    var $extraargs = 0;
    var $opts = array();

    function syntax_plugin_bureaucracy_field($syntax_plugin, $args) {
        $this->syntax_plugin = $syntax_plugin;
        if(count($args) < $this->extraargs + 1){
            msg(sprintf($this->getLang('e_missingargs'),hsc($args[0]),hsc($args[1])),-1);
            return;
        }
        // get standard arguments
        $this->cmd = array_shift($args);

        $this->opt = array('label' => array_shift($args));

        // save additional minimum args here
        $keep = $this->extraargs - 1;
        if($keep > 0){
            $this->opt['args'] = array_slice($args,0,$keep);
        }

        // parse additional arguments
        foreach($args as $arg){
            if($arg[0] == '='){
                $this->opt['value'] = substr($arg,1);
            }elseif($arg[0] == '>'){
                $this->opt['min'] = substr($arg,1);
                if(!is_numeric($this->opt['min'])) unset($this->opt['min']);
            }elseif($arg[0] == '<'){
                $this->opt['max'] = substr($arg,1);
                if(!is_numeric($this->opt['max'])) unset($this->opt['max']);
            }elseif($arg[0] == '/' && substr($arg,-1) == '/'){
                $this->opt['re'] = substr($arg,1,-1);
            }elseif($arg == '!'){
                $this->opt['optional'] = true;
            }elseif($arg == '@'){
                $this->opt['pagename'] = true;
            }elseif(preg_match('/x\d/', $arg)) {
                $this->opt['rows'] = substr($arg,1);
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

        // regexp
        if(isset($this->opt['re']) && !@preg_match('/'.$this->opt['re'].'/i',$value)){
            msg(sprintf($this->getLang('e_match'),hsc($this->opt['label']),hsc($this->opt['re'])),-1);
            return false;
        }

        // min
        if(isset($this->opt['min']) && $value < $this->opt['min']){
            msg(sprintf($this->getLang('e_min'),hsc($this->opt['label']),hsc($this->opt['min'])),-1);
            return false;
        }

        // max
        if(isset($this->opt['max']) && $value > $this->opt['max']){
            msg(sprintf($this->getLang('e_max'),hsc($this->opt['label']),hsc($this->opt['max'])),-1);
            return false;
        }

        $this->opt['value'] = $value;
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

    function getLabel() {
        return $this->opt['label'];
    }

    function getValue() {
        return $this->opt['value'];
    }
}
