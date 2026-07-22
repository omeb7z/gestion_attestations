<?php
session_start();
unset($_SESSION['membre_id'], $_SESSION['membre_nom'], $_SESSION['membre_email']);
header("Location: login_membre.php");
exit();