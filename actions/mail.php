<?php
/**
 * Action sendemail for DokuWiki plugin bureaucracy
 */

class syntax_plugin_bureaucracy_action_mail extends syntax_plugin_bureaucracy_action {

    /**
     * Build a nice email from the submitted data and send it
     */
    function run($data, $thanks, $argv, &$errors) {
        global $ID;
        global $conf;

        // get recipient address(es)
        $to = join(',',$argv);

        $sub = sprintf($this->getLang('mailsubject'),$ID);
        $txt = sprintf($this->getLang('mailintro')."\n\n\n",strftime($conf['dformat']));

        foreach($data as $opt){
            $value = $opt->getParam('value');
            $label = $opt->getParam('label');

            switch($opt->getFieldType()){
                case 'fieldset':
                    $txt .= "\n====== ".hsc($label)." ======\n\n";
                    break;
                default:
                    if($value === null || $label === null) break;
                    $txt .= $label."\n";
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
