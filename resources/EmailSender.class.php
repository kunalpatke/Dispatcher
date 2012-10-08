<?php
/* 
 * 
 *  @Author: Abhijit
 */
Class EmailSender{
    public static function sendEmail($email,$message,$subject,$header){
        $mail_sent = mail( $email, $subject, $message, $header);
        if($mail_sent)
            return TRUE;
	else
            return FALSE;
    }

static $mimetypes=array();

public static function send($head, $subject, $body, $attachments=array())
  {
    if (count($head['from'])!=1) return false;
    if (count($head['to'  ])==0) return false;
   
    $to='';
    foreach($head['to'] as $cc_addr=>$cc_name)
        $to.= $cc_name . "<" . $cc_addr . ">";    
    $fromaddress = array_pop( array_keys  ($head['from']) );
    $fromname    = array_pop( array_values($head['from']) );
    $eol="\r\n";
    $mime_boundary=md5(time())."-2";
    $mime_boundary2= $mime_boundary."-3";

    # Common Headers
    $headers='';
    $headers .= "Message-ID: <".time()."-".$fromaddress.">".$eol;
    $headers .= "Date: ".date('r').$eol;
    $headers .= "From: ".$fromname."<".$fromaddress.">".$eol;
    if (isset($head['cc']))
        foreach($head['cc'] as $cc_address=>$cc_name)
            $headers .= "Cc: ".$cc_name."<".$cc_address.">".$eol;
    if (isset($head['bcc']))
        foreach($head['bcc'] as $cc_address=>$cc_name)
            $headers .= "Bcc: ".$cc_name."<".$cc_address.">".$eol;
    $headers .= "Reply-To: ".$fromname."<".$fromaddress.">".$eol;
    $headers .= "Return-Path: ".$fromname."<".$fromaddress.">".$eol;    // these two to set reply address
    //$headers .= "Message-ID: <".time()."-".$fromaddress.">".$eol;
    $headers .= "X-Mailer: PHP v".phpversion().$eol;          // These two to help avoid spam-filters

    # Boundry for marking the split & Multitype Headers
    $headers .= 'Mime-Version: 1.0'.$eol;
    $headers .= "Content-Type: multipart/mixed; boundary=\"".$mime_boundary."\"".$eol.$eol;
    #$headers .= "To: ".$to.$eol;
    #$headers .= "Subject: ".$subject.$eol.$eol;
    $headers .= "This is a MIME-formatted message.  If you see this text it means that your".$eol;
    $headers .= "E-mail software does not support MIME-formatted messages.".$eol.$eol;

    # Open the first part of the mail
    $msg ='';

    $msg .= "--".$mime_boundary.$eol;
    $msg .= "Content-Type: multipart/alternative; boundary=\"$mime_boundary2\"".$eol.$eol;
    $msg .= "This is a MIME-formatted message.  IF you see this text it means that your".$eol;
    $msg .= "E-mail softare does not support MIME-formatted messages.".$eol.$eol;
    $msg .= "--".$mime_boundary2.$eol;
    $msg .= "Content-Type: text/plain; charset=iso-8859-1; format=flowed".$eol;
    $msg .= "Content-Transfer-Encoding: 7bit".$eol;
    $msg .= "Content-Disposition: inline".$eol.$eol;
    $msg .= strip_tags(str_replace("<br>", "\n", $body ));
    $msg .= $eol.$eol;
    $msg .= "--".$mime_boundary2.$eol;
    $msg .= "Content-Type: text/html; charset=iso-8859-1;".$eol;
    $msg .= "Content-Transfer-Encoding: quoted-printable".$eol;
    $msg .= "Content-Disposition: inline".$eol.$eol;
    $msg .= "<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\">".$eol;
    $msg .= "<html>".$eol;
    $msg .= "<body>".$eol;
    $msg .= EmailSender::mime_html_encode($body).$eol;
    $msg .= "</body>".$eol;
    $msg .= "</html>".$eol;
    $msg .= $eol.$eol;
    $msg .= "--".$mime_boundary2."--".$eol.$eol;

    for($i=0; $i < count($attachments); $i++)
    {
        if (is_file($attachments[$i]))
        {  
          # File for Attachment
          $file_name = basename($attachments[$i]);
         
          $handle=fopen($attachments[$i], 'rb');
          $f_contents=fread($handle, filesize($attachments[$i]));
          $f_contents=chunk_split(base64_encode($f_contents));    //Encode The Data For Transition using base64_encode();
          $f_type=filetype($attachments[$i]);
          fclose($handle);

          $mime_type = EmailSender::get_mimetype( array_pop( explode(".", $attachments[$i] ) ) );
         
          # Attachment
          $msg .= "--".$mime_boundary.$eol;
          $msg .= "Content-Type: ".$mime_type."; name=\"".$file_name."\"".$eol;
          $msg .= "Content-Transfer-Encoding: base64".$eol;
          $msg .= "Content-Description: inline; $eol filename=\"".$file_name."\"".$eol.$eol; // !! This line needs TWO end of lines !! IMPORTANT !!
          $msg .= $f_contents.$eol.$eol;
        }
    }
   

    # Finished
    $msg .= "--".$mime_boundary."--".$eol.$eol;  // finish with two eol's for better security. see Injection.
   
    # SEND THE EMAIL
    ini_set('sendmail_from',$fromaddress);  // the INI lines are to force the From Address to be used !
    $mail_sent = mail($to, $subject, $msg, $headers);
   
    ini_restore('sendmail_from');
   
    return $mail_sent;
  }


  public static function get_mimetype($ext)
  {
    if (count(EmailSender::$mimetypes)==0)
        EmailSender::$mimetypes = EmailSender::mimelist();
    $ext_lower = strtolower(str_replace(".","",$ext));
    if (isset( EmailSender::$mimetypes[$ext_lower] ))
        return EmailSender::$mimetypes[$ext_lower];

    return "application/octet-stream";
  }

private static function mimelist()
  {
	$arr['pdf'  ]='application/pdf';
	return $arr;
}
private function mime_html_encode($input , $line_max = 76)
  {
   
    $eol    = "\r\n";//MAIL_MIMEPART_CRLF
    $output = '';
    $line   = '';
    $intag  = false;
   
   
    for($i=0; $i<strlen($input); $i++)
    {
      $ip=$input{$i};
      $op='';
         
      if ($intag)
      {
          if ($ip=="=") $op="=3D";
          else $op= $ip;
      }
      else
      {
          if ($ip=="\"") $op='"';//'
          else if ($ip=="&") $op="&";
          else if ($ip=="'") $op="'";
          else $op= $ip;
      }


      if ((strlen($line)+strlen($op))>=$line_max)
      {
          $output.=$line.'='.$eol;
          //if ($intag) $output.=$line.'='.$eol;
          //else  $output.=$line.$eol;
          $line='';
      }
      $line.=$op;

      if($ip=='<')
          $intag=true;
      else if ($ip=='>')
          $intag=false;
    }
    return $output.$line.$eol;
  }

}
?>
