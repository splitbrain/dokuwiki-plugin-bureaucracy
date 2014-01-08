<?php
/**
 * Action sendemail for DokuWiki plugin bureaucracy
 */

class syntax_plugin_bureaucracy_action_mail extends syntax_plugin_bureaucracy_action {

    /**
     * Build a nice email from the submitted data and send it
     */
    function run($data, $thanks, $argv) {
        global $ID;
        global $conf;

        // get recipient address(es)
        $to = join(',',$argv);

        $sub = sprintf($this->getLang('mailsubject'),$ID);
        $txt = sprintf($this->getLang('mailintro')."\n\n\n", dformat());
        
        $headers_attachment = '';
        $boundary = strtoupper(md5(uniqid(time())));
        $headers = "\nContent-Type: multipart/mixed; boundary=$boundary";

        foreach($data as $opt){
            $value = $opt->getParam('value');
            $label = $opt->getParam('label');
            
            switch($opt->getFieldType()){
                case 'fieldset':
                    $txt .= "\n====== ".hsc($label)." ======\n\n";
                    break;
                case 'file':
                    
                    if(!$value['size']) {
                        $txt .= $label."\n";
                        $txt .= "\t\t(empty)\n";
                    } else if($value['size'] > 43145728) {
                        $txt .= $label."\n";
                        $txt .= "\t\t".hsc($value['name'])."(File too large)\n";
                    } else {
                        $file_content = fread(fopen($value['tmp_name'],"r"),$value['size']);
                        $file_content = chunk_split(base64_encode($file_content));
                        $headers_attachment .= "\n--$boundary";
                        $headers_attachment .= "\nContent-Type: ".$value['type']."; name=\"".$value['name']."\"";
                        $headers_attachment .= "\nContent-Length: ".$value['size'].";";
                        $headers_attachment .= "\nContent-Transfer-Encoding: base64";
                        $headers_attachment .= "\nContent-Disposition: attachment; filename=\"".$value['name']."\"";
                        $headers_attachment .= "\n$file_content";
                    }
                    break;
                default:
                    if($value === null || $label === null) break;
                    $txt .= $label."\n";
                    $txt .= "\t\t$value\n";
            }
        }

        $headers_txt .= "\n--$boundary";
        $headers_txt .= "\nContent-Type: text/plain; charset=UTF-8"; 
        $headers_txt .= "\nContent-Transfer-Encoding: quoted-printable";
        $headers_txt .= "\n" . mail_quotedprintable_encode($txt);

        if(!$this->_mail_send(array(
                'to'=>$to,
                'subject'=>$sub,
                'body'=>$headers_txt . $headers_attachment . "\n--$boundary--",
                'from'=>$conf['mailfrom'],
                'headers'=>$headers
            ))) {
            throw new Exception($this->getLang('e_mail'));
        }
        return $thanks;
    }
    
    /**
     * copied from inc/mail.php _mail_send_action
     * without mail_quotedprintable_encode on whole body param
     */
    function _mail_send($data) {

        // retrieve parameters from event data, $to, $subject, $body, $from, $cc, $bcc, $headers, $params
        $to = $data['to'];
        $subject = $data['subject'];
        $body = $data['body'];

        // add robustness in case plugin removes any of these optional values
        $from = isset($data['from']) ? $data['from'] : '';
        $cc = isset($data['cc']) ? $data['cc'] : '';
        $bcc = isset($data['bcc']) ? $data['bcc'] : '';
        $headers = isset($data['headers']) ? $data['headers'] : null;
        $params = isset($data['params']) ? $data['params'] : null;

        // discard mail request if no recipients are available
        if(trim($to) === '' && trim($cc) === '' && trim($bcc) === '') return false;

        // end additional code to support event ... original mail_send() code from here

        if(defined('MAILHEADER_ASCIIONLY')){
            $subject = utf8_deaccent($subject);
            $subject = utf8_strip($subject);
        }

        if(!utf8_isASCII($subject)) {
            $enc_subj = '=?UTF-8?Q?'.mail_quotedprintable_encode($subject,0).'?=';
            // Spaces must be encoded according to rfc2047. Use the "_" shorthand
            $enc_subj = preg_replace('/ /', '_', $enc_subj);

            // quoted printable has length restriction, use base64 if needed
            if(strlen($subject) > 74){
                $enc_subj = '=?UTF-8?B?'.base64_encode($subject).'?=';
            }

            $subject = $enc_subj;
        }

        $header  = '';

        // No named recipients for To: in Windows (see FS#652)
        $usenames = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? false : true;

        $to = mail_encode_address($to,'',$usenames);
        $header .= mail_encode_address($from,'From');
        $header .= mail_encode_address($cc,'Cc');
        $header .= mail_encode_address($bcc,'Bcc');
        $header .= 'MIME-Version: 1.0'.MAILHEADER_EOL;
        $header .= 'Content-Type: text/plain; charset=UTF-8'.MAILHEADER_EOL;
        $header .= 'Content-Transfer-Encoding: quoted-printable'.MAILHEADER_EOL;
        $header .= $headers;
        $header  = trim($header);

        //$body = mail_quotedprintable_encode($body);

        if($params == null){
            return @mail($to,$subject,$body,$header);
        }else{
            return @mail($to,$subject,$body,$header,$params);
        }
    }

}
// vim:ts=4:sw=4:et:enc=utf-8:
