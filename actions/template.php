<?php
/**
 * Simple template replacement action for the bureaucracy plugin
 *
 * @author Michael Klier <chi@chimeric.de>
 */

class syntax_plugin_bureaucracy_action_template extends syntax_plugin_bureaucracy_action {

    function run($data, $thanks, $argv) {
        global $ID;
        global $conf;

        list($tpl, $ns, $sep) = $argv;
        if(is_null($sep)) $sep = $conf['sepchar'];

        $pagename = '';
        $patterns = array();
        $values   = array();
        $templates = array();

        // run through fields
        foreach($data as $opt) {
            $value = $opt->getParam('value');
            $label = $opt->getParam('label');

            // prepare replacements
            if(!is_null($value) && !is_null($label)) {
                $patterns[] = '/(@@|##)'.preg_quote($label, '/').'(@@|##)/i';
                $values[]   = $value;
            }

            // handle pagenames
            if(!is_null($opt->getParam('pagename')) && !is_null($value)){
                // no namespace separators in input allowed:
                $name = $value;
                if($conf['useslash']) $name = str_replace('/',' ',$name);
                $name = str_replace(':',' ',$name);
                $pagename .= $sep . $name;
            }

            if (!is_null($opt->getParam('page_tpl')) &&
                !is_null($opt->getParam('page_tgt'))) {
                $page_tpl = preg_replace($patterns, $values, $opt->getParam('page_tpl'));
                if (auth_aclcheck($page_tpl, $runs ? $runas : $_SERVER['REMOTE_USER'],
                                  $USERINFO['grps']) >= AUTH_READ) {
                    $templates[$opt->getParam('page_tgt')] = rawWiki($page_tpl);
                }
            }
        }

        // check pagename
        $pagename = cleanID($pagename);
        if(!$pagename) {
            throw new Exception($this->getLang('e_pagename'));
        }
        $pagename = cleanID($ns).':'.$pagename;
        if(page_exists($pagename)) {
            throw new Exception(sprintf($this->getLang('e_pageexists'), html_wikilink($pagename)));
        }

        $_templates = array();
        foreach($templates as $k => $v) {
            $_templates["$pagename:$k"] = $v;
        }
        $templates = $_templates;

        // check auth
        $runas = $this->getConf('runas');
        if($runas){
            $auth = auth_aclcheck($pagename,$runas,array());
        }else{
            $auth = auth_quickaclcheck($pagename);
        }
        if($auth < AUTH_CREATE) {
            throw new Exception($this->getLang('e_denied'));
        }

        // get templates
        if($tpl == '_'){
            // use namespace template
            $templates[$pagename] = pageTemplate(array($pagename));
        } elseif($tpl !== '!') {
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
            throw new Exception(sprintf($this->getLang('e_template'), $tpl));
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
