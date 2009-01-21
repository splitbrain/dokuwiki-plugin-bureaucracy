<?php
/**
 * Simple template replacement action for the burreaucracy plugin
 *
 * @author Michael Klier <chi@chimeric.de>
 */
class syntax_plugin_bureaucracy_action_template extends syntax_plugin_bureaucracy_actions {


    function run($data, $thanks, $argv, &$errors) {
        global $ID;
        global $conf;

        $tpl = cleanID(array_shift($argv));
        $ns  = cleanID(array_shift($argv));
        $sep = array_shift($argv); // used for combining pagename parts
        if(is_null($sep)) $sep = $conf['sepchar'];


        $pagename = '';
        $patterns = array();
        $values   = array();

        // run through fields and prepare replacements
        foreach($data as $opt) {
            $value = $_POST['bureaucracy'][$opt['idx']];
            $label = preg_quote($opt['label']);
            if(in_array($opt['cmd'],$this->nofield)) continue;
            // handle pagenames:
            if($opt['pagename']){
                // no namespace seperators in input allowed:
                $name = $value;
                if($conf['useslash']) $name = str_replace('/',' ',$name);
                $name = str_replace(':',' ',$name);
                $pagename .= $sep . $name;
            }
            $patterns[] = '/(@@|##)'.$label.'(@@|##)/i';
            $values[]   = $value;
        }

        // check pagename
        $pagename = cleanID($pagename);
        if(!$pagename) {
            msg($this->getLang('e_pagename'), -1);
            return false;
        }
        $pagename = $ns.':'.$pagename;
        if(page_exists($pagename)) {
            msg(sprintf($this->getLang('e_pageexists'), html_wikilink($pagename)), -1);
            return false;
        }

        // check auth
        $runas = $this->getConf('runas');
        if($runas){
            $auth = auth_aclcheck($pagename,$runas,array());
        }else{
            $auth = auth_quickaclcheck($pagename);
        }
        if($auth < AUTH_CREATE) {
            msg($this->getLang('e_denied'), -1);
            return false;
        }

        // get template
        if($tpl == ''){
            // use namespace template
            $template = pageTemplate(array($pagename));
        }else{
            $tpl = cleanID($tpl);
            if($runas){
                $auth = auth_aclcheck($tpl,$runas,array());
            }else{
                $auth = auth_quickaclcheck($tpl);
            }
            if($auth < AUTH_READ){
                msg(sprintf($this->getLang('e_template'), $tpl), -1);
                return false;
            }
            // fetch template
            $template = rawWiki($tpl);
        }
        if(empty($template)) {
            msg(sprintf($this->getLang('e_template'), $tpl), -1);
            return false;
        }

        // do the replacements
        $template = preg_replace($patterns,$values,$template);

        // save page and return
        saveWikiText($pagename, $template, sprintf($this->getLang('summary'),$ID));
        return $thanks.' '.html_wikilink($pagename);
    }

}
// vim:ts=4:sw=4:et:enc=utf-8:
