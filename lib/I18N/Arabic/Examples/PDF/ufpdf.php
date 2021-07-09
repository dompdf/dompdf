<?php
/*******************************************************************************
* Software: UFPDF, Unicode Free PDF generator                                  *
* Version:  0.1                                                                *
*           based on FPDF 1.52 by Olivier PLATHEY                              *
* Date:     2004-09-01                                                         *
* Author:   Steven Wittens <steven@acko.net>                                   *
* License:  GPL                                                                *
*                                                                              *
* UFPDF is a modification of FPDF to support Unicode through UTF-8.            *
*                                                                              *
*******************************************************************************/

if(!class_exists('UFPDF'))
{
define('UFPDF_VERSION','0.1');

include_once 'fpdf.php';

class UFPDF extends FPDF
{

/*******************************************************************************
*                                                                              *
*                               Public methods                                 *
*                                                                              *
*******************************************************************************/
function UFPDF($orientation='P',$unit='mm',$format='A4')
{
  FPDF::FPDF($orientation, $unit, $format);
}

function GetStringWidth($s)
{
  //Get width of a string in the current font
  $s = (string)$s;
  $codepoints=$this->utf8_to_codepoints($s);
  $cw=&$this->CurrentFont['cw'];
  $w=0;
  foreach($codepoints as $cp)
    $w+=$cw[$cp];
  return $w*$this->FontSize/1000;
}

function AddFont($family,$style='',$file='')
{
  //Add a TrueType or Type1 font
  $family=strtolower($family);
  if($family=='arial')
    $family='helvetica';
  $style=strtoupper($style);
  if($style=='IB')
    $style='BI';
  if(isset($this->fonts[$family.$style]))
    $this->Error('Font already added: '.$family.' '.$style);
  if($file=='')
    $file=str_replace(' ','',$family).strtolower($style).'.php';
  if(defined('FPDF_FONTPATH'))
    $file=FPDF_FONTPATH.$file;
  include($file);
  if(!isset($name))
    $this->Error('Could not include font definition file');
  $i=count($this->fonts)+1;
  $this->fonts[$family.$style]=array('i'=>$i,'type'=>$type,'name'=>$name,'desc'=>$desc,'up'=>$up,'ut'=>$ut,'cw'=>$cw,'file'=>$file,'ctg'=>$ctg);
  if($file)
  {
    if($type=='TrueTypeUnicode')
      $this->FontFiles[$file]=array('length1'=>$originalsize);
    else
      $this->FontFiles[$file]=array('length1'=>$size1,'length2'=>$size2);
  }
}

function Text($x,$y,$txt)
{
  //Output a string
  $s=sprintf('BT %.2f %.2f Td %s Tj ET',$x*$this->k,($this->h-$y)*$this->k,$this->_escapetext($txt));
  if($this->underline and $txt!='')
    $s.=' '.$this->_dounderline($x,$y,$this->GetStringWidth($txt),$txt);
  if($this->ColorFlag)
    $s='q '.$this->TextColor.' '.$s.' Q';
  $this->_out($s);
}

function AcceptPageBreak()
{
  //Accept automatic page break or not
  return $this->AutoPageBreak;
}

function Cell($w,$h=0,$txt='',$border=0,$ln=0,$align='',$fill=0,$link='')
{
  //Output a cell
  $k=$this->k;
  if($this->y+$h>$this->PageBreakTrigger and !$this->InFooter and $this->AcceptPageBreak())
  {
    //Automatic page break
    $x=$this->x;
    $ws=$this->ws;
    if($ws>0)
    {
      $this->ws=0;
      $this->_out('0 Tw');
    }
    $this->AddPage($this->CurOrientation);
    $this->x=$x;
    if($ws>0)
    {
      $this->ws=$ws;
      $this->_out(sprintf('%.3f Tw',$ws*$k));
    }
  }
  if($w==0)
    $w=$this->w-$this->rMargin-$this->x;
  $s='';
  if($fill==1 or $border==1)
  {
    if($fill==1)
      $op=($border==1) ? 'B' : 'f';
    else
      $op='S';
    $s=sprintf('%.2f %.2f %.2f %.2f re %s ',$this->x*$k,($this->h-$this->y)*$k,$w*$k,-$h*$k,$op);
  }
  if(is_string($border))
  {
    $x=$this->x;
    $y=$this->y;
    if(is_int(strpos($border,'L')))
      $s.=sprintf('%.2f %.2f m %.2f %.2f l S ',$x*$k,($this->h-$y)*$k,$x*$k,($this->h-($y+$h))*$k);
    if(is_int(strpos($border,'T')))
      $s.=sprintf('%.2f %.2f m %.2f %.2f l S ',$x*$k,($this->h-$y)*$k,($x+$w)*$k,($this->h-$y)*$k);
    if(is_int(strpos($border,'R')))
      $s.=sprintf('%.2f %.2f m %.2f %.2f l S ',($x+$w)*$k,($this->h-$y)*$k,($x+$w)*$k,($this->h-($y+$h))*$k);
    if(is_int(strpos($border,'B')))
      $s.=sprintf('%.2f %.2f m %.2f %.2f l S ',$x*$k,($this->h-($y+$h))*$k,($x+$w)*$k,($this->h-($y+$h))*$k);
  }
  if($txt!='')
  {
    $width = $this->GetStringWidth($txt);
    if($align=='R')
      $dx=$w-$this->cMargin-$width;
    elseif($align=='C')
      $dx=($w-$width)/2;
    else
      $dx=$this->cMargin;
    if($this->ColorFlag)
      $s.='q '.$this->TextColor.' ';
    $txtstring=$this->_escapetext($txt);
    $s.=sprintf('BT %.2f %.2f Td %s Tj ET',($this->x+$dx)*$k,($this->h-($this->y+.5*$h+.3*$this->FontSize))*$k,$txtstring);
    if($this->underline)
      $s.=' '.$this->_dounderline($this->x+$dx,$this->y+.5*$h+.3*$this->FontSize,$width,$txt);
    if($this->ColorFlag)
      $s.=' Q';
    if($link)
      $this->Link($this->x+$dx,$this->y+.5*$h-.5*$this->FontSize,$width,$this->FontSize,$link);
  }
  if($s)
    $this->_out($s);
  $this->lasth=$h;
  if($ln>0)
  {
    //Go to next line
    $this->y+=$h;
    if($ln==1)
      $this->x=$this->lMargin;
  }
  else
    $this->x+=$w;
}

/*******************************************************************************
*                                                                              *
*                              Protected methods                               *
*                                                                              *
*******************************************************************************/

function _puttruetypeunicode($font) {
  //Type0 Font
  $this->_newobj();
  $this->_out('<</Type /Font');
  $this->_out('/Subtype /Type0');
  $this->_out('/BaseFont /'. $font['name'] .'-UCS');
  $this->_out('/Encoding /Identity-H');
  $this->_out('/DescendantFonts ['. ($this->n + 1) .' 0 R]');
  $this->_out('>>');
  $this->_out('endobj');

  //CIDFont
  $this->_newobj();
  $this->_out('<</Type /Font');
  $this->_out('/Subtype /CIDFontType2');
  $this->_out('/BaseFont /'. $font['name']);
  $this->_out('/CIDSystemInfo <</Registry (Adobe) /Ordering (UCS) /Supplement 0>>');
  $this->_out('/FontDescriptor '. ($this->n + 1) .' 0 R');
  $c = 0;
  foreach ($font['cw'] as $i => $w) {
    $widths .= $i .' ['. $w.'] ';
  }
  $this->_out('/W ['. $widths .']');
  $this->_out('/CIDToGIDMap '. ($this->n + 2) .' 0 R');
  $this->_out('>>');
  $this->_out('endobj');

  //Font descriptor
  $this->_newobj();
  $this->_out('<</Type /FontDescriptor');
  $this->_out('/FontName /'.$font['name']);
  foreach ($font['desc'] as $k => $v) {
    $s .= ' /'. $k .' '. $v;
  }
  if ($font['file']) {
		$s .= ' /FontFile2 '. $this->FontFiles[$font['file']]['n'] .' 0 R';
  }
  $this->_out($s);
  $this->_out('>>');
  $this->_out('endobj');

  //Embed CIDToGIDMap
  $this->_newobj();
  if(defined('FPDF_FONTPATH'))
    $file=FPDF_FONTPATH.$font['ctg'];
  else
    $file=$font['ctg'];
  $size=filesize($file);
  if(!$size)
    $this->Error('Font file not found');
  $this->_out('<</Length '.$size);
	if(substr($file,-2) == '.z')
    $this->_out('/Filter /FlateDecode');
  $this->_out('>>');
  $f = fopen($file,'rb');
  $this->_putstream(fread($f,$size));
  fclose($f);
  $this->_out('endobj');
}

function _dounderline($x,$y,$width,$txt)
{
  //Underline text
  $up=$this->CurrentFont['up'];
  $ut=$this->CurrentFont['ut'];
  $w=$width+$this->ws*substr_count($txt,' ');
  return sprintf('%.2f %.2f %.2f %.2f re f',$x*$this->k,($this->h-($y-$up/1000*$this->FontSize))*$this->k,$w*$this->k,-$ut/1000*$this->FontSizePt);
}

function _textstring($s)
{
  //Convert to UTF-16BE
  $s = $this->utf8_to_utf16be($s);
  //Escape necessary characters
  return '('. strtr($s, array(')' => '\\)', '(' => '\\(', '\\' => '\\\\')) .')';
}

function _escapetext($s)
{
  //Convert to UTF-16BE
  $s = $this->utf8_to_utf16be($s, false);
  //Escape necessary characters
  return '('. strtr($s, array(')' => '\\)', '(' => '\\(', '\\' => '\\\\')) .')';
}

function _putinfo()
{
	$this->_out('/Producer '.$this->_textstring('UFPDF '. UFPDF_VERSION));
	if(!empty($this->title))
		$this->_out('/Title '.$this->_textstring($this->title));
	if(!empty($this->subject))
		$this->_out('/Subject '.$this->_textstring($this->subject));
	if(!empty($this->author))
		$this->_out('/Author '.$this->_textstring($this->author));
	if(!empty($this->keywords))
		$this->_out('/Keywords '.$this->_textstring($this->keywords));
	if(!empty($this->creator))
		$this->_out('/Creator '.$this->_textstring($this->creator));
	$this->_out('/CreationDate '.$this->_textstring('D:'.date('YmdHis')));
}

// UTF-8 to UTF-16BE conversion.
// Correctly handles all illegal UTF-8 sequences.
function utf8_to_utf16be(&$txt, $bom = true) {
  $l = strlen($txt);
  $out = $bom ? "\xFE\xFF" : '';
  for ($i = 0; $i < $l; ++$i) {
    $c = ord($txt{$i});
    // ASCII
    if ($c < 0x80) {
      $out .= "\x00". $txt{$i};
    }
    // Lost continuation byte
    else if ($c < 0xC0) {
      $out .= "\xFF\xFD";
      continue;
    }
    // Multibyte sequence leading byte
    else {
      if ($c < 0xE0) {
        $s = 2;
      }
      else if ($c < 0xF0) {
        $s = 3;
      }
      else if ($c < 0xF8) {
        $s = 4;
      }
      // 5/6 byte sequences not possible for Unicode.
      else {
        $out .= "\xFF\xFD";
        while (ord($txt{$i + 1}) >= 0x80 && ord($txt{$i + 1}) < 0xC0) { ++$i; }
        continue;
      }
      
      $q = array($c);
      // Fetch rest of sequence
      while (ord($txt{$i + 1}) >= 0x80 && ord($txt{$i + 1}) < 0xC0) { ++$i; $q[] = ord($txt{$i}); }
      
      // Check length
      if (count($q) != $s) {
        $out .= "\xFF\xFD";        
        continue;
      }
      
      switch ($s) {
        case 2:
          $cp = (($q[0] ^ 0xC0) << 6) | ($q[1] ^ 0x80);
          // Overlong sequence
          if ($cp < 0x80) {
            $out .= "\xFF\xFD";        
          }
          else {
            $out .= chr($cp >> 8);
            $out .= chr($cp & 0xFF);
          }
          continue;

        case 3:
          $cp = (($q[0] ^ 0xE0) << 12) | (($q[1] ^ 0x80) << 6) | ($q[2] ^ 0x80);
          // Overlong sequence
          if ($cp < 0x800) {
            $out .= "\xFF\xFD";        
          }
          // Check for UTF-8 encoded surrogates (caused by a bad UTF-8 encoder)
          else if ($c > 0xD800 && $c < 0xDFFF) {
            $out .= "\xFF\xFD";
          }
          else {
            $out .= chr($cp >> 8);
            $out .= chr($cp & 0xFF);
          }
          continue;

        case 4:
          $cp = (($q[0] ^ 0xF0) << 18) | (($q[1] ^ 0x80) << 12) | (($q[2] ^ 0x80) << 6) | ($q[3] ^ 0x80);
          // Overlong sequence
          if ($cp < 0x10000) {
            $out .= "\xFF\xFD";
          }
          // Outside of the Unicode range
          else if ($cp >= 0x10FFFF) {
            $out .= "\xFF\xFD";            
          }
          else {
            // Use surrogates
            $cp -= 0x10000;
            $s1 = 0xD800 | ($cp >> 10);
            $s2 = 0xDC00 | ($cp & 0x3FF);
            
            $out .= chr($s1 >> 8);
            $out .= chr($s1 & 0xFF);
            $out .= chr($s2 >> 8);
            $out .= chr($s2 & 0xFF);
          }
          continue;
      }
    }
  }
  return $out;
}

// UTF-8 to codepoint array conversion.
// Correctly handles all illegal UTF-8 sequences.
function utf8_to_codepoints(&$txt) {
  $l = strlen($txt);
  $out = array();
  for ($i = 0; $i < $l; ++$i) {
    $c = ord($txt{$i});
    // ASCII
    if ($c < 0x80) {
      $out[] = ord($txt{$i});
    }
    // Lost continuation byte
    else if ($c < 0xC0) {
      $out[] = 0xFFFD;
      continue;
    }
    // Multibyte sequence leading byte
    else {
      if ($c < 0xE0) {
        $s = 2;
      }
      else if ($c < 0xF0) {
        $s = 3;
      }
      else if ($c < 0xF8) {
        $s = 4;
      }
      // 5/6 byte sequences not possible for Unicode.
      else {
        $out[] = 0xFFFD;
        while (ord($txt{$i + 1}) >= 0x80 && ord($txt{$i + 1}) < 0xC0) { ++$i; }
        continue;
      }
      
      $q = array($c);
      // Fetch rest of sequence
      while (ord($txt{$i + 1}) >= 0x80 && ord($txt{$i + 1}) < 0xC0) { ++$i; $q[] = ord($txt{$i}); }
      
      // Check length
      if (count($q) != $s) {
        $out[] = 0xFFFD;
        continue;
      }
      
      switch ($s) {
        case 2:
          $cp = (($q[0] ^ 0xC0) << 6) | ($q[1] ^ 0x80);
          // Overlong sequence
          if ($cp < 0x80) {
            $out[] = 0xFFFD;
          }
          else {
            $out[] = $cp;
          }
          continue;

        case 3:
          $cp = (($q[0] ^ 0xE0) << 12) | (($q[1] ^ 0x80) << 6) | ($q[2] ^ 0x80);
          // Overlong sequence
          if ($cp < 0x800) {
            $out[] = 0xFFFD;
          }
          // Check for UTF-8 encoded surrogates (caused by a bad UTF-8 encoder)
          else if ($c > 0xD800 && $c < 0xDFFF) {
            $out[] = 0xFFFD;
          }
          else {
            $out[] = $cp;
          }
          continue;

        case 4:
          $cp = (($q[0] ^ 0xF0) << 18) | (($q[1] ^ 0x80) << 12) | (($q[2] ^ 0x80) << 6) | ($q[3] ^ 0x80);
          // Overlong sequence
          if ($cp < 0x10000) {
            $out[] = 0xFFFD;
          }
          // Outside of the Unicode range
          else if ($cp >= 0x10FFFF) {
            $out[] = 0xFFFD;
          }
          else {
            $out[] = $cp;
          }
          continue;
      }
    }
  }
  return $out;
}

//End of class
}

}
?>
