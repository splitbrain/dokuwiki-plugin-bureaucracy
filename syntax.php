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

function syntax_plugin_bureaucracy_autoload($name) {
    if (!preg_match('/^syntax_plugin_bureaucracy_(field|action)(?:_(\w+))?$/', $name, $matches)) {
        return false;
    }

    if (!isset($matches[2])) {
        // Autoloading the field / action base class
        $matches[2] = $matches[1];
    }

    $filename = DOKU_PLUGIN . "bureaucracy/{$matches[1]}s/{$matches[2]}.php";
    if (!@file_exists($filename)) {
        $plg = new syntax_plugin_bureaucracy;
        msg(sprintf($plg->getLang($matches[1] === 'field' ? 'e_unknowntype' : 'e_noaction'),
                    hsc($matches[2])), -1);
        eval("class $name extends syntax_plugin_bureaucracy_{$matches[1]} { };");
        return true;
    }
    include $filename;
    return true;
}

spl_autoload_register('syntax_plugin_bureaucracy_autoload');

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_bureaucracy extends DokuWiki_Syntax_Plugin {
    // allowed types and the number of arguments
    var $form_id = 0;

    /**
     * What kind of syntax are we?
     */
    function getType(){
        return 'substition';
    }

    /**
     * What about paragraphs?
     */
    function getPType(){
        return 'block';
    }

    /**
     * Where to sort in?
     */
    function getSort(){
        return 155;
    }


    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('<form>.*?</form>',$mode,'plugin_bureaucracy');
    }


    /**
     * Handle the match
     */
    function handle($match, $state, $pos, &$handler){
        $match = substr($match,6,-7); // remove form wrap
        $lines = explode("\n",$match);
        $action = array('type' => '',
                        'argv' => array());
        $thanks = '';

        // parse the lines into an command/argument array
        $cmds = array();
        foreach ($lines as $line) {
            $line = trim($line);
            if (!$line) continue;

            $args = $this->_parse_line($line);
            $args[0] = strtolower($args[0]);

            if (in_array($args[0], array('action', 'thanks'))) {
                if (count($args) < 2) {
                    msg(sprintf($this->getLang('e_missingargs'),hsc($args[0]),hsc($args[1])),-1);
                    continue;
                }

                // is action element?
                if ($args[0] == 'action') {
                    array_shift($args);
                    $action['type'] = array_shift($args);
                    $action['argv'] = $args;
                    continue;
                }

                // is thank you text?
                if ($args[0] == 'thanks') {
                    $thanks = $args[1];
                    continue;
                }
            }

            $class = 'syntax_plugin_bureaucracy_field_' . $args[0];
            $cmds[] = new $class($this, $args);
        }

        // check if action is available
        $action['type'] = preg_replace('/[^a-z]+/', '', $action['type']);
        if (!$action['type'] ||
            !@file_exists(DOKU_PLUGIN.'bureaucracy/actions/' .
                          $action['type'] . '.php')) {
            msg(sprintf($this->getLang('e_noaction'), $action), -1);
        }
        // set thank you message
        if (!$thanks) {
            $thanks = $this->getLang($action['type'].'_thanks');
        } else {
            $thanks = hsc($thanks);
        }
        return array('data'=>$cmds,'action'=>$action,'thanks'=>$thanks);
    }

    /**
     * Create output
     */
    function render($format, &$R, $data) {
        global $ID;
        if ($format != 'xhtml') return false;
        $R->info['cache'] = false; // don't cache

        $this->form_id++;
        if (isset($_POST['bureaucracy']) && checkSecurityToken() &&
            $_POST['bureaucracy_id'] == $this->form_id) {
            $success = $this->_handlepost($data);
            if ($success !== false) {
                $R->doc .= '<div class="bureaucracy__plugin" id="scroll__here">'
                        .  $success . '</div>';
                return true;
            }
        }
        $R->doc .= $this->_htmlform($data['data']);

        return true;
    }

    /**
     * Validate data, perform action
     */
    function _handlepost($data) {
        $success = true;
        foreach ($data['data'] as $id => $opt) {
            if ($opt->getFieldType() === 'fieldset') {
                $_ret = $opt->handle_post($_POST['bureaucracy'][$id], $id, $data['data']);
            } else {
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

        $class = 'syntax_plugin_bureaucracy_action_' . $data['action']['type'];
        $action = new $class();

        try {
            $success = $action->run($data['data'], $data['thanks'],
                                    $data['action']['argv']);
        } catch (Exception $e) {
            msg($e->getMessage());
            return false;
        }
        return $success;
    }

    /**
     * Create the form
     */
    function _htmlform($data){
        global $ID;

        $form = new Doku_Form(array('class' => 'bureaucracy__plugin',
                                    'id'    => 'bureaucracy__plugin' . $this->form_id));
        $form->addHidden('id', $ID);
        $form->addHidden('bureaucracy_id', $this->form_id);

        foreach ($data as $id => $opt) {
            $opt->render(array('name' => 'bureaucracy['.$id.']'), $form);
        }

        ob_start();
        $form->printForm();
        $out = ob_get_contents();
        ob_end_clean();
        return $out;
    }

    /**
     * Parse a line into (quoted) arguments
     *
     * @author William Fletcher <wfletcher@applestone.co.za>
     */
    function _parse_line($line) {
        $args = array();
        $inQuote = false;
        $len = strlen($line);
        $arg = '';
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
        if ( strlen($arg) > 0 ) array_push($args, $arg);
        return $args;
    }
}

//Setup VIM: ex: et ts=4 enc=utf-8 :
