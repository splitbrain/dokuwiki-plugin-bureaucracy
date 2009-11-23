<?php
/**
 * Simple template replacement action for the bureaucracy plugin
 *
 * @author Michael Klier <chi@chimeric.de>
 */

class syntax_plugin_bureaucracy_action_template extends syntax_plugin_bureaucracy_action {

    function run($data, $thanks, $argv, &$errors) {
        global $ID;
        global $conf;

        list($tpl, $ns, $sep) = $argv;
        if(is_null($sep)) $sep = $conf['sepchar'];

        $pagename = '';
        $patterns = array();
        $values   = array();

        // run through fields and prepare replacements
        foreach($data as $opt) {
            $value = $opt->getParam('value');
            $label = preg_quote($opt->getParam('label'),'/');
            if($value === null || $label === null) continue;
            // handle pagenames:
            if(!is_null($opt->getParam('pagename'))){
                // no namespace separators in input allowed:
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
        $pagename = cleanID($ns).':'.$pagename;
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

        $templates = array();
        // get templates
        if($tpl == '_'){
            // use namespace template
            $templates[$pagename] = pageTemplate(array($pagename));
        } else {
            // Namespace link
            require_once DOKU_INC.'inc/search.php';
            if ($runas) {
                /* Hack user credentials. */
                global $USERINFO;
                $backup = array($_SERVER['REMOTE_USER'],$USERINFO['grps']);
                $_SERVER['REMOTE_USER'] = $runas;
                $USERINFO['grps'] = array();
            }
            $t_pages = array();
            search($t_pages, $conf['datadir'], 'search_index', array(),
                   str_replace(':', '/', getNS($tpl)));
            foreach($t_pages as $t_page) {
                $t_name = cleanID($t_page['id']);
                $p_name = str_replace(cleanID($tpl), $pagename, $t_name);
                if ($p_name === $t_name) {
                    /* When using a single-page template, ignore other pages
                       in the same namespace. */
                    continue;
                }
                $templates[$p_name] = rawWiki($t_name);
            }

            if ($runas) {
                /* Restore user credentials. */
                global $USERINFO;
                list($_SERVER['REMOTE_USER'],$USERINFO['grps']) = $backup;
            }
        }
        if(empty($templates)) {
            msg(sprintf($this->getLang('e_template'), $tpl), -1);
            return false;
        }

        foreach($templates as $pname => $template) {

            // do the replacements
            $template = preg_replace($patterns,$values,$template);

            // save page and return
            saveWikiText($pname, $template, sprintf($this->getLang('summary'),$ID));

        }
        return $thanks.' '.implode(', ', array_map('html_wikilink', array_keys($templates)));
    }

}
// vim:ts=4:sw=4:et:enc=utf-8:
