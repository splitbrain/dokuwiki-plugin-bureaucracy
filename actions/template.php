<?php
/**
 * Simple template replacement action for the burreaucracy plugin
 *
 * @author Michael Klier <chi@chimeric.de>
 */
class syntax_plugin_bureaucracy_action_template extends syntax_plugin_bureaucracy_actions {


    function run($data, $thanks, $argv, $errors) {
        global $ID;

        $tpl = cleanID(array_shift($argv));

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

        $sep = '_'; //FIXME
        $pagename = '';


        // run through fields and do replacements
        foreach($data as $opt) {
            $value = $_POST['bureaucracy'][$opt['idx']];
            $label = preg_quote($opt['label']);
            if(in_array($opt['cmd'],$this->nofield)) continue;
            if($opt['pagename']) $pagename .= $sep . $value; // rmember values for pagename
            $pattern = '/@@' . $label . '@@/i';
            $template = preg_replace($pattern, $value, $template);
        }

        // check pagename
        $pagename = cleanID($pagename);
        if(!$pagename) {
            msg($this->getLang('e_pagename'), -1);
            return false;
        }
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
