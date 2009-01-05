<?php
/**
 * Action sendemail for DokuWiki plugin bureaucracy
 */

class syntax_plugin_bureaucracy_action_mail extends syntax_plugin_bureaucracy_actions {

    /**
     * Build a nice email from the submitted data and send it
     */
    function run($data, $thanks, $argv, &$errors) {
        global $ID;
        global $conf;

        // get receipient address(es)
        $to = join(',',$argv);

        $sub = sprintf($this->getLang('mailsubject'),$ID);
        $txt = sprintf($this->getLang('mailintro')."\n\n\n",strftime($conf['dformat']));

        foreach($data as $opt){
            $value = $_POST['bureaucracy'][$opt['idx']];

            switch($opt['cmd']){
                case 'fieldset':
                    $txt .= "\n====== ".hsc($opt['label'])." ======\n\n";
                    break;
                default:
                    if(in_array($opt['cmd'],$this->nofield)) break;
                    $txt .= $opt['label']."\n";
                    $txt .= "\t\t$value\n";
            }
        }

        if(!mail_send($to, $sub, $txt, $conf['mailfrom'])) {
            msg($this->getLang('e_mail'), -1);
            return false;
        }
        return $thanks;
    }

}
// vim:ts=4:sw=4:et:enc=utf-8:
