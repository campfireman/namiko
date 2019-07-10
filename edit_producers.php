<?php
ini_set("display_errors", 1);
session_start();
require_once("inc/config.inc.php");
require_once("inc/functions.inc.php");

//Überprüfe, dass der User eingeloggt ist
//Der Aufruf von check_user() muss in alle internen Seiten eingebaut sein
$user = check_user();
check_consul();

include("templates/header.inc.php");
include("templates/nav.inc.php");
include("templates/admin-nav.inc.php");

if (isset($_POST['updateDoc'])) {
  $description = $_POST['description'];
  $pro_id = $_POST['pro_id'];

  $statement = $pdo->prepare("UPDATE producers SET description = '$description' WHERE pro_id = '$pro_id'");
  $result = $statement->execute();

  if ($result) {
    $_SESSION['notification'] = true;
    $_SESSION['notificationmsg'] = 'Herstellerinformationen erfolgreich aktualisiert.';
    header("Location: producers.php");
  } else {
    $_SESSION['notification'] = true;
    $_SESSION['notificationmsg'] = 'Herstellerinformationen konnte nicht aktualisiert werden.';
    header("Location: producers.php");
  }
}

if (isset($_POST['edit_producer'])) {
  $pro_id = $_POST['pro_id'];

  $statement = $pdo->prepare("SELECT description, producerName FROM producers WHERE pro_id = '$pro_id'");
  $result = $statement->execute();

  if ($result) {
    $row = $statement->fetch();

    echo "<script>var doc = '". $row['description'] ."';</script>"; 

    echo '<div class="sizer spacer">
    <span class="subtitle2">'. $row['producerName'] .'</span><br><br>
    <form method="post" action="'. htmlspecialchars($_SERVER['REQUEST_URI']) .'">
    <input type="hidden" name="description">
    <input type="hidden" name="pro_id" value="'. $pro_id .'">
    <label>Herstellerinformationen aktualisieren</label>
    <div id="summernote"></div><br>
    <button id="updateDoc" type="submit" name="updateDoc" class="clean-btn green">Speichern <i class="fa fa-floppy-o" aria-hidden="true"></i></button>
    </form>
    </div>';
  } else {
    echo 'Keinen Eintrag für diese pro_id gefunden.';
  }
}


?>
<link href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.11/summernote-lite.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.11/summernote-lite.js"></script>



<script>

  function slashEscape(contents) {
    return contents
        .replace(/\\/g, '\\\\')
        .replace(/"/g, '\\"')
        .replace(/\n/g, '\\n');
}
	$('document').ready(function(){
      $('#summernote').summernote({
        placeholder: '',
        tabsize: 2,
        height: 200
      });
      $('#summernote').summernote('code', slashEscape(doc));
      $('#updateDoc').on('click', function(){
      	var description = $('#summernote').summernote('code');
      	$('input[name="description"]').val(description);
      })
  	})
</script>
<?php 
include("templates/footer.inc.php")
?>