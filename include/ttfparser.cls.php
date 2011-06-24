<?php
/*******************************************************************************
* Utility to parse TTF font files                                              *
*                                                                              *
* Version: 1.0                                                                 *
* Date:    2011-06-18                                                          *
* Author:  Olivier PLATHEY                                                     *
*******************************************************************************/

class TTFParser
{
  var $f;
  var $tables;
  
  // head
  var $version;
  var $fontRevision;
  var $checkSumAdjustment;
  var $unitsPerEm;
  var $xMin, $yMin, $xMax, $yMax;
  var $numberOfHMetrics;
  
  var $numGlyphs;
  var $postScriptName;
  var $Embeddable;
  var $Bold;
  var $typoAscender;
  var $typoDescender;
  var $capHeight;
  var $italicAngle;
  var $underlinePosition;
  var $underlineThickness;
  var $isFixedPitch;
  
  var $widths;
  var $widthsUnicode;
  var $charToGlyph;
  var $glyphToChar;

  function Parse($file)
  {
    $this->f = fopen($file, 'rb');
    if(!$this->f)
      $this->Error('Can\'t open file: '.$file);

    $version = $this->Read(4);
    if($version=='OTTO')
      $this->Error('OpenType fonts based on PostScript outlines are not supported');
    if($version!="\x00\x01\x00\x00")
      $this->Error('Unrecognized file format');
    $numTables = $this->ReadUShort();
    $this->Skip(3*2); // searchRange, entrySelector, rangeShift
    $this->tables = array();
    for($i=0;$i<$numTables;$i++)
    {
      $tag = $this->Read(4);
      $this->Skip(4); // checkSum
      $offset = $this->ReadULong();
      $this->Skip(4); // length
      $this->tables[$tag] = $offset;
    }

    $this->ParseHead();
    $this->ParseHhea();
    $this->ParseMaxp();
    $this->ParseCmap();
    $this->ParseHmtx();
    $this->ParseName();
    $this->ParseOS2();
    $this->ParsePost();

    fclose($this->f);
  }
  
  function normalizeMetric($value) {
    return round($value * (1000 / $this->unitsPerEm));
  }

  function ParseHead()
  {
    $this->Seek('head');
    $this->Skip(3*4); // version, fontRevision, checkSumAdjustment
    
    $magicNumber = $this->ReadULong();
    if($magicNumber!=0x5F0F3CF5)
      $this->Error('Incorrect magic number');
      
    $this->Skip(2); // flags
    $this->unitsPerEm = $this->ReadUShort();
    $this->Skip(2*8); // created, modified
    $this->xMin = $this->normalizeMetric($this->ReadShort());
    $this->yMin = $this->normalizeMetric($this->ReadShort());
    $this->xMax = $this->normalizeMetric($this->ReadShort());
    $this->yMax = $this->normalizeMetric($this->ReadShort());
  }

  function ParseHhea()
  {
    $this->Seek('hhea');
    $this->Skip(4);
    $this->ascent = $this->normalizeMetric($this->ReadShort());
    $this->descent = $this->normalizeMetric($this->ReadShort());
    $this->Skip(13*2);
    $this->numberOfHMetrics = $this->ReadUShort();
  }

  function ParseMaxp()
  {
    $this->Seek('maxp');
    $this->Skip(4);
    $this->numGlyphs = $this->ReadUShort();
  }

  function ParseHmtx()
  {
    $this->Seek('hmtx');
    $this->widths = array();
    for($i=0;$i<$this->numberOfHMetrics;$i++)
    {
      $advanceWidth = $this->ReadUShort();
      $this->Skip(2); // lsb
      
      if ($advanceWidth > 0) {
        $this->widths[$i] = $this->normalizeMetric($advanceWidth);
        
        if (isset($this->glyphToChar[$i])) {
          $c = $this->glyphToChar[$i];
          $this->widthsUnicode[$c] = $this->normalizeMetric($advanceWidth);
        }
      }
    }
    if($this->numberOfHMetrics<$this->numGlyphs)
    {
      $lastWidth = $this->widths[$this->numberOfHMetrics-1];
      $this->widths = array_pad($this->widths, $this->numGlyphs, $lastWidth);
    }
  }

