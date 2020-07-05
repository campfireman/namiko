<?php
session_start(); //start session
//ini_set('display_errors', 1);
require_once "inc/config.inc.php"; //include config file
require_once "inc/functions.inc.php";
require_once "inc/Mail.inc.php";

if (isset($_POST["subject"])) {
    $subject = $_POST['subject'];
    $text = $_POST['text'];
    $query = "";

    if (isset($_POST['others'])) {
        $query .= 'SELECT email, first_name FROM newsletter_recipients WHERE verified = 1';

        if (isset($_POST['members'])) {
            $query .= ' UNION SELECT email, first_name FROM users WHERE newsletter = 1';
        }
    }

    if (empty($_POST['others']) && isset($_POST['members'])) {
        $query = ' SELECT email, first_name AS first_name FROM users';
    }

    if (empty($_POST['members']) && empty($_POST['others'])) {
        die(json_encode('Kein Rezipient ausgewählt.'));
    }

    $statement = $pdo->prepare($query);
    $result = $statement->execute();
    $batch = array();
    $count = 0;

    while ($row = $statement->fetch()) {
        $batch[$count]['email'] = $row['email'];
        $batch[$count]['recipient'] = $row['first_name'];
        $batch[$count]['subject'] = $subject;
        $batch[$count]['text'] = '<h3>Moin, ' . htmlspecialchars($row['first_name']) . '!</h3>' . $text;
        $count++;
    }

    $mail = new Mail($smtp_host, $smtp_username, $smtp_password, $myEmail, $myEntity);

    if ($mail->sendBatch($batch)) {
        res(1, $mail->getMailErrors());
    } else {
        res(0, 'Mails erfolgreich verschickt.');
    }

}

if (isset($_POST["temp_id"])) {
    $temp_id = $_POST['temp_id'];

    $statement = $pdo->prepare("SELECT template FROM mail_templates WHERE temp_id = '$temp_id'");
    $result = $statement->execute();
    $row = $statement->fetch();

    if ($result) {
        die(json_encode($row['template']));
    } else {
        die(json_encode('Fehler.'));
    }
}
