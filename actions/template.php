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
     * @param syntax_plugin_bureaucracy_field[] $fields  array with form fields
     * @param string $thanks  thanks message
     * @param array  $argv    array with entries: template, pagename, separator
     * @return array|mixed
     *
     * @throws Exception
     */
    public function run($fields, $thanks, $argv) {
        global $conf;

        list($tpl, $this->pagename, $sep) = $argv;
        if(is_null($sep)) $sep = $conf['sepchar'];

        $this->patterns = array();
        $this->values   = array();
        $this->templates = array();

        $this->prepareLanguagePlaceholder();
        $this->prepareNoincludeReplacement();
        $this->processFields($fields, $sep);
        $this->buildTargetPageName();
        $this->resolveTemplates();
        $tpl = $this->getTemplates($tpl);

        if(empty($this->templates)) {
            throw new Exception(sprintf($this->getLang('e_template'), $tpl));
        }

        $this->checkTargetPageNames();
        
        $this->processUploads($fields);
        
        $this->replaceAndSavePages();

        $ret = $this->buildThankYouPage($thanks);

        return $ret;
    }

    /**
     * - Generate field replacements
     * - Handle page names (additional pages via addpage)
     *
     * @param syntax_plugin_bureaucracy_field[]  $fields  List of field objects
     * @param string                             $sep     Separator between fields for page id
     * @return array
     */
    function processFields($fields, $sep) {
        foreach ($fields as $field) {
            $label = $field->getParam('label');
            $value = $field->getParam('value');

            //field replacements
            $this->prepareFieldReplacements($label, $value);

            // handle pagenames
            $pname = $field->getParam('pagename');
            if (!is_null($pname)) {
                $this->pagename .= $sep . $pname;
            }


            if (!is_null($field->getParam('page_tpl')) && !is_null($field->getParam('page_tgt')) ) {
                $page_tpl = $this->replaceDefault($field->getParam('page_tpl'));

                $auth = $this->aclcheck($page_tpl);
                if ($auth >= AUTH_READ ) {
                    $this->templates[$field->getParam('page_tgt')] = $this->replace(array(), array(), rawWiki($page_tpl));
                }
            }
        }
    }

    /**
     * Prepare and resolve target page
     *
     * @throws Exception missing pagename
     */
    protected function buildTargetPageName() {
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
    protected function resolveTemplates() {
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
     * Load template(s) given via action field
     *
     * @param string $tpl    template name as given in form
     * @return string template
     */
    protected function getTemplates($tpl) {
        global $USERINFO;
        global $conf;
        $runas = $this->getConf('runas');

        if ($tpl == '_') {
            // use namespace template
            if (!isset($this->templates[$this->pagename])) {
                $this->templates[$this->pagename] = pageTemplate(array($this->pagename));
            }
        } elseif ($tpl !== '!') {
            $tpl = $this->replaceDefault($tpl);

            // Namespace link
            $backup = array();
            if ($runas) {
                // Hack user credentials.
                $backup = array($_SERVER['REMOTE_USER'], $USERINFO['grps']);
                $_SERVER['REMOTE_USER'] = $runas;
                $USERINFO['grps'] = array();
            }
            $template_pages = array();
            $opts = array(
                'depth' => 0,
                'listfiles' => true,
                'showhidden' => true
            );
            search($template_pages, $conf['datadir'], 'search_universal', $opts, str_replace(':', '/', getNS($tpl)));

            foreach ($template_pages as $template_page) {
                $templatepageid = cleanID($template_page['id']);
                $newpageid = preg_replace('/^' . preg_quote_cb(cleanID($tpl)) . '($|:)/', $this->pagename . '$1', $templatepageid);
                if ($newpageid === $templatepageid) {
                    // When using a single-page template, ignore other pages
                    // in the same namespace.
                    continue;
                }

                if (!isset($this->templates[$newpageid])) {
                    // load page data and do default pattern replacements like
                    // namespace templates do
                    $data = array(
                        'id' => $newpageid,
                        'tpl' => rawWiki($templatepageid),
                        'doreplace' => true,
                    );
                    parsePageTemplate($data);
                    $this->templates[$newpageid] = $this->replace(
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
     * Checks for existance and access of target pages
     *
     * @return mixed
     * @throws Exception
     */
    protected function checkTargetPageNames() {
        foreach (array_keys($this->templates) as $pname) {
            // prevent overriding already existing pages
            if (page_exists($pname)) {
                throw new Exception(sprintf($this->getLang('e_pageexists'), html_wikilink($pname)));
            }

            $auth = $this->aclcheck($pname);
            if ($auth < AUTH_CREATE) {
                throw new Exception($this->getLang('e_denied'));
            }
        }
    }

    /**
     * Perform replacements on the collected templates, and save the pages.
     */
    protected function replaceAndSavePages() {
        global $ID;
        foreach ($this->templates as $pageName => $template) {
            // set NSBASE var to make certain dataplugin constructs easier
            $this->patterns['__nsbase__'] = '/@NSBASE@/';
            $this->values['__nsbase__'] = noNS(getNS($pageName));

            // save page
            saveWikiText(
                $pageName,
                cleanText($this->replaceDefault($template, false)),
                sprintf($this->getLang('summary'), $ID)
            );
        }
    }

    /**
     * (Callback) Sorts first by namespace depth, next by page ids
     *
     * @param string $a
     * @param string $b
     * @return int positive if $b is in deeper namespace than $a, negative higher.
     *             further sorted by pageids
     *
     *  return an integer less than, equal to, or
     * greater than zero if the first argument is considered to be
     * respectively less than, equal to, or greater than the second.
     */
    public function _sort($a, $b) {
        $ns_diff = substr_count($a, ':') - substr_count($b, ':');
        return ($ns_diff === 0) ? strcmp($a, $b) : ($ns_diff > 0 ? -1 : 1);
    }

    /**
     * (Callback) Build content of item
     *
     * @param array $item
     * @return string
     */
    public function html_list_index($item){
        $ret = '';
        if($item['type']=='f'){
            $ret .= html_wikilink(':'.$item['id']);
        } else {
            $ret .= '<strong>' . trim(substr($item['id'], strrpos($item['id'], ':', -2)), ':') . '</strong>';
        }
        return $ret;
    }

    /**
     * Build thanks message, trigger indexing and rendering of new pages.
     *
     * @param string $thanks
     * @return string html of thanks message or when redirect the first page id of created pages
     */
    protected function buildThankYouPage($thanks) {
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
    
    /**
     * move the files to <pagename>:FILENAME 
     *
     *
     * @param syntax_plugin_bureaucracy_field[] $fields
     * @throws Exception
     */
    protected function processUploads($fields) {
        $ns = $this->pagename;
        foreach($fields as $field) {

            if($field->getFieldType() !== 'file') continue;

            $label = $field->getParam('label');
            $file  = $field->getParam('file');

            //skip empty files
            if(!$file['size']) {
                $this->values[$label] = '';
                continue;
            }

            $id = $ns.':'.$file['name'];
            $id = cleanID($id);

            $auth = $this->aclcheck($id);

            $res = media_save(
                array('name' => $file['tmp_name']),
                $id,
                false,
                $auth,
                'copy_uploaded_file');

            if(is_array($res)) throw new Exception($res[0]);

            $this->values[$label] = $res;

        }
    }

    /**
     * Returns ACL access level of the user or the (virtual) 'runas' user
     *
     * @param string $id
     * @return int
     */
    protected function aclcheck($id) {
        $runas = $this->getConf('runas');

        if($runas) {
            $auth = auth_aclcheck($id, $runas, array());
        } else {
            $auth = auth_quickaclcheck($id);
        }
        return $auth;

    }

}
// vim:ts=4:sw=4:et:enc=utf-8:
