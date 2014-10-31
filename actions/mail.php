<?php
/**
 * Action sendemail for DokuWiki plugin bureaucracy
 */

class syntax_plugin_bureaucracy_action_mail extends syntax_plugin_bureaucracy_action {

    protected $_mail_html = '';
    protected $_mail_text = '';
    protected $subject = '';
    protected $replyto = array();

    /**
     * Build a nice email from the submitted data and send it
     *
     * @param syntax_plugin_bureaucracy_field[] $fields
     * @param string                            $thanks
     * @param array                             $argv
     * @return string thanks message
     * @throws Exception mailing failed
     */
    public function run($fields, $thanks, $argv) {
        global $ID;
        global $conf;

        $mail = new Mailer();

        $this->prepareLanguagePlaceholder();

        //set default subject
        $this->subject = sprintf($this->getLang('mailsubject'),$ID);

        $this->_mail_text .=  sprintf($this->getLang('mailintro')."\n\n", dformat());
        $this->_mail_html .=  sprintf($this->getLang('mailintro')."<br><br>", dformat());

        $this->buildTables($fields, $mail);

        $this->subject = $this->replaceDefault($this->subject);

        if(!empty($this->replyto)) {
            $replyto = $mail->cleanAddress($this->replyto);
            $mail->setHeader('Reply-To', $replyto, false);
        }
        
        $to = $mail->cleanAddress(implode(',',$argv)); // get recipient address(es) 
        $mail->to($to);
        $mail->from($conf['mailfrom']);
        $mail->subject($this->subject);
        $mail->setBody($this->_mail_text,null,null,$this->_mail_html);
        

        if(!$mail->send()) {
            throw new Exception($this->getLang('e_mail'));
        }
        return $thanks;
    }

    /**
     * Create html and plain table of the field
     *
     * @param syntax_plugin_bureaucracy_field[] $fields
     * @param Mailer $mail
     */
    protected function buildTables($fields, $mail) {
        $this->_mail_html .= '<table>';

        foreach($fields as $field) {
            $value = $field->getParam('value');
            $label = $field->getParam('label');

            switch($field->getFieldType()) {
                case 'fieldset':
                    $this->mail_addRow($label);
                    break;
                case 'file':
                    $file = $field->getParam('file');
                    if(!$file['size']) {
                        $this->mail_addRow($label, $this->getLang('attachmentMailEmpty'));
                    } else if($file['size'] > $this->getConf('maxEmailAttachmentSize')) {
                        $this->mail_addRow($label, $file['name'] . ' ' . $this->getLang('attachmentMailToLarge'));
                    } else {
                        $this->mail_addRow($label, $file['name']);
                        $mail->attachFile($file['tmp_name'], $file['type'], $file['name']);
                    }
                    break;
                case 'subject':
                    $this->subject = $label;
                    break;
                /** @noinspection PhpMissingBreakStatementInspection */
                case 'email':
                    if(!is_null($field->getParam('replyto'))) {
                        $this->replyto[] = $value;
                    }
                /** fall through */
                default:
                    if($value === null || $label === null) break;
                    $this->mail_addRow($label, $value);
            }

            $this->prepareFieldReplacements($label, $value);
        }
        $this->_mail_html .= '</table>';
    }

    /**
     * Add a row
     *
     * @param $column1
     * @param null $column2
     */
    protected function mail_addRow($column1,$column2=null) {
        if($column2 === null) {
            $this->_mail_html .= '<tr><td colspan="2"><u>'.hsc($column1).'<u></td></tr>';
            $this->_mail_text .= "\n=====".$column1.'=====';
        } else {
            $this->_mail_html .= '<tr><td><b>'.hsc($column1).'<b></td><td>'.hsc($column2).'</td></tr>';
            $this->_mail_text .= "\n $column1 \t\t $column2";
        }
    }
}
// vim:ts=4:sw=4:et:enc=utf-8:
