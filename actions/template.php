<?php
/**
 * Simple template replacement action for the bureaucracy plugin
 *
 * @author Michael Klier <chi@chimeric.de>
 */

class syntax_plugin_bureaucracy_action_template extends syntax_plugin_bureaucracy_action {

    var $templates;
    var $pagename;

    /**
     * Performs template action
     *
     * @param array  $fields  array with form fields
     * @param string $thanks  thanks message
     * @param array  $argv    array with arguments: template, pagename, separator
     * @return array|mixed
     *
     * @throws Exception
     */
    public function run($fields, $thanks, $argv) {
        global $conf;

        list($tpl, $this->pagename, $sep) = $argv;
        if(is_null($sep)) $sep = $conf['sepchar'];

        $runas = $this->getConf('runas');
        $this->patterns = array();
        $this->values   = array();
        $this->templates = array();

        $this->prepareLanguagePlaceholder();
        $this->prepareNoincludeReplacement();
        $this->processFields($fields, $sep, $runas);
        $this->buildTargetPageName();
        $this->resolveTemplates();
        list($tpl,,) = $this->getTemplates($fields, $tpl, $runas);

        if(empty($this->templates)) {
            throw new Exception(sprintf($this->getLang('e_template'), $tpl));
        }

        $this->checkTargetPageNames($runas);

        $this->replaceAndSavePages();

        $ret = $this->buildThankYouPage($thanks);

        return $ret;
    }

    static function _sort($a, $b) {
        $ns_diff = substr_count($a, ':') - substr_count($b, ':');
        return ($ns_diff === 0) ? strcmp($a, $b) : ($ns_diff > 0 ? -1 : 1);
    }

    static function html_list_index($item){
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



    /**
     * - Generate field replacements
     * - Handle page names (additional pages via addpage)
     *
     * @param array  $data  List of field objects
     * @param string $sep   Separator between fields for page id
     * @param string $runas User to run the command as - empty for current user
     * @return array
     */
    function processFields($data, $sep, $runas) {
        global $USERINFO;
        foreach ($data as $opt) {
            /** @var $opt syntax_plugin_bureaucracy_field */
            $label = $opt->getParam('label');
            $value = $opt->getParam('value');

            $this->prepareFieldReplacements($label, $value);

            // handle pagenames
            $pname = $opt->getParam('pagename');
            if (!is_null($pname)) {
                $this->pagename .= $sep . $pname;
            }

            if (!is_null($opt->getParam('page_tpl')) && !is_null($opt->getParam('page_tgt')) ) {
                $page_tpl = $this->replaceDefault($opt->getParam('page_tpl'));
                $user = $runas ? $runas : $_SERVER['REMOTE_USER'];
                if (auth_aclcheck($page_tpl, $user, $USERINFO['grps']) >= AUTH_READ ) {
                    $this->templates[$opt->getParam('page_tgt')] = $this->replace(array(), array(), rawWiki($page_tpl));
                }
            }
        }
    }

    /**
     * Prepare and resolve target page
     *
     * @throws Exception missing pagename
     */
    function buildTargetPageName() {
        global $ID;
        $this->pagename = $this->replaceDefault($this->pagename);
        $myns = getNS($ID);
        resolve_pageid($myns, $this->pagename, $junk); // resolve relatives
        if ($this->pagename === '') {
            throw new Exception($this->getLang('e_pagename'));
        }
    }

    /**
     * Resolve pageids of target pages
     */
    function resolveTemplates() {
        global $ID;
        $_templates = array();
        $ns = getNS($ID);
        foreach ($this->templates as $k => $v) {
            resolve_pageid($ns, $k, $ignored); // resolve template
            $_templates[cleanID("$this->pagename:$k")] = $v; // $this->pagename is already resolved
        }
        $this->templates = $_templates;
    }

    /**
     * @param $fields
     * @param $tpl
     * @param $runas
     * @return string template
     */
    function getTemplates($fields, $tpl, $runas) {
        global $USERINFO;
        global $conf;

        if ($tpl == '_') {
            // use namespace template
            if (!isset($this->templates[$this->pagename])) {
                $this->templates[$this->pagename] = pageTemplate(array($this->pagename));
            }
        } elseif ($tpl !== '!') {
            $tpl = $this->replaceDefault($tpl);
            // Namespace link
            if ($runas) {
                // Hack user credentials.
                $backup = array($_SERVER['REMOTE_USER'], $USERINFO['grps']);
                $_SERVER['REMOTE_USER'] = $runas;
                $USERINFO['grps'] = array();
            }
            $t_pages = array();
            search($t_pages, $conf['datadir'], 'search_universal',
                array('depth' => 0, 'listfiles' => true, 'showhidden' => true),
                str_replace(':', '/', getNS($tpl)));
            foreach ($t_pages as $t_page) {
                $t_name = cleanID($t_page['id']);
                $p_name = preg_replace('/^' . preg_quote_cb(cleanID($tpl)) . '($|:)/', $this->pagename . '$1', $t_name);
                if ($p_name === $t_name) {
                    // When using a single-page template, ignore other pages
                    // in the same namespace.
                    continue;
                }

                if (!isset($this->templates[$p_name])) {
                    // load page data and do default pattern replacements like
                    // namespace templates do
                    $data = array(
                        'id' => $p_name,
                        'tpl' => rawWiki($t_name),
                        'doreplace' => true,
                    );
                    parsePageTemplate($data);
                    $this->templates[$p_name] = $this->replace(
                        array('__lang__' => $this->patterns['__lang__'], '__trans__' => $this->patterns['__trans__']),
                        array('__lang__' => $this->values['__lang__'], '__trans__' => $this->values['__trans__']),
                        $data['tpl'], false);
                }
            }

            if ($runas) {
                /* Restore user credentials. */
                list($_SERVER['REMOTE_USER'], $USERINFO['grps']) = $backup;
            }
        }
        return $tpl;
    }

    /**
     * @param $runas
     * @return mixed
     * @throws Exception
     */
    function checkTargetPageNames($runas) {
        foreach (array_keys($this->templates) as $pname) {
            // prevent overriding already existing pages
            if (page_exists($pname)) {
                throw new Exception(sprintf($this->getLang('e_pageexists'), html_wikilink($pname)));
            }

            // check auth
            if ($runas) {
                $auth = auth_aclcheck($pname, $runas, array());
            } else {
                $auth = auth_quickaclcheck($pname);
            }
            if ($auth < AUTH_CREATE) {
                throw new Exception($this->getLang('e_denied'));
            }
        }
        return $pname;
    }

    function replaceAndSavePages() {
        global $ID;
        foreach ($this->templates as $pageName => $template) {
            // set NSBASE var to make certain dataplugin constructs easier
            $this->patterns['__nsbase__'] = '/@NSBASE@/';
            $this->values['__nsbase__'] = noNS(getNS($pageName));

            // save page
            saveWikiText($pageName,
                cleanText($this->replaceDefault($template, false)),
                sprintf($this->getLang('summary'), $ID));
        }
    }

    /**
     * Build thanks message, trigger indexing and rendering of new pages.
     *
     * @param string $thanks
     * @return string html of thanks message or when redirect the first page id of created pages
     */
    function buildThankYouPage($thanks) {
        global $ID;
        $ret = "<p>$thanks</p>";
        // Build result tree
        $pages = array_keys($this->templates);
        usort($pages, array($this, '_sort'));

        $oldid = $ID;
        $data = array();
        $last_folder = array();
        foreach ($pages as $ID) {
            $lvl = substr_count($ID, ':');
            for ($n = 0; $n < $lvl; ++$n) {
                if (!isset($last_folder[$n]) || strpos($ID, $last_folder[$n]['id']) !== 0) {
                    $last_folder[$n] = array(
                        'id' => substr($ID, 0, strpos($ID, ':', ($n > 0 ? strlen($last_folder[$n - 1]['id']) : 0) + 1) + 1),
                        'level' => $n + 1,
                        'open' => 1
                    );
                    $data[] = $last_folder[$n];
                }
            }
            $data[] = array('id' => $ID, 'level' => 1 + substr_count($ID, ':'), 'type' => 'f');
        }
        $ret .= html_buildlist($data, 'idx', array($this, 'html_list_index'), 'html_li_index');

        // Add indexer bugs for every just-created page
        $ret .= '<div class="no">';
        ob_start();
        foreach ($pages as $ID) {
            // indexerWebBug uses ID and INFO[exists], but the bureaucracy form
            // page always exists, as does the just-saved page, so INFO[exists]
            // is correct in any case
            tpl_indexerWebBug();

            // the iframe will trigger real rendering of the pages to make sure
            // any used plugins are initialized (eg. the do plugin)
            echo '<iframe src="' . wl($ID, array('do' => 'export_html')) . '" width="1" height="1" style="visibility:hidden"></iframe>';
        }
        $ret .= ob_get_contents();
        ob_end_clean();
        $ID = $oldid;
        $ret .= '</div>';
        return $ret;
    }

}
// vim:ts=4:sw=4:et:enc=utf-8:
