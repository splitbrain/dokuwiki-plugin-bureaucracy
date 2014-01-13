<?php
/**
 * Action sendemail for DokuWiki plugin bureaucracy
 */

class syntax_plugin_bureaucracy_action_mail extends syntax_plugin_bureaucracy_action {

    protected $_mail_html = '';
    protected $_mail_text = '';

    /**
     * Build a nice email from the submitted data and send it
     */
    function run($data, $thanks, $argv) {
        global $ID;
        global $conf;

        $mail = new Mailer();
        // get recipient address(es)
        $to = join(',',$argv);

        $sub = sprintf($this->getLang('mailsubject'),$ID);

        $this->_mail_text .=  sprintf($this->getLang('mailintro')."\n\n", dformat());
        $this->_mail_html .=  sprintf($this->getLang('mailintro')."<br><br>", dformat());

        $mail->to($to);
        $mail->subject($sub);

        $this->_mail_html .= '<table>';
        foreach($data as $opt){
            $value = $opt->getParam('value');
            $label = $opt->getParam('label');
            
            switch($opt->getFieldType()){
                case 'fieldset':
                    $this->mail_addRow($label);
                    break;
                case 'file':
                    if(!$value['size']) {
                        $this->mail_addRow($label,$this->getLang('attachmentMailEmpty'));
                    } else if($value['size'] > $this->getConf('maxEmailAttachmentSize')) {
                        $this->mail_addRow($label,$value['name'] .' '. $this->getLang('attachmentMailToLarge'));
                    } else {
                        $this->mail_addRow($label,$value['name']);
                        $mail->attachFile($value['tmp_name'],$value['type'],$value['name']);
                    }
                    break;
                default:
                    if($value === null || $label === null) break;
                    $this->mail_addRow($label,$value);
            }
            
        }
        $this->_mail_html .= '</table>';

        $mail->setBody($this->_mail_text,null,null,$this->_mail_html);
        $mail->from($conf['mailfrom']);

        if(!$mail->send()) {
            throw new Exception($this->getLang('e_mail'));
        }
        return $thanks;
    }

    protected function mail_addRow($col1,$col2=null) {
        if($col2 === null) {
            $this->_mail_html .= '<tr><td colspan="2"><u>'.hsc($col1).'<u></td></tr>';
            $this->_mail_text .= "\n=====".$col1.'=====';
        } else {
            $this->_mail_html .= '<tr><td><b>'.hsc($col1).'<b></td><td>'.hsc($col2).'</td></tr>';
            $this->_mail_text .= "\n $col1 \t\t $col2";
        }
    }
}
// vim:ts=4:sw=4:et:enc=utf-8:
