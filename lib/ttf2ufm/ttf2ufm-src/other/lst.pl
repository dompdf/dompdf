#!/usr/bin/perl
#
# script to create HTML file with character table
# in plain, italic, bold, bold-italic
#
# see COPYRIGHT
#

# width of tables
$step=16;

# commands to enable and disable the font modes
# (the fastest changing is first)
@matrix = (
	[ "Roman", "Italic", "</i>", "<i>" ],
	[ "Medium", "Bold", "</b>", "<b>" ],
	[ "Variable", "Fixed", "</tt>", "<tt>" ],
);

sub printall
{
	local $i, $j;

	printf("<table border=\"0\" >\n");
	for($j=32; $j<256; $j+=$step) {
		printf("<tr>\n");
		for $i ($j..$j+$step-1) {
			$c=chr($i);
			if($c eq "<") {
				$c="&lt;";
			} elsif($c eq ">") {
				$c="&gt;";
			}
			printf("<td><font color=\"gray\">%03d</font></td><td>\n", $i);
			printf("<font color=\"white\">%s%s%s</font>\n", $enmode, $c, $dismode);
			printf("</td>\n");
		}
		printf("</tr>\n");
	}
	printf("</table><p>\n");
}

printf("<HTML><HEAD></HEAD><BODY bgcolor=\"black\">\n<font color=\"white\"><p>\n");

for $mask (0.. (1<<@matrix)-1) {
	#printf("<table><tr>");
	$mode = $enmode = $dismode = "";
	for $bit (0.. $#matrix) {
		$val = ($mask >> $bit) & 1;
		$mode = $matrix[$bit]->[$val] . "<br>" . $mode;
		if( $val ) {
			$enmode = $matrix[$bit]->[3] . $enmode;
			$dismode = $dismode . $matrix[$bit]->[2];
		}
		#printf("=== %d %s %s %s\n", $val, $mode, $enmode, $dismode);
	}
	#printf("%x %s %s %s\n", $mask, $mode, $enmode, $dismode);
	printf("<table border=\"0\"><tr><td>\n");
	&printall();
	printf("</td><td valign=top><font size=\"+1\" color=\"yellow\"><b>\n");
	printf("%s\n", $mode);
	printf("</b></font></td></tr></table>\n");
}

printf("</font></BODY></HTML>\n");
