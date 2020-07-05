<?php
session_start();
require_once "inc/config.inc.php";
require_once "inc/functions.inc.php";

$error_msg = "";
if (isset($_POST['email']) && isset($_POST['password'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $statement = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $result = $statement->execute(array('email' => $email));
    $user = $statement->fetch();

    // check password
    if ($user !== false && password_verify($password, $user['password'])) {
        $_SESSION['userid'] = $user['uid'];

        // Keep me logged in?
        if (isset($_POST['angemeldet_bleiben'])) {
            $identifier = random_string();
            $securitytoken = random_string();

            $insert = $pdo->prepare("INSERT INTO securitytokens (user_id, identifier, securitytoken) VALUES (:user_id, :identifier, :securitytoken)");
            $insert->execute(array('user_id' => $user['uid'], 'identifier' => $identifier, 'securitytoken' => sha1($securitytoken)));
            setcookie("identifier", $identifier, time() + (3600 * 24 * 365)); //Valid for 1 year
            setcookie("securitytoken", $securitytoken, time() + (3600 * 24 * 365)); //Valid for 1 year
        }

        header("location: internal.php");
        exit;
    } else {
        $error_msg = "E-Mail oder Passwort war ungültig<br><br>";
    }

}

$email_value = "";
if (isset($_POST['email'])) {
    $email_value = htmlentities($_POST['email']);
}

include "templates/header.inc.php";
?>

<div class="login-background">
</div>

<div class="center-vertical" style="height: 100vh">
	<div class="login form-container">
		<?php
if (isset($error_msg)) {
    echo $error_msg;
}
?>
		<form action="login.php" method="post">
			<input type="email" name="email" id="inputEmail" class="" placeholder="E-Mail" value="<?php echo $email_value; ?>" required autofocus>

			<input type="password" name="password" id="inputPassword" class="" placeholder="Passwort" required><br>

			<button class="login-btn" type="submit">Login <i class="fa fa-sign-in" aria-hidden="true"></i></button><br>

			<div class="checkbox">
			  <label style="font-size: 13px;">
				<input type="checkbox" value="remember-me" name="angemeldet_bleiben" value="1">Angemeldet bleiben
			  </label>
			</div>

			<div class="misc">
				<a href="forgotpassword.php" class="blue">Passwort vergessen <i class="fa fa-question" aria-hidden="true"></i></a><span> | </span>
				<a href="register.php" class="green">Mitglied werden <i class="fa fa-users" aria-hidden="true"></i></a>
			</div>
		</form>
	</div>
</div>




<?php
include "templates/footer.inc.php"
?>