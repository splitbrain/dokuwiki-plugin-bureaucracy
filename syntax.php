<?php
/**
 * Bureaucracy Plugin: Creates forms and submits them via email
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <andi@splitbrain.org>
 */
// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

require_once DOKU_PLUGIN . 'bureaucracy/fields/field.php';

function syntax_plugin_bureaucracy_autoload($name) {
    if (strpos($name, 'syntax_plugin_bureaucracy_field_') !== 0) {
        return false;
    }

    $subclass = substr($name, 32);
    if (!@file_exists(DOKU_PLUGIN . 'bureaucracy/fields/' . $subclass . '.php')) {
        $plg = new syntax_plugin_bureaucracy;
        msg(sprintf($plg->getLang('e_unknowntype'),hsc($subclass)),-1);
        return false;
    }
    require_once DOKU_PLUGIN . 'bureaucracy/fields/' . $subclass . '.php';
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
        $errors = array();
        if (isset($_POST['bureaucracy']) && $_POST['bureaucracy_id'] == $this->form_id) {
            list($errors, $hiddens) = $this->_checkpost($data['data']);

            if (count($errors) === 0 && $data['action']) {
                require_once DOKU_PLUGIN . 'bureaucracy/actions/actions.php';
                require_once DOKU_PLUGIN . 'bureaucracy/actions/' . $data['action']['type'] . '.php';
                $class = 'syntax_plugin_bureaucracy_action_' . $data['action']['type'];
                $action = new $class();

                $out_data = array();
                foreach ($data['data'] as $id => $dat) {
                    if (!isset($hiddens[$id])) $out_data[$id] = $dat;
                }

                $success = $action->run($out_data, $data['thanks'],
                                        $data['action']['argv'], $errors);
                if ($success) {
                    $R->doc .= '<div class="bureaucracy__plugin" id="scroll__here">';
                    $R->doc .= $success;
                    $R->doc .= '</div>';
                    return true;
                }

            }
        }
        $R->doc .= $this->_htmlform($data['data'],$errors);

        return true;
    }

    /**
     * Validate any posted data, display errors using the msg() function,
     * put a list of bad fields in the return array
     */
    function _checkpost($data){
        $errors = array();
        $hiddens = array();
        foreach ($data as $id => $opt) {
            if ($id > 0 && isset($hiddens[$id - 1]) &&
                $opt->getFieldType() !== 'fieldset') {
                $hiddens[$id] = 1;
                continue;
            }
            $_res = $opt->handle_post($_POST['bureaucracy'][$id]);
            if ($_res === false) {
                $errors[$id] = 1;
                continue;
            } elseif ($_res !== true) {
                for ($n = 0 ; $n < $id; ++$n) {
                    if ($data[$n]->getParam('label') != $_res[0]) {
                        continue;
                    }
                    $hidden = $data[$n]->getParam('value') != $_res[1];
                    if ($hidden) {
                        $hiddens[$id] = 1;
                    }
                    break;
                }
            }
        }
        return array($errors, $hiddens);
    }

    /**
     * Create the form
     */
    function _htmlform($data,$errors){
        global $ID;

        $form = new Doku_Form('bureaucracy__plugin');
        $form->addHidden('id', $ID);
        $form->addHidden('bureaucracy_id', $this->form_id);

        foreach ($data as $id => $opt) {
            $params = array('name' => 'bureaucracy['.$id.']');
            if (isset($errors[$id])) {
                $params['class'] = 'bureaucracy_error';
            }
            $opt->render($params, $form);
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
