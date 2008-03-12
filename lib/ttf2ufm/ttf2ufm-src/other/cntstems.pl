#!/usr/bin/perl
#
# Copyright (c) 2000 by Sergey Babkin
# (see COPYRIGHT for full copyright notice)
#
# script to calculate the total number of stems used by the
# glyphs in case if stems are always pushed to the stack and
# never popped (as X11 does)

$insubrs = 0;
$inchars = 0;

while(<>)
{
	if(/\/Subrs/) {
		$insubrs = 1;
	} elsif(/\/CharStrings/) {
		$insubrs = 0;
		$inchars = 1;
	} 
	if($insubrs && /^dup (\d+)/) {
		$cursubr = $1;
		$substems[$cursubr] = 0;
	} elsif (/^dup (\d+) \/(\S+) put/) {
		$codeof{$2} = $1;
	}
	if($inchars) {
		if(/^\/(\S+)\s+\{/) {
			$curchar = $1;
			$charstems = 0;
		} elsif( /endchar/ ) {
			printf("%d:%s\t%d\n", $codeof{$curchar}, $curchar, $charstems);
		} elsif( /(\d+)\s+4\s+callsubr/) {
			$charstems += $substems[$1+0];
		}
	}
	if(/[hv]stem3/) {
		if($insubrs) {
			$substems[$cursubr] += 3;
		} elsif($inchars) {
			$charstems += 3;
		}
	} elsif( /[hv]stem/ ) {
		if($insubrs) {
			$substems[$cursubr]++;
		} elsif($inchars) {
			$charstems++;
		}
	}
}
