<?php

include("inc/common.php");

// get post mode
if (isset($_POST['mode'])) {
  $mode = $_POST['mode'];
} elseif (isset($_GET['code'])) {
  $regcode = $_GET['code'];
  $mode = 'register';
} else {
  $mode = "login";
}

switch ($mode) {
  case "login":
    //$user = new uFlex();
    $showloginform = true;
    break;

  case "validate":

    if (isset($_POST['username'])) {
      $username = $_POST['username'];
    }
    if (isset($_POST['password'])) {
      $password = $_POST['password'];
    }
    if (isset($_POST['auto'])) {
      $auto = $_POST['auto'];
    }  // To remember user with a cookie for autologin

    $user = new uFlex($username, $password, 1);
    if ($user->signed) {
      // successful login, sent to this page to let common.php handle next page

      header("Location: http://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']);
      exit;
    } else {
      // login unsuccessful, sent back to login
      foreach ($user->error() as $err) {
        $errorString[] = $err;
      }
      //$errorString = "<p>" . $errorString . "</p>\n";
      $showloginform = true;
    }
    break;

  case "register":
    //Code Here
    $showregisterform = true;
    break;

  case "lostpass":
    //Code Here
    $showlostpassform = true;
    break;
  
  
    case "processlostpassemail":
    //Code Here

      if (email_exists($_POST['email'], true)) {
        // email found reset
        
        $uid = get_userid_by_email($_POST['email']);
        $profile = get_user_profile($uid);
        $newpass = reset_pass($_POST['email'], $user);

        if ($newpass != false) {
          // send email
          $subject = "Going Dutch password reset";
          $link = '<a href="' . LOGIN_URL . '">login</a>';
          $body = "Someone (most likely you) has requested a password reset for the Going Dutch account with this email address. <br />";
          $body .= "Please reset your password on the profile page after logging in. You can now {$link} with this username and password: <br />";
          $body .= "Username: {$profile['username']}<br />";
          $body .= "Password: {$newpass}<br />";
          $from = 'admin@inthere.nl';
          $from_name = 'Going Dutch';
          smtpmailer($_POST['email'], $from, $from_name, $subject, $body, $replyto = '', $sendas='to');
          $registercomplete[] = "A new password has been emailed to {$_POST['email']}";
          $registercomplete[] .= "Click <a href=\"http://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . "\">here</a> to login";
          $sendpasscomplete = true;
        } else {
          $errorString[] = "Password could not be reset :-(";
          $sendpasserror = true;
        }
        
      } else {
        $errorString[] = "Email address not found";
        $showlostpassform = true;
      }

    break;
  
  
  
  case "processregister":
    include("inc/email_validator.php");

    // validate fields
    $errorString = "";
    if (!is_valid_name($_POST['username'])) {
      $errorString[] = "Invalid username";
    }
    if (!is_valid_real_name($_POST['realname'])) {
      $errorString[] = "Invalid name";
    } elseif (username_exists($_POST['username'])) {
      $errorString[] = "Username already in use";
    }
    /* elseif (realname_exists($_POST['realname'])) {
      $errorString[] = "Name already in use";
      } */
    if (!is_valid_password($_POST['password'], $_POST['password2'])) {
      $errorString[] = "Passwords do not match or are not of required length";
    }
    if (!is_rfc3696_valid_email_address($_POST['email'])) {
      $errorString[] = "Invalid email address";
    }
    if (email_exists($_POST['email'], true)) {
      $errorString[] = "Email address already in use";
    }
    if (!empty($_POST['code']) && !regcode_exists($_POST['code'])) {
      $errorString[] = "Supplied registration code is not recognized or expired.";
    }
    /* if (!is_valid_group($_POST['group_id'])) {
      $errorString .= "Invalid group! (how is this possible?) <br />";
      } */
    if (!empty($errorString)) {
      $showregisterform = true;
    } else {
      // no errors, register user
      // add default group and remove post mode
      unset($_POST['mode']);

      $_POST['group_id'] = false;
      $notnew = false;
      $ugroups = true ;
      // if regcode, update user by regcode      
      if (regcode_exists($_POST['code'])) {
        $ugroups = update_user_by_code($_POST, $user);
        $notnew = true;
      } elseif (email_exists($_POST['email'])) {
        // check if email already in database, then update that user
        $ugroups = update_user_by_email($_POST, $user);
        $notnew = true ;
      }
      if ($ugroups != false && $notnew == true) {
        $gsize = count($ugroups);
        if ($gsize > 1)
          $s = "these groups:";
        else
          $s = "this group:";
        $registercomplete[] = "You have succesfully registered and have been added to $s";
        for ($i = 0; $i < $gsize; $i++) {
          $registercomplete[] = $ugroups[$i]['group_name'];
        }
        $registercomplete[] .= "Click <a href=\"http://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . "\">here</a> to continue";
      } elseif ($notnew == true) {
        $registercomplete[] = "Something went wrong :-(";
      } 
      if (!$notnew){
        // new user, register with uFlex
        if (isset($_POST['code']))
          unset($_POST['code']);
        if (isset($_POST['token']))
          unset($_POST['token']);
        if (isset($_POST['group_id']))
          unset($_POST['group_id']);
        
        $registered = $user->register($_POST, false);
        if ($registered) {
          $user = new uFlex($_POST['username'], $_POST['password'], $_POST['auto']);
          $registercomplete[] = "You have succesfully registered.";
          $registercomplete[] .= "Click <a href=\"http://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . "\">here</a> to continue";
        } else {
          //Display Errors
          foreach ($user->error() as $err) {
            echo "<b>Error:</b> {$err} <br />";
          }
        }
      }
    }
    break;

  case "logout":
    //Code Here
    echo "logout";
    break;

  case "validated":
    //Code Here
    echo "validated";
    break;
}




print_header();




if ($showloginform) {
  // print_pagetitle("Login");
  // array structure: $bararray['title'], $bararray['leftnav'][$i][name|url], $bararray['rightnav'][$i][name|url]
  print_body_start();
  print_topbar("Going Dutch");
  if ($errorString) {
    print_pageitem_text_html("Please correct the following:", $errorString);
  }
  if (isset($_POST['auto'])) {
    $auto = $_POST['auto'];
  } else {
    $auto = "on";
  }
  //$formarray['rows'][$i]['items'] = "label|name|type|value";
  $formarray['action'] = $_SERVER['PHP_SELF'];
  $formarray['rows'][0]['items'] = "Username:|username|text|" . $_POST['username'];
  $formarray['rows'][1]['items'] = "Password:|password|password";
  $formarray['rows'][2]['items'] = "Remember me|auto|checkbox|" . $auto;
  $formarray['rows'][3]['items'] = "|mode|hidden|validate";
  $formarray['rows'][4]['items'] = "||submit|Login";
  echo create_form_html($formarray);
  unset($formarray);

  $formarray['action'] = $_SERVER['PHP_SELF'];
  $formarray['rows'][0]['items'] = "|mode|hidden|register";
  $formarray['rows'][1]['items'] = "||submit|Register";
  echo create_form_html($formarray);
  
  $formarray['action'] = $_SERVER['PHP_SELF'];
  $formarray['rows'][0]['items'] = "|mode|hidden|lostpass";
  $formarray['rows'][1]['items'] = "||submit|Forgot password";
  echo create_form_html($formarray);
  
  
  unset($formarray);
} elseif ($showregisterform) {
  //print_pagetitle("Registration");
  $topbar['title'] = "Registration";
  $topbar['leftnav'][0]['name'] = "Login";
  $topbar['leftnav'][0]['url'] = "http://" . $_SERVER['HTTP_HOST'] . DIR . "index.php";
  print_topbar($topbar);
  print_body_start();

  if ($errorString) {
    print_pageitem_text_html("Please correct the following:", $errorString);
  }
  if (isset($_POST['code']))
    $regcode = $_POST['code'];
  else
    $regcode = space_code($regcode);


  //$formarray['rows'][$i]['items'] = "label|name|type|value";
  $formarray['action'] = $_SERVER['PHP_SELF'];
  $formarray['rows'][0]['items'] = "Username:|username|text|" . $_POST['username'];
  $formarray['rows'][1]['items'] = "Name:|realname|text|" . $_POST['realname'];
  $formarray['rows'][2]['items'] = "Password:|password|password";
  $formarray['rows'][3]['items'] = "Re-enter:|password2|password";
  $formarray['rows'][4]['items'] = "Email:|email|text|" . $_POST['email'];
  $formarray['rows'][5]['items'] = "Registration code (optional):|code|text|" . $regcode;
  $formarray['rows'][6]['items'] = "|mode|hidden|processregister";
  $formarray['rows'][7]['items'] = "||submit|Register";
  echo create_form_html($formarray);
  unset($formarray);
} elseif ($sendpasscomplete) {
  print_pageitem_text_html("Reset complete", $registercomplete);
} elseif ($sendpasserror) {
  print_pageitem_text_html("Oops", $errorString);
} elseif ($registercomplete) {
  print_pageitem_text_html("Registration complete", $registercomplete);
} elseif ($showlostpassform) {
   $topbar['title'] = "Reset password";
  $topbar['leftnav'][0]['name'] = "Login";
  $topbar['leftnav'][0]['url'] = "http://" . $_SERVER['HTTP_HOST'] . DIR . "index.php";
  print_topbar($topbar);
  print_body_start();

  if ($errorString) {
    print_pageitem_text_html("Please correct the following:", $errorString);
  }

  //$formarray['rows'][$i]['items'] = "label|name|type|value";
  $formarray['action'] = $_SERVER['PHP_SELF'];
  $formarray['rows'][0]['items'] = "Email:|email|text|" . $_POST['email'];
  $formarray['rows'][1]['items'] = "|mode|hidden|processlostpassemail";
  $formarray['rows'][2]['items'] = "||submit|Reset password";
  echo create_form_html($formarray);
  unset($formarray);
  
  
  
}

print_footer($user, 5);
?>