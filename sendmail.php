<?php
/**
 * Created by PhpStorm.
 * User: Whiskey
 * Date: 29-11-2015
 * Time: 21:13
 */

require_once 'PHPMailer/class.phpmailer.php';
require_once 'PHPMailer/class.smtp.php';
require_once 'Db/Db.php';


function SendEmail(){
    // error_log("Sending mail...");

    $config = parse_ini_file('Db/dbconfig.ini', true);

    $sql = "SELECT * FROM email WHERE sent = 0";
    $stmt = Db::getInstance()->prepare($sql);
    $stmt->execute();
    $emails = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    error_log('Email count: ' . count($emails));
    foreach ($emails as $email) {
        error_log('Processing mail id: ' . $email['email_id']);
        $mailer = new PHPMailer();  // create a new object
        $mailer->AddAddress($email['toaddress']);
        $mailer->SetFrom($email['toaddress'], 'Going Dutch');
        $mailer->IsSMTP(); // enable SMTP
        $mailer->SMTPDebug = 0;  // debugging: 1 = errors and messages, 2 = messages only
        $mailer->SMTPAuth = true;  // authentication enabled
        $mailer->SMTPSecure = 'ssl'; // secure transfer enabled REQUIRED for Gmail
        $mailer->Host = $config['email']['host'];
        $mailer->Port = $config['email']['port'];
        $mailer->Username = $config['email']['user'];
        $mailer->Password = $config['email']['pass'];
        $mailer->Subject = $email['subject'];
        $mailer->Body = $email['message'];
        $mailer->IsHTML(true);


        $sql = "UPDATE email SET sent=FROM_UNIXTIME(:updated) WHERE email_id=:email_id";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(
            array(
                ':email_id' => $email['email_id'],
                ':updated' => time()
            )
        );

        if (!$mailer->Send()) {
            $smtpmailer_error = 'Mail error: ' . $mailer->ErrorInfo;
            error_log('Mail error: ' . $mailer->ErrorInfo);
            // return false;
        } else {
            $smtpmailer_error = 'Message sent!';
            error_log('Mail for expense ' . $email['eid'] .  ' sent to ' . $email['toaddress']);
            //return true;
        }
    }
}

SendEmail();