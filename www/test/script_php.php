<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-15">
<title></title>
</head>

<body>

<?php
echo "<p>Here's some dynamically generated text and some random circles...</p>";
?>

<script type="text/php">
$max_x = $pdf->get_width() - 50;
$max_y = $pdf->get_height() - 50; 
for ( $i = 0; $i < 30; $i++) {
  $pdf->circle(rand(50, $max_x), rand(50, $max_y), rand(10, 70),
               array(rand()/getrandmax(), rand()/getrandmax(), rand()/getrandmax()),
               rand(1,40));
}
</script>
<?php
echo "<p>Current PHP version: " . phpversion() . ".  ";
echo "Today is " . strftime("%A") . " the " . strftime("%e").date("S").strftime(" of %B, %Y %T") . "</p>";

?>
</body> </html>
