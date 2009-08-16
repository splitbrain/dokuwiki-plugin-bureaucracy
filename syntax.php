<?php
/**
 * Bureaucracy Plugin: Creates forms and submits them via email
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <andi@splitbrain.org>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_bureaucracy extends DokuWiki_Syntax_Plugin {
    // allowed types and the number of arguments
    var $form_id = 0;
    var $argcheck = array(
                        'textbox'    => 2,
                        'email'      => 2,
                        'password'   => 2,
                        'number'     => 2,
                        'submit'     => 1,
                        'fieldset'   => 1,
                        'select'     => 3,
                        'onoff'      => 2,
                        'yesno'      => 2,
                        'static'     => 2,
                        'textarea'   => 2,
                        'action'     => 2,
                        'thanks'     => 2,
                        );
    // types that are no fields
    var $nofield = array('action','static','fieldset','submit','thanks');

    /**
     * return some info
     */
    function getInfo(){
        return array(
            'author' => 'Andreas Gohr',
            'email'  => 'andi@splitbrain.org',
            'date'   => '2009-08-16',
            'name'   => 'Bureaucracy Plugin',
            'desc'   => 'A simple form generator/emailer',
            'url'    => 'http://dokuwiki.org/plugin:bureaucracy',
        );
    }

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
        $action = array('type'=>'','argv'=>array());
        $thanks = '';

        $idx = 0;
        // parse the lines into an command/argument array
        $cmds = array();
        foreach($lines as $line){
            $line = trim($line);
            if(!$line) continue;
            $args = $this->_parse_line($line);
            $args[0] = strtolower($args[0]);
            if(!isset($this->argcheck[$args[0]])){
                msg(sprintf($this->getLang('e_unknowntype'),hsc($args[0])),-1);
                continue;
            }
            if(count($args) < $this->argcheck[$args[0]]){
                msg(sprintf($this->getLang('e_missingargs'),hsc($args[0]),hsc($args[1])),-1);
                continue;
            }

            // is action element?
            if($args[0] == 'action'){
                array_shift($args);
                $action['type'] = array_shift($args);
                $action['argv'] = $args;
                continue;
            }

            // is thank you text?
            if($args[0] == 'thanks'){
                $thanks = $args[1];
                continue;
            }

            // get standard arguments
            $opt = array();
            $opt['cmd']   = array_shift($args);
            $opt['label'] = array_shift($args);
            $opt['idx']   = $idx++;

            // save addtional minimum args here
            $keep = $this->argcheck[$opt['cmd']]-2;
            if($keep > 0){
                $opt['args'] = array_slice($args,0,$keep);
            }

            // parse additional arguments
            foreach($args as $arg){
                if($arg[0] == '='){
                    $opt['default'] = substr($arg,1);
                }elseif($arg[0] == '>'){
                    $opt['min'] = substr($arg,1);
                    if(!is_numeric($opt['min'])) unset($opt['min']);
                }elseif($arg[0] == '<'){
                    $opt['max'] = substr($arg,1);
                    if(!is_numeric($opt['max'])) unset($opt['max']);
                }elseif($arg[0] == '/' && substr($arg,-1) == '/'){
                    $opt['re'] = substr($arg,1,-1);
                }elseif($arg == '!'){
                    $opt['optional'] = true;
                }elseif($arg == '@'){
                    $opt['pagename'] = true;
                }elseif(preg_match('/x\d/', $arg)) {
                    $opt['rows'] = substr($arg,1);
                }
            }

            $cmds[] = $opt;
        }

        // check if action is available
        $action['type'] = preg_replace('/[^a-z]+/','',$action['type']);
        if(!$action['type'] or !@file_exists(DOKU_PLUGIN.'bureaucracy/actions/' . $action['type'] . '.php')) {
            msg(sprintf($this->getLang('e_noaction'), $action),-1);
        }
        // set thank you message
        if(!$thanks){
            $thanks = $this->getLang($action['type'].'_thanks');
        }else{
            $thanks = hsc($thanks);
        }

        return array('data'=>$cmds,'action'=>$action,'thanks'=>$thanks);
    }

    /**
     * Create output
     */
    function render($format, &$R, $data) {
        global $ID;
        if($format != 'xhtml') return false;
        $R->info['cache'] = false; // don't cache

        $this->form_id++;
        $errors = array();
        if(isset($_POST['bureaucracy']) && $_POST['bureaucracy_id'] == $this->form_id){
            $errors = $this->_checkpost($data['data']);
            // check CAPTCHA
            $ok = true;
            $helper = null;
            if(@is_dir(DOKU_PLUGIN.'captcha')) $helper = plugin_load('helper','captcha');
            if(!is_null($helper) && $helper->isEnabled()){
                $ok = $helper->check();
            }

            if($ok && !count($errors) && $data['action']){
                require_once(DOKU_PLUGIN . 'bureaucracy/actions/actions.php');
                require_once(DOKU_PLUGIN . 'bureaucracy/actions/' . $data['action']['type'] . '.php');
                $class = 'syntax_plugin_bureaucracy_action_' . $data['action']['type'];
                $action = new $class();

                $success = $action->run($data['data'], $data['thanks'], $data['action']['argv'], $errors);
                if($success) {
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

        foreach($data as $opt){
            // required
            if(trim($_POST['bureaucracy'][$opt['idx']]) === ''){
                if($opt['optional']) continue;
                if(in_array($opt['cmd'],$this->nofield)) continue;
                $errors[$opt['idx']] = 1;
                msg(sprintf($this->getLang('e_required'),hsc($opt['label'])),-1);
                continue;
            }

            $value = $_POST['bureaucracy'][$opt['idx']];

            // regexp
            if($opt['re'] && !@preg_match('/'.$opt['re'].'/i',$value)){
                $errors[$opt['idx']] = 1;
                msg(sprintf($this->getLang('e_match'),hsc($opt['label']),hsc($opt['re'])),-1);
                continue;
            }

            // email
            if($opt['cmd'] == 'email' && !mail_isvalid($value)){
                $errors[$opt['idx']] = 1;
                msg(sprintf($this->getLang('e_email'),hsc($opt['label'])),-1);
                continue;
            }

            // numbers
            if($opt['cmd'] == 'number' && !is_numeric($value)){
                $errors[$opt['idx']] = 1;
                msg(sprintf($this->getLang('e_numeric'),hsc($opt['label'])),-1);
                continue;
            }

            // min
            if(isset($opt['min']) && $value < $opt['min']){
                $errors[$opt['idx']] = 1;
                msg(sprintf($this->getLang('e_min'),hsc($opt['label']),hsc($opt['min'])),-1);
                continue;
            }

            // max
            if(isset($opt['max']) && $value > $opt['max']){
                $errors[$opt['idx']] = 1;
                msg(sprintf($this->getLang('e_max'),hsc($opt['label']),hsc($opt['max'])),-1);
                continue;
            }
        }

        return $errors;
    }

    /**
     * Create the form
     */
    function _htmlform($data,$errors){
        global $ID;

        $form = new Doku_Form('bureaucracy__plugin');
        $form->addHidden('id',$ID);
        $form->addHidden('bureaucracy_id',$this->form_id);

        $captcha = false; // to make sure we add it only once

        foreach($data as $opt){
            if(isset($_POST['bureaucracy'][$opt['idx']]) && ($_POST['bureaucracy_id'] == $this->form_id)){
                $value = $_POST['bureaucracy'][$opt['idx']];
            }else{
                $value = $opt['default'];
            }
            $name  = 'bureaucracy['.$opt['idx'].']';

            if($errors[$opt['idx']]){
                $class = 'bureaucracy_error';
            }else{
                $class = '';
            }

            // we always start with a fieldset!
            if(!$form->_infieldset && $opt['cmd'] != 'fieldset'){
                $form->startFieldset('');
            }

            // handle different field types
            switch($opt['cmd']){
                case 'fieldset':
                    $form->startFieldset($opt['label']);
                    break;
                case 'submit':
                    //add captcha if available
                    if(!$captcha){
                        $captcha = true;
                        $helper = null;
                        if(@is_dir(DOKU_PLUGIN.'captcha')) $helper = plugin_load('helper','captcha');
                        if(!is_null($helper) && $helper->isEnabled()){
                            $form->addElement($helper->getHTML());
                        }
                    }

                    $form->addElement(form_makeButton('submit','', $opt['label']));
                    break;
                case 'password':
                    $form->addElement(form_makePasswordField($name,$opt['label'],'',$class));
                    break;
                case 'textbox':
                case 'number':
                case 'email':
                    $form->addElement(form_makeTextField($name,$value,$opt['label'],'',$class));
                    break;
                case 'onoff':
                case 'yesno':
                    $chk = ($value) ? 'checked="checked"' : '';
                    $form->addElement('<label class="'.$class.'"><span>'.hsc($opt['label']).'</span>'.
                                      '<input type="checkbox" name="'.$name.'" value="Yes" '.$chk.' /></label>');
                    break;
                case 'select':
                    $vals = explode('|',$opt['args'][0]);
                    $vals = array_map('trim',$vals);
                    $vals = array_filter($vals);
                    if (!$value && $opt['optional']) array_unshift($vals,' ');
                    $form->addElement(form_makeListboxField($name,$vals,$value,$opt['label'],'',$class));
                    break;
                case 'static':
                    $form->addElement('<p>'.hsc($opt['label']).'</p>');
                    break;
                case 'textarea':
                    $rows = ($opt['rows']) ? $opt['rows'] : 10;
                    $form->addElement('<label class="'.$class.'"><span>'.hsc($opt['label']).'</span>'.
                                      '<textarea name="'.$name.'" rows="' . $rows . '" cols="10" class="edit">'.$value.'</textarea></label>');
                    break;
            }
        }

        ob_start();
        $form->printForm();
        $out .= ob_get_contents();
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
        for(  $i = 0 ; $i <= $len; $i++ ) {
            if( $line{$i} == '"' ) {
                if($inQuote) {
                    array_push($args, $arg);
                    $inQuote = false;
                    $arg = '';
                    continue;
                } else {
                    $inQuote = true;
                    continue;
                }
            } else if ( $line{$i} == ' ' ) {
                if($inQuote) {
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