  function ParseCmap()
  {
    $this->Seek('cmap');
    $this->Skip(2); // version
    $numTables = $this->ReadUShort();
    $offset31 = 0;
    for($i=0;$i<$numTables;$i++)
    {
      $platformID = $this->ReadUShort();
      $encodingID = $this->ReadUShort();
      $offset = $this->ReadULong();
      if($platformID==3 && $encodingID==1)
        $offset31 = $offset;
    }
    if($offset31==0)
      $this->Error('No Unicode encoding found');

    $startCount = array();
    $endCount = array();
    $idDelta = array();
    $idRangeOffset = array();
    
    $this->charToGlyph = array();
    $this->glyphToChar = array();
    
    fseek($this->f, $this->tables['cmap']+$offset31, SEEK_SET);
    $format = $this->ReadUShort();
    if($format!=4)
      $this->Error('Unexpected subtable format: '.$format);
    $this->Skip(2*2); // length, language
    $segCount = $this->ReadUShort()/2;
    $this->Skip(3*2); // searchRange, entrySelector, rangeShift
    for($i=0;$i<$segCount;$i++)
      $endCount[$i] = $this->ReadUShort();
    $this->Skip(2); // reservedPad
    for($i=0;$i<$segCount;$i++)
      $startCount[$i] = $this->ReadUShort();
    for($i=0;$i<$segCount;$i++)
      $idDelta[$i] = $this->ReadShort();
    $offset = ftell($this->f);
    for($i=0;$i<$segCount;$i++)
      $idRangeOffset[$i] = $this->ReadUShort();

    for($i=0;$i<$segCount;$i++)
    {
      $c1 = $startCount[$i];
      $c2 = $endCount[$i];
      $d = $idDelta[$i];
      $ro = $idRangeOffset[$i];
      if($ro>0)
        fseek($this->f, $offset+2*$i+$ro, SEEK_SET);
      for($c=$c1;$c<=$c2;$c++)
      {
        if($c==0xFFFF)
          break;
        if($ro>0)
        {
          $gid = $this->ReadUShort();
          if($gid>0)
            $gid += $d;
        }
        else
          $gid = $c+$d;
        if($gid>=65536)
          $gid -= 65536;
        if($gid>0) {
          $this->charToGlyph[$c] = $gid;
          $this->glyphToChar[$gid] = $c;
        }
      }
    }
  }

  function ParseName()
  {
    $this->Seek('name');
    $tableOffset = ftell($this->f);
    $this->postScriptName = '';
    $this->Skip(2); // format
    $count = $this->ReadUShort();
    $stringOffset = $this->ReadUShort();
    for($i=0;$i<$count;$i++)
    {
      $this->Skip(3*2); // platformID, encodingID, languageID
      $nameID = $this->ReadUShort();
      $length = $this->ReadUShort();
      $offset = $this->ReadUShort();
      if($nameID==6)
      {
        // PostScript name
        fseek($this->f, $tableOffset+$stringOffset+$offset, SEEK_SET);
        $s = $this->Read($length);
        $s = str_replace(chr(0), '', $s);
        $s = preg_replace('|[ \[\](){}<>/%]|', '', $s);
        $this->postScriptName = $s;
        break;
      }
    }
    if($this->postScriptName=='')
      $this->Error('PostScript name not found');
  }

  function ParseOS2()
  {
    $this->Seek('OS/2');
    $version = $this->ReadUShort();
    $this->Skip(3*2); // xAvgCharWidth, usWeightClass, usWidthClass
    $fsType = $this->ReadUShort();
    $this->Embeddable = ($fsType!=2) && ($fsType & 0x200)==0;
    $this->Skip(11*2+10+4*4+4);
    $fsSelection = $this->ReadUShort();
    $this->Bold = ($fsSelection & 32)!=0;
    $this->Skip(2*2); // usFirstCharIndex, usLastCharIndex
    $this->typoAscender = $this->normalizeMetric($this->ReadShort());
    $this->typoDescender = $this->normalizeMetric($this->ReadShort());
    if($version>=2)
    {
      $this->Skip(3*2+2*4+2);
      $this->capHeight = $this->normalizeMetric($this->ReadShort());
    }
    else
      $this->capHeight = 0;
  }

  function ParsePost()
  {
    $this->Seek('post');
    $this->Skip(4); // version
    $this->italicAngle = $this->ReadShort();
    $this->Skip(2); // Skip decimal part
    $this->underlinePosition = $this->normalizeMetric($this->ReadShort());
    $this->underlineThickness = $this->normalizeMetric($this->ReadShort());
    $this->isFixedPitch = ($this->ReadULong()!=0);
  }

  function Error($msg)
  {
    if(PHP_SAPI=='cli')
      die("Error: $msg\n");
    else
      die("<b>Error</b>: $msg");
  }

  function Seek($tag)
  {
    if(!isset($this->tables[$tag]))
      $this->Error('Table not found: '.$tag);
    fseek($this->f, $this->tables[$tag], SEEK_SET);
  }

  function Skip($n)
  {
    fseek($this->f, $n, SEEK_CUR);
  }

  function Read($n)
  {
    return fread($this->f, $n);
  }

  function ReadUShort()
  {
    $a = unpack('nn', fread($this->f,2));
    return $a['n'];
  }

  function ReadShort()
  {
    $a = unpack('nn', fread($this->f,2));
    $v = $a['n'];
    if($v>=0x8000)
      $v -= 65536;
    return $v;
  }

  function ReadULong()
  {
    $a = unpack('NN', fread($this->f,4));
    return $a['N'];
  }
}
?>