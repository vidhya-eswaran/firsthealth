<?php
$resetUrl = isset($_GET['resetUrl']) ? $_GET['resetUrl'] : '';
$email = isset($_GET['email']) ? $_GET['email'] : '';

$appLink = "firsthealth://" . $resetUrl .'&email=' .$email ;
//print_r($appLink);
?>

<script>
window.location.href = '<?php echo $appLink; ?>';
</script>
