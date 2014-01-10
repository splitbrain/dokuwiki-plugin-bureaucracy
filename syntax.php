<?php
/**
 * Bureaucracy Plugin: Allows flexible creation of forms
 *
 * This plugin allows definition of forms in wiki pages. The forms can be
 * submitted via email or used to create new pages from templates.
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <andi@splitbrain.org>
 * @author     Adrian Lang <dokuwiki@cosmocode.de>
 */
// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_bureaucracy extends DokuWiki_Syntax_Plugin {
    // allowed types and the number of arguments
    private $form_id = 0;

    /**
     * What kind of syntax are we?
     */
    public function getType(){
        return 'substition';
    }

    /**
     * What about paragraphs?
     */
    public function getPType(){
        return 'block';
    }

    /**
     * Where to sort in?
     */
    public function getSort(){
        return 155;
    }


    /**
     * Connect pattern to lexer
     */
    public function connectTo($mode) {
        $this->Lexer->addSpecialPattern('<form>.*?</form>',$mode,'plugin_bureaucracy');
    }


    /**
     * Handle the match
     */
    public function handle($match, $state, $pos, Doku_Handler &$handler){
        $match = substr($match,6,-7); // remove form wrap
        $lines = explode("\n",$match);
        $actions = array();
        $thanks = '';
        $labels = '';

        // parse the lines into an command/argument array
        $cmds = array();
        while (count($lines) > 0) {
            $line = trim(array_shift($lines));
            if (!$line) continue;
            $args = $this->_parse_line($line, $lines);
            $args[0] = $this->_sanitizeClassName($args[0]);

            if (in_array($args[0], array('action', 'thanks', 'labels'))) {
                if (count($args) < 2) {
                    msg(sprintf($this->getLang('e_missingargs'),hsc($args[0]),hsc($args[1])),-1);
                    continue;
                }

                // is action element?
                if ($args[0] == 'action') {
                    array_shift($args);
                    $actions[] = array('type' => array_shift($args), 'argv' => $args);
                    continue;
                }

                // is thank you text?
                if ($args[0] == 'thanks') {
                    $thanks = $args[1];
                    continue;
                }

                // is labels?
                if ($args[0] == 'labels') {
                    $labels = $args[1];
                    continue;
                }
            }

            $class = 'syntax_plugin_bureaucracy_field_' . $args[0];
            $cmds[] = new $class($args);
        }

        foreach($actions as $action) {
            // check if action is available
            $action['type'] = $this->_sanitizeClassName($action['type']);
            if (!$action['type'] ||
                !@file_exists(DOKU_PLUGIN.'bureaucracy/actions/' .
                              $action['type'] . '.php')) {
                msg(sprintf($this->getLang('e_noaction'), $action), -1);
            }
        }
        // set thank you message
        if (!$thanks) {
            $thanks = "";
            foreach($actions as $action) {
                $thanks .= $this->getLang($action['type'].'_thanks');
            }
        } else {
            $thanks = hsc($thanks);
        }
        return array('fields'=>$cmds,'actions'=>$actions,'thanks'=>$thanks,'labels'=>$labels);
    }

    /**
     * Create output
     */
    public function render($format, Doku_Renderer &$R, $data) {
        if ($format != 'xhtml') return false;
        $R->info['cache'] = false; // don't cache

        /**
         * replace some time and name placeholders in the default values
         * @var $opt syntax_plugin_bureaucracy_field */
        foreach ($data['fields'] as &$opt) {
            if(isset($opt->opt['value'])) {
                $opt->opt['value'] = $this->replaceNSTemplatePlaceholders($opt->opt['value']);
            }

        }

        if($data['labels']) $this->loadlabels($data);

        $this->form_id++;
        if (isset($_POST['bureaucracy']) && checkSecurityToken() &&
            $_POST['bureaucracy']['$$id'] == $this->form_id) {
            $success = $this->_handlepost($data);
            if ($success !== false) {
                $R->doc .= '<div class="bureaucracy__plugin" id="scroll__here">'
                        .  $success . '</div>';
                return true;
            }
        }

        $R->doc .= $this->_htmlform($data['fields']);

        return true;
    }

    /**
     * Initializes the labels, loaded from a defined labelpage
     *
     * @param array $data all data passed to render()
     */
    protected function loadlabels(&$data){
        global $INFO;
        $labelpage = $data['labels'];
        $exists = false;
        resolve_pageid($INFO['namespace'], $labelpage, $exists);
        if(!$exists){
            msg(sprintf($this->getLang('e_labelpage'), html_wikilink($labelpage)),-1);
            return;
        }

        // parse simple list (first level cdata only)
        $labels = array();
        $instructions = p_cached_instructions(wikiFN($labelpage));
        $inli = 0;
        $item = '';
        foreach($instructions as $instruction){
            if($instruction[0] == 'listitem_open'){
                $inli++;
                continue;
            }
            if($inli === 1 && $instruction[0] == 'cdata'){
                $item .= $instruction[1][0];
            }
            if($instruction[0] == 'listitem_close'){
                $inli--;
                if($inli === 0 ){
                    list($k, $v) = explode('=', $item, 2);
                    $k = trim($k);
                    $v = trim($v);
                    if($k && $v) $labels[$k] = $v;
                    $item = '';
                }
            }
        }

        // apply labels to all fields
        $len = count($data['fields']);
        for($i = 0; $i < $len; $i++) {
            if(isset($data['fields'][$i]->depends_on)) {
                // translate dependency on fieldsets
                $label = $data['fields'][$i]->depends_on[0];
                if(isset($labels[$label])) {
                    $data['fields'][$i]->depends_on[0] = $labels[$label];
                }

            } else if(isset($data['fields'][$i]->opt['label'])) {
                // translate field labels
                $label = $data['fields'][$i]->opt['label'];
                if(isset($labels[$label])) {
                    $data['fields'][$i]->opt['display'] = $labels[$label];
                }
            }
        }

        if (isset($data['thanks'])) {
            if (isset($labels[$data['thanks']])) {
                $data['thanks'] = $labels[$data['thanks']];
            }
        }

    }

    /**
     * Validate posted data, perform action(s)
     *
     * @param array $data all data passed to render()
     * @return bool|string
     *      returns thanks message when fields validated and performed the action(s) succesfully;
     *      otherwise returns false.
     */
    private function _handlepost($data) {
        $success = true;
        foreach ($data['fields'] as $id => $opt) {
            /** @var $opt syntax_plugin_bureaucracy_field */
            $_ret = true;
            if ($opt->getFieldType() === 'fieldset') {
                $params = array($_POST['bureaucracy'][$id], $id, &$data['fields']);
                $_ret = $opt->handle_post($params);
            } elseif(!$opt->hidden) {
                $_ret = $opt->handle_post($_POST['bureaucracy'][$id]);
            }
            if (!$_ret) {
                // Do not return instantly to allow validation of all fields.
                $success = false;
            }
        }
        if (!$success) {
            return false;
        }

        $thanks = '';
        foreach($data['actions'] as $actionData) {
            /** @var syntax_plugin_bureaucracy_action $action */
            $class = 'syntax_plugin_bureaucracy_action_' . $actionData['type'];
            $action = new $class();

            try {
                $thanks .= $action->run($data['fields'], $data['thanks'],
                                        $actionData['argv']);
            } catch (Exception $e) {
                msg($e->getMessage(), -1);
                return false;
            }
        }

        // Perform after_action hooks
        foreach($data['fields'] as $field) {
            /** @var $field syntax_plugin_bureaucracy_field */
            $field->after_action();
        }
        return $thanks;
    }

    /**
     * Create the form
     *
     * @param array $fields array with form fields
     * @return string html of the form
     */
    private function _htmlform($fields){
        global $ID;

        $form = new Doku_Form(array('class' => 'bureaucracy__plugin',
                                    'id'    => 'bureaucracy__plugin' . $this->form_id));
        $form->addHidden('id', $ID);
        $form->addHidden('bureaucracy[$$id]', $this->form_id);

        foreach ($fields as $id => $opt) {
            /** @var $opt syntax_plugin_bureaucracy_field */
            $opt->renderfield(array('name' => 'bureaucracy['.$id.']'), $form);
        }

        return $form->getForm();
    }

    /**
     * Parse a line into (quoted) arguments
     * Splits line at spaces, except when quoted
     *
     * @author William Fletcher <wfletcher@applestone.co.za>
     *
     * @param string $line line to parse
     * @param array  $lines all remaining lines
     * @return array with all the arguments
     */
    private function _parse_line($line, &$lines) {
        $args = array();
        $inQuote = false;
        $arg = '';
        do {
            $len = strlen($line);
            for ( $i = 0 ; $i < $len; $i++ ) {
                if ( $line{$i} == '"' ) {
                    if ($inQuote) {
                        array_push($args, $arg);
                        $inQuote = false;
                        $arg = '';
                        continue;
                    } else {
                        $inQuote = true;
                        continue;
                    }
                } else if ( $line{$i} == ' ' ) {
                    if ($inQuote) {
                        $arg .= ' ';
                        continue;
                    } else {
                        if ( strlen($arg) < 1 ) continue;
                        array_push($args, $arg);
                        $arg = '';
                        continue;
                    }
                }
                $arg .= $line{$i};
            }
            if (!$inQuote || count($lines) === 0) break;
            $line = array_shift($lines);
            $arg .= "\n";
        } while (true);
        if ( strlen($arg) > 0 ) array_push($args, $arg);
        return $args;
    }

    /**
     * Clean class name
     *
     * @param string $classname
     * @return string cleaned name
     */
    private function _sanitizeClassName($classname) {
        return preg_replace('/[^\w\x7f-\xff]/', '', strtolower($classname));
    }

    /**
     * Replace some placeholders (default available for namespace templates) for userinfo and time
     *   - more replacements are done in syntax_plugin_bureaucracy_action_template
     * @param $input
     * @return mixed
     */
    protected function replaceNSTemplatePlaceholders($input) {
        global $USERINFO;
        global $conf;

        // replace placeholders
        return str_replace(array(
                                '@USER@',
                                '@NAME@',
                                '@MAIL@',
                                '@DATE@',
                                '@YEAR@',
                                '@MONTH@',
                                '@DAY@',
                                '@TIME@'
                           ),
                           array(
                                $_SERVER['REMOTE_USER'],
                                $USERINFO['name'],
                                $USERINFO['mail'],
                                strftime($conf['dformat']),
                                date('Y'),
                                date('m'),
                                date('d'),
                                date('H:i')
                           ), $input);
    }
}
