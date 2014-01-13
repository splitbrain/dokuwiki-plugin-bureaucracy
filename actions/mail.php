<?php
/**
 * Action sendemail for DokuWiki plugin bureaucracy
 */

class syntax_plugin_bureaucracy_action_mail extends syntax_plugin_bureaucracy_action {

    /**
     * Build a nice email from the submitted data and send it
     */
    function run($fields, $thanks, $argv) {
        global $ID;

        $this->prepareLanguagePlaceholder();

        // get recipient address(es)
        $to = join(',',$argv);
        $replyto = array();
        $headers = null;

        $subject = sprintf($this->getLang('mailsubject'),$ID);
        $txt = sprintf($this->getLang('mailintro')."\n\n\n", dformat());

        foreach($fields as $opt){
            /** @var syntax_plugin_bureaucracy_field $opt */
            $value = $opt->getParam('value');
            $label = $opt->getParam('label');

            switch($opt->getFieldType()){
                case 'fieldset':
                    $txt .= "\n====== ".hsc($label)." ======\n\n";
                    break;
                case 'subject':
                    $subject = $label;
                    break;
                case 'email':
                    if(!is_null($opt->getParam('replyto'))) {
                        $replyto[] = $value;
                    }
                    /** fall through */
                default:
                    if($value === null || $label === null) break;
                    $txt .= $label."\n";
                    $txt .= "\t\t$value\n";
            }

            $this->prepareFieldReplacements($label, $value);
        }

        $subject = $this->replaceDefault($subject);

        if(!empty($replyto)) {
            $headers = mail_encode_address(join(',',$replyto), 'Reply-To');
        }

        global $conf;
        if(!mail_send($to, $subject, $txt, $conf['mailfrom'], '', '', $headers)) {
            throw new Exception($this->getLang('e_mail'));
        }
        return $thanks;
    }

}
// vim:ts=4:sw=4:et:enc=utf-8:
