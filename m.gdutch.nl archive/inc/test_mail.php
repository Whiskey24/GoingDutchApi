<?php
error_reporting(-1);
ini_set('safe_mode_exec_dir', '/');
echo "testing<br />";
$output = 'output.txt';
$background_mailfile = dirname(__FILE__) . '/background_mailer.php';
      $cmd = "/usr/bin/php {$background_mailfile} atsantema@yahoo.com atsantema@yahoo.com \"Bert Santema\" \"Test email\" \"Testerdetest\" \"Atsantema@yahoo.com\"";
      //exec("/usr/bin/php {$background_mailfile} {$user['email']} {$from} {$from_name} {$subject} {$body} {$replyto} {$sendas} > {$ouput} &");
      exec("{$cmd}  ");
echo "done testing<br /><br >";
echo $cmd;
echo "<br><br>output: {$output}";
phpinfo();
