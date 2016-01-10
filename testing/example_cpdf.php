<?php

//--------------------------------------------------
// Start

	require_once('../lib/class.pdf.php');

	$pageWidth = 300;
	$pageHeight = 100;
	$fontSize = 10;

	$pdf = new cpdf(array(
			0,
			0,
			$pageWidth,
			$pageHeight,
		), true);

//--------------------------------------------------
// Page 1

	$pdf->selectFont('../lib/fonts/Helvetica.afm');

	$pdf->addText(10, ($pageHeight - $fontSize - 10), $fontSize, 'Page 1');

	$pdf->selectFont('../lib/fonts/DejaVuSerif-Italic');

	$pdf->addText(10, ($fontSize + 10), $fontSize, 'Italic Text');

//--------------------------------------------------
// Page 2

	$pdf->newDocument(array(
			0,
			0,
			$pageHeight,
			$pageHeight,
		));

	$pdf->selectFont('../lib/fonts/DejaVuSerifCondensed-Bold');

	$pdf->addText(10, ($pageHeight - $fontSize - 10), $fontSize, 'Page 2 Bold');

//--------------------------------------------------
// Page 3

	$pdf->newPage();

	$pdf->selectFont('../lib/fonts/Helvetica.afm');

	$pdf->addText(10, ($pageHeight - $fontSize - 10), $fontSize, 'Page 3');

	$pdf->selectFont('../lib/fonts/DejaVuSerifCondensed-Bold');

	$pdf->addText(10, ($fontSize + 10), $fontSize, 'Bold Text');

//--------------------------------------------------
// Output

	$pdf->stream(array('Attachment' => false));
	exit();

?>