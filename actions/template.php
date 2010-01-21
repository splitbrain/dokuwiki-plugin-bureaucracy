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
        global $USERINFO;

        list($tpl, $ns, $sep) = $argv;
        if(is_null($sep)) $sep = $conf['sepchar'];

        $runas = $this->getConf('runas');
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
                if (auth_aclcheck($page_tpl, $runas ? $runas : $_SERVER['REMOTE_USER'],
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
            if (!isset($templates[$pagename])) {
                $templates[$pagename] = pageTemplate(array($pagename));
            }
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
            search($t_pages, $conf['datadir'], 'search_universal',
                   array('depth' => 0, 'listfiles' => true),
                   str_replace(':', '/', getNS($tpl)));
            foreach($t_pages as $t_page) {
                $t_name = cleanID($t_page['id']);
                $p_name = preg_replace('/^' . preg_quote_cb(cleanID($tpl)) . '/', $pagename, $t_name);
                if ($p_name === $t_name) {
                    // When using a single-page template, ignore other pages
                    // in the same namespace.
                    continue;
                }
                if (!isset($templates[$p_name])) {
                    $templates[$p_name] = rawWiki($t_name);
                }
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
            $template = strftime(preg_replace($patterns,$values,$template));

            // save page
            saveWikiText($pname, $template, sprintf($this->getLang('summary'),$ID));

        }

        // Build result tree
        $pages = array_keys($templates);
        usort($pages, array($this, '_sort'));

        $data = array();
        $last_folder = array();
        foreach($pages as $page) {
            $lvl = substr_count($page, ':');
            for ($n = 0 ; $n < $lvl ; ++$n) {
                if (!isset($last_folder[$n]) || strpos($page, $last_folder[$n]['id']) !== 0) {
                    $last_folder[$n] = array('id' => substr($page, 0, strpos($page, ':', ($n > 0 ? strlen($last_folder[$n - 1]['id']) : 0) + 1) + 1),
                                             'level' => $n + 1,
                                             'open' => 1);
                    $data[] = $last_folder[$n];
                }
            }
            $data[] = array('id' => $page, 'level' => 1 + substr_count($page, ':'), 'type' => 'f');
        }
        return '<p>' . $thanks . '</p>' . html_buildlist($data, 'idx', array($this, 'html_list_index'), 'html_li_index');
    }

    static function _sort($a, $b) {
        $ns_diff = substr_count($a, ':') - substr_count($b, ':');
        return ($ns_diff === 0) ? strcmp($a, $b) : ($ns_diff > 0 ? -1 : 1);
    }

    static function html_list_index($item){
        global $ID;
        $ret = '';
        $base = ':'.$item['id'];
        $base = substr($base,strrpos($base,':')+1);
        if($item['type']=='f'){
            $ret .= html_wikilink(':'.$item['id']);
        } else {
            $ret .= '<strong>' . trim(substr($item['id'], strrpos($item['id'], ':', -2)), ':') . '</strong>';
        }
        return $ret;
    }

}
// vim:ts=4:sw=4:et:enc=utf-8:
