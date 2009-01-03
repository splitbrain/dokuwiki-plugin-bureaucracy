<?php
/**
 * Simple template replacement action for the burreaucracy plugin
 *
 * @author Michael Klier <chi@chimeric.de>
 */
class syntax_plugin_bureaucracy_action_template extends syntax_plugin_bureaucracy_actions {


    function run($data, $thanks, $argv, $errors) {
        global $ID;
        global $conf;

        $tpl = cleanID(array_shift($argv));
        $ns  = cleanID(array_shift($argv));
        $sep = array_shift($argv); // used for combining pagename parts
        if(is_null($sep)) $sep = $conf['sepchar'];

        if(auth_quickaclcheck($tpl) < AUTH_READ){
            msg(sprintf($this->getLang('e_template'), $tpl), -1);
            return false;
        }

        // fetch template
        $template = rawWiki($tpl);

        if(empty($template)) {
            msg(sprintf($this->getLang('e_template'), $tpl), -1);
            return false;
        }

        $pagename = '';

        // run through fields and do replacements
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
            $pattern = '/@@' . $label . '@@/i';
            $template = preg_replace($pattern, $value, $template);
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
        if(auth_quickaclcheck($pagename) < AUTH_CREATE) {
            msg($this->getLang('e_denied'), -1);
            return false;
        }

        // still no pagename die!

        saveWikiText($pagename, $template, sprintf($this->getLang('summary'),$ID));
        $this->success = $thanks.' '.html_wikilink($pagename);
        return true;
    }

    /**
     * Replace callback to iterate over multiline textarea patterns
     */
    function replace_callback($matches) {
        $lines = explode("\n", trim($this->value));
        $ret = '';
        foreach($lines as $line) {
            $ret .= trim(preg_replace("/@@LINE@@/", trim($line), trim($matches[1]))) . "\n";
        }
        return ($ret);
    }
}
// vim:ts=4:sw=4:et:enc=utf-8:
