<?php
/**
 * Simple template replacement action for the burreaucracy plugin
 *
 * @author Michael Klier <chi@chimeric.de>
 */
class syntax_plugin_bureaucracy_action_template extends syntax_plugin_bureaucracy_actions {

    // tmp value used for textareas
    var $value = '';

    var $pagename = null;

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

        // replace normal stuff first
        foreach($data as $opt) {

            $value = $_POST['bureaucracy'][$opt['idx']];
            $parts = (explode(' ', $opt['label']));
            $label = strtoupper(trim($parts[0]));

            switch($opt['cmd']) {
                case 'textarea':
                    $pattern = '/@@' . $label . '!@@(.*?@@LINE@@.*?)@@!' . $label . '@@/s';
                    $this->value = $value;
                    $template = preg_replace_callback($pattern, array($this, 'replace_callback'), $template);
                    break;
                default:
                    if(in_array($opt['cmd'],$this->nofield)) break;
                    if($opt['pagename']) {
                        $this->pagename = cleanID($_POST['bureaucracy'][$opt['idx']]);
                        if(page_exists($this->pagename)) {
                            msg(sprintf($this->getLang('e_pageexists'), html_wikilink($this->pagename)), -1);
                            $errors[$opt['idx']] = 1;
                            return false;
                        }
                        if(auth_quickaclcheck($this->pagename) < AUTH_CREATE) {
                            msg($this->getLang('e_denied'), -1);
                            return false;
                        }
                    }
                    $pattern = '/@@' . $label . '@@/';
                    $template = preg_replace($pattern, $value, $template);
                    break;
            }
        }

        // still no pagename die!
        if(!$this->pagename) {
            msg($this->getLang('e_pagename'), -1);
            return false;
        }

        saveWikiText($this->pagename, $template, sprintf($this->getLang('summary'),$ID));
        $this->success = sprintf($thanks, html_wikilink($this->pagename));
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
