<html>
<style>
table { margin: auto; }
td {
  font-size: 0.8em;
  padding: 4pt;
  text-align: center;
  font-family: sans-serif;
}
</style>
<body>
<table>
<thead>
<tr>
<td colspan="20">Header</td>
</tr>
</thead>
<tfoot>
<tr>
<td colspan="20">Footer</td>
</tr>
</tfoot>
<?php
$i_max = 40;
$j_max = 20;

for ( $i = 1; $i <= $i_max; $i++): ?>
<tr>
<?php
for ( $j = 1; $j <= $j_max; $j++) {
  $r = (int)(255*$i / $i_max);
  $b = (int)(255*$j / $j_max);
  $g = (int)(255*($i + $j)/($i_max + $j_max));
  $c = "black;";
  $bg = "rgb($r,$g,$b)";
  echo "<td style=\"color: $c; background-color: $bg;\">" . ($i * $j) . "</td>\n";
}
?>
</tr>
<?php endfor; ?>
</table>
</body>
</html>