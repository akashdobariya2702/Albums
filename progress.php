<?php
session_start();
$total = ceil(($_SESSION['Progress']*100)/$_SESSION['Count']);
echo $total;

?>