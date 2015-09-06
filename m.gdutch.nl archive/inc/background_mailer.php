<?php


function exec_enabled() {
  $disabled = explode(', ', ini_get('disable_functions'));
  return !in_array('exec', $disabled);
}

//if (exec_enabled())
//  print "Exec enabled\n";
//else 
//  print "Exec DISabled\n";
//exit;


$to = $_SERVER['argv'][1];
$from = $_SERVER['argv'][2];
$from_name = $_SERVER['argv'][3];
$subject = $_SERVER['argv'][4];
$body = $_SERVER['argv'][5];
$replyto = $_SERVER['argv'][6];
$sendas  = $_SERVER['argv'][7];

//print ("TEST\n");
//print ("to: {$to}\n");
//print("from: {$from}\n");
//print("from_name: {$from_name}\n");
//print("subject: {$subject}\n");
//print("body: {$body}\n");
//print("replyto: {$replyto}\n");
//print("sendas: {$sendas}\n");
$date = date('l jS \of F Y h:i:s A');
//logToFile($date);
global $smtpmailer_error;
smtpmailer($to, $from, $from_name, $subject, $body, $replyto, $sendas);
//logToFile($smtpmailer_error);
function logToFile($string) {
  $myFile = "mailLog.txt";
  $fh = fopen($myFile, 'a') or die("can't open file");
  fwrite($fh, $string."\n");
  fclose($fh);
}
//define('GUSER', 'bert@inthere.nl'); // Gmail username
//define('GPWD', 'nannhnapniboctio '); // Gmail password

function smtpmailer($to, $from, $from_name ,  $subject, $body, $replyto = '', $sendas = 'to') {
  $guser = 'bert@inthere.nl';
  $gpwd =  'nannhnapniboctio';
  if (empty($sendas))
    $sendas = 'to';  
  $mailclass =  dirname(__FILE__) . '/PHPMailer_v5.1/class.phpmailer.php';
  require_once ( $mailclass);
  global $smtpmailer_error;
  $mail = new PHPMailer();  // create a new object
  
  if ($sendas == 'to') {
  
  if (is_array($to)) {
    foreach ($to as $emailaddress)
      $mail->AddAddress($emailaddress);
  } elseif (substr_count($to, ',') > 0) {
    $tolist = explode(',', $to);
    foreach ($tolist as $emailaddress)
      $mail->AddAddress($emailaddress);
  } elseif (substr_count($to, ';') > 0) {
    $tolist = explode(';', $to);
    foreach ($tolist as $emailaddress)
      $mail->AddAddress($emailaddress);
  } else
    $mail->AddAddress($to);
  
  }

    elseif ($sendas == 'bcc') {
  
  if (is_array($to)) {
    foreach ($to as $emailaddress)
      $mail->AddBCC($emailaddress);
  } elseif (substr_count($to, ',') > 0) {
    $tolist = explode(',', $to);
    foreach ($tolist as $emailaddress)
      $mail->AddBCC($emailaddress);
  } elseif (substr_count($to, ';') > 0) {
    $tolist = explode(';', $to);
    foreach ($tolist as $emailaddress)
      $mail->AddBCC($emailaddress);
  } else
    $mail->AddBCC($to);
  
  }
  
  
  if (!empty($replyto)) {
    if (is_array($replyto)) {
      $mail->AddReplyTo($replyto[0], $replyto[1]);
    } else 
    $mail->AddReplyTo($replyto);
  }
    
  
  $mail->IsSMTP(); // enable SMTP
  $mail->SMTPDebug = 0;  // debugging: 1 = errors and messages, 2 = messages only
  $mail->SMTPAuth = true;  // authentication enabled
  $mail->SMTPSecure = 'ssl'; // secure transfer enabled REQUIRED for Gmail
  $mail->Host = 'smtp.gmail.com';
  $mail->Port = 465;
  $mail->Username = $guser;
  $mail->Password = $gpwd;
  $mail->SetFrom($from, $from_name);
  $mail->Subject = $subject;
  $mail->Body = $body;
//$mail->AddAddress($to);
  $mail->IsHTML(true);
  if (!$mail->Send()) {
    $smtpmailer_error = 'Mail error: ' . $mail->ErrorInfo;
    return false;
  } else {
    $smtpmailer_error = 'Message sent!';
    return true;
  }
}
?>
