<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo 'POST request successful';
} else {
    echo 'This is not a POST request';
}
?>
