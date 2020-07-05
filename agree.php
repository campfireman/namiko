<?php
session_start();
require_once "inc/config.inc.php";
require_once "inc/functions.inc.php";

include "templates/header.inc.php";

// PHPMailer for sending validation email
require 'util/phpmailer/Exception.php';
require 'util/phpmailer/PHPMailer.php';
require 'util/phpmailer/SMTP.php';
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
?>

<div id="reg-con" class="login-background">
</div>



<div id="reg" class="reg-container center-vertical">
	<div class="form-container register">

<?php
// prevevious page check
if (isset($_POST['sepa'])) {

    // double check: user has agreed
    if (isset($_POST['SEPAagree'])) {
        $_SESSION['agreed'] = true;
    }

    if (!isset($_POST['SEPAagree'])) {
        $_SESSION['error'] = true;
        $_SESSION['errormsg'] = 'Du musst dem SEPA Mandat zustimmen.</div></div>';
        header("location: check.php");
    }

    // resume if agreed
    if ($_SESSION['agreed']) {
        // encode password
        $password_hash = password_hash($_SESSION['password'], PASSWORD_DEFAULT);

        // generate verify_code
        function createCode()
        {

            $chars = "abcdefghijkmnopqrstuvwxyz023456789";
            srand((double) microtime() * 1000000);
            $i = 0;
            $code = '';

            // loop for 8 digits
            while ($i <= 7) {
                $num = rand() % 33;
                $tmp = substr($chars, $num, 1);
                $code = $code . $tmp;
                $i++;
            }

            return $code;

        }

        // gather all data from session variables
        $first_name = $_SESSION['first_name'];
        $last_name = $_SESSION['last_name'];
        $email = $_SESSION['email'];
        $postal_code = $_SESSION['postal_code'];
        $region = $_SESSION['region'];
        $street = $_SESSION['street'];
        $street_number = $_SESSION['street_number'];
        $account_holder = $_SESSION['account_holder'];
        $iban = $_SESSION['iban'];
        $bic = $_SESSION['bic'];
        $contribution = $_SESSION['contribution'];
        $loan = $_SESSION['loan'];
        $verify_code = createCode();

        // insert payment data into document
        $memberDoc = $_SESSION['memberDoc1'] . $_SESSION['contribution'] . $_SESSION['memberDoc2'];

        $statement = $pdo->prepare("INSERT INTO users (email, password, first_name, last_name, postal_code, region, street, street_number, account_holder, iban, bic, contribution, loan, verify_code) VALUES (:email, :password, :first_name, :last_name, :postal_code, :region, :street, :street_number, :account_holder, :iban, :bic, :contribution, :loan, :verify_code)");
        $result = $statement->execute(array('email' => $email, 'password' => $password_hash, 'first_name' => $first_name, 'last_name' => $last_name, 'postal_code' => $postal_code, 'region' => $region, 'street' => $street, 'street_number' => $street_number, 'account_holder' => $account_holder, 'iban' => $iban, 'bic' => $bic, 'contribution' => $contribution, 'loan' => $loan, 'verify_code' => $verify_code));

        if (!$result) {
            echo $_SESSION['errortxt'] . 'Beim Abspeichern ist leider ein Fehler aufgetreten</div></div>';
            session_destroy();
        }

        $statement = "SELECT uid FROM users WHERE email = '$email'";
        $uid = $pdo->query($statement)->fetchAll(PDO::FETCH_COLUMN);

        $ip = '';
        if (getenv('HTTP_CLIENT_IP')) {
            $ip = getenv('HTTP_CLIENT_IP');
        } else if (getenv('HTTP_X_FORWARDED_FOR')) {
            $ip = getenv('HTTP_X_FORWARDED_FOR');
        } else if (getenv('HTTP_X_FORWARDED')) {
            $ip = getenv('HTTP_X_FORWARDED');
        } else if (getenv('HTTP_FORWARDED_FOR')) {
            $ip = getenv('HTTP_FORWARDED_FOR');
        } else if (getenv('HTTP_FORWARDED')) {
            $ip = getenv('HTTP_FORWARDED');
        } else if (getenv('REMOTE_ADDR')) {
            $ip = getenv('REMOTE_ADDR');
        } else {
            $ip = 'UNKNOWN';
        }

        $statement = $pdo->prepare("INSERT INTO mandates (uid, ip) VALUES (:uid, :ip)");
        $result2 = $statement->execute(array('uid' => $uid[0], 'ip' => $ip));

        $statement = "SELECT mid FROM mandates WHERE uid = '$uid[0]'";
        $mid = $pdo->query($statement)->fetchAll(PDO::FETCH_COLUMN);

        // insert mandate reference id
        $sepaDoc = $_SESSION['sepaDoc1'] . $mid[0] . $_SESSION['sepaDoc2'];

        // import tcpdf library
        require_once 'util/tcpdf/tcpdf.php';

        ##################### Create PDFs #####################

        // Erstellung des PDF Dokuments
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Dokumenteninformationen
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('admin');
        $pdf->SetTitle('Mitglied Nr. ' . $uid[0] . '_' . $last_name . ' ' . $last_name);
        $pdf->SetSubject('Mitgliedschaft');

        // Header und Footer Informationen
        $pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

        // Auswahl des Font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // Auswahl der MArgins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

        // Automatisches Autobreak der Seiten
        $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);

        // Image Scale
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // Schriftart
        $pdf->SetFont('helvetica', '', 10);

        // Neue Seite
        $pdf->AddPage();

        // Fügt den HTML Code in das PDF Dokument ein
        $pdf->writeHTML($memberDoc, true, false, true, false, '');

        //Ausgabe der PDF

        $memberName = $uid[0] . '_' . $first_name . '_' . $last_name . '_' . $verify_code . '.pdf';

        //Variante 2: PDF im Verzeichnis abspeichern:
        $pdf->Output(dirname(__FILE__) . '/applications/' . $memberName, 'F');

        //------------------------------------------------

        // Erstellung des PDF Dokuments
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Dokumenteninformationen
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('admin');
        $pdf->SetTitle('Mandat Nr.' . $mid[0] . '_' . $first_name . ' ' . $last_name);
        $pdf->SetSubject('Mitgliedsantrag');

        // Header und Footer Informationen
        $pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

        // Auswahl des Font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // Auswahl der MArgins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

        // Automatisches Autobreak der Seiten
        $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);

        // Image Scale
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // Schriftart
        $pdf->SetFont('helvetica', '', 10);

        // Neue Seite
        $pdf->AddPage();

        // Fügt den HTML Code in das PDF Dokument ein
        $pdf->writeHTML($sepaDoc, true, false, true, false, '');

        //Ausgabe der PDF

        $mandateName = $mid[0] . '_' . $_SESSION['first_name'] . '_' . $_SESSION['last_name'] . '_' . $verify_code . '.pdf';

        //Variante 2: PDF im Verzeichnis abspeichern:
        $pdf->Output(dirname(__FILE__) . '/mandates/' . $mandateName, 'F');

        ##################### Send Validation Mail with PDFs attached #####################

        $mail = new PHPMailer(true);
        try {
            //Server settings
            $mail->SMTPDebug = 0;
            $mail->isSMTP();
            $mail->Host = $smtp_host;
            $mail->SMTPAuth = true;
            $mail->Username = $smtp_username;
            $mail->Password = $smtp_password;
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';

            //Recipients
            $mail->setFrom('noreply@namiko.org', 'namiko e.V. Hannover');
            $mail->addAddress($email, $first_name . $last_name);
            $mail->addReplyTo('noreply@namiko.org', 'NoReply');

            //Attachments
            $mail->addAttachment('applications/' . $memberName);
            $mail->addAttachment('mandates/' . $mandateName);

            //Content
            $mail->isHTML(true);
            $mail->Subject = 'Deine Mitgliedschaft bei namiko Hannover e.V.';
            $mail->Body = '<h1>Willkommen ' . $first_name . '!</h1>
			    					<p>Um deine Anmeldung abzuschliessen, logge Dich ein und gib folgenden Code ein:<br><br><span style="font-weight: 600; font-size: 30px;">' . $verify_code . '</span><br><br>
			    					Wir freuen uns sehr mit Dir zusammenzuarbeiten. Anbei findest du deinen Mitgliedschaftsbeitrag & das Lastschriftmandat.<br><br><span style="font-style: italic">Dein namiko Hannover e.V. Team</span><br><br><br><br><br><br>
			    					Bei Rückfragen einfach an kontakt@namiko.org schreiben.</p>';
            $mail->AltBody = 'Willkommen ' . $first_name . '!
			    					Um deine Anmeldung abzuschließen, logge Dich ein und gib folgenden Code ein:' . $verify_code . '
			    					Wir freuen uns sehr mit Dir zusammenzuarbeiten. Anbei findest du deinen Mitgliedschaftsantrag & das Lastschriftmandat. Dein namiko Hannover e.V. Team
			    					Bei Rueckfragen einfach an kontakt@namiko.org schreiben.';

            $mail->send();
            echo 'Verifizeriungsmail wurde erfolgreich versendet.<br><br>';
        } catch (Exception $e) {
            echo 'Verifizeriungsmail konnte nicht versendet werden. Mailer Error: ', $mail->ErrorInfo;
        }

        if ($result) {
            echo 'Du wurdest erfolgreich registriert. <a href="login.php">Zum Login</a>';
            session_destroy();
        }
    }
} else {
    $_SESSION['errormsg'] = 'Bitte erst zustimmen.</div></div>';
    $_SESSION['error'] = true;
    header("location: check.php");
}
?>
</div>
</div>

<script>

function close_error () {
	var x = document.getElementsByClassName('error');
	document.body.classList.remove('noscroll');

	for (var i = 0; i <= x.item.length; i++) {
		if (x.item(i) != null) {
		x.item(i).style.height = '0';
		}
	}
}


</script>
<?php
include "templates/footer.inc.php"
?>