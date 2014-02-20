<?php
/**
 * @package php-font-lib
 * @link    https://github.com/PhenX/php-font-lib
 * @author  Fabien Ménager <fabien.menager@gmail.com>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 * @version $Id: Font_Table_glyf.php 46 2012-04-02 20:22:38Z fabien.menager $
 */

/**
 * `glyf` font table.
 * 
 * @package php-font-lib
 */
class Font_Glyph_Outline_Simple extends Font_Glyph_Outline {
  const ON_CURVE       = 0x01;
  const X_SHORT_VECTOR = 0x02;
  const Y_SHORT_VECTOR = 0x04;
  const REPEAT         = 0x08;
  const THIS_X_IS_SAME = 0x10;
  const THIS_Y_IS_SAME = 0x20;
  
  public $instructions;
  public $points;
  
  function parseData(){
    parent::parseData();
  
    if (!$this->size) {
      return;
    }
  
    $font = $this->getFont();
    
    $noc = $this->numberOfContours;
    
    if ($noc == 0) {
      return;
    }
    
    $endPtsOfContours = $font->r(array(self::uint16, $noc));
    
    $instructionLength = $font->readUInt16();
    $this->instructions = $font->r(array(self::uint8, $instructionLength));
    
    $count = $endPtsOfContours[$noc-1] + 1;
    
    // Flags
    $flags = array();
    for ($index = 0; $index < $count; $index++) {
      $flags[$index] = $font->readUInt8();
      
      if ($flags[$index] & self::REPEAT) {
        $repeats = $font->readUInt8();
        
        for ($i = 1; $i <= $repeats; $i++) {
          $flags[$index+$i] = $flags[$index];
        }
        
        $index += $repeats;
      }
    }
    
    $points = array();
    foreach ($flags as $i => $flag) {
      $points[$i]["onCurve"] = $flag & self::ON_CURVE;
      $points[$i]["endOfContour"] = in_array($i, $endPtsOfContours);
    }
    
    // X Coords
    $x = 0;
    for($i = 0; $i < $count; $i++) {
      $flag = $flags[$i];
      
      if ($flag & self::THIS_X_IS_SAME) {
        if ($flag & self::X_SHORT_VECTOR) {
          $x += $font->readUInt8();
        }
      }
      else {
        if ($flag & self::X_SHORT_VECTOR) {
          $x -= $font->readUInt8();
        }
        else {
          $x += $font->readInt16();
        }
      }
      
      $points[$i]["x"] = $x;
    }
    
    // Y Coords
    $y = 0;
    for($i = 0; $i < $count; $i++) {
      $flag = $flags[$i];
      
      if ($flag & self::THIS_Y_IS_SAME) {
        if ($flag & self::Y_SHORT_VECTOR) {
          $y += $font->readUInt8();
        }
      }
      else {
        if ($flag & self::Y_SHORT_VECTOR) {
          $y -= $font->readUInt8();
        }
        else {
          $y += $font->readInt16();
        }
      }
      
      $points[$i]["y"] = $y;
    }
    
    $this->points = $points;
  }
  
  public function splitSVGPath($path) {
    preg_match_all('/([a-z])|(-?\d+(?:\.\d+)?)/i', $path, $matches, PREG_PATTERN_ORDER);
    return $matches[0];
  }
  
  public function makePoints($path) {
    $path = $this->splitSVGPath($path);
    $l = count($path);
    $i = 0;
    
    $points = array();
    
    while($i < $l) {
      switch($path[$i]) {
        // moveTo
        case "M":
          $points[] = array(
            "onCurve" => true,
            "x"       => $path[++$i],
            "y"       => $path[++$i],
            "endOfContour" => false,
          );
          break;
          
        // lineTo
        case "L":
          $points[] = array(
            "onCurve" => true,
            "x"       => $path[++$i],
            "y"       => $path[++$i],
            "endOfContour" => false,
          );
          break;
        
        // quadraticCurveTo
        case "Q":
          $points[] = array(
            "onCurve" => false,
            "x"       => $path[++$i],
            "y"       => $path[++$i],
            "endOfContour" => false,
          );
          $points[] = array(
            "onCurve" => true,
            "x"       => $path[++$i],
            "y"       => $path[++$i],
            "endOfContour" => false,
          );
          break;
        
        // closePath
        /** @noinspection PhpMissingBreakStatementInspection */
        case "z":
          $points[count($points)-1]["endOfContour"] = true;
        
        default:
          $i++;
          break;
      }
    }
    
    return $points;
  }

  function encode(){
    if (empty($this->points)) {
      return parent::encode();
    }
    
    return $this->size = $this->encodePoints($this->points);
  }

  public function encodePoints($points) {
    $endPtsOfContours = array();
    $flags = array();
    $coords_x = array();
    $coords_y = array();
    
    $last_x = 0;
    $last_y = 0;
    $xMin = $yMin = 0xFFFF;
    $xMax = $yMax = -0xFFFF;
    foreach($points as $i => $point) {
      $flag = 0;
      if ($point["onCurve"]) {
        $flag |= self::ON_CURVE;
      }
      
      if ($point["endOfContour"]) {
        $endPtsOfContours[] = $i;
      }
      
      // Simplified, we could do some optimizations
      if ($point["x"] == $last_x) {
        $flag |= self::THIS_X_IS_SAME;
      }
      else {
        $x = intval($point["x"]);
        $xMin = min($x, $xMin);
        $xMax = max($x, $xMax);
        $coords_x[] = $x-$last_x; // int16
      }
      
      // Simplified, we could do some optimizations
      if ($point["y"] == $last_y) {
        $flag |= self::THIS_Y_IS_SAME;
      }
      else {
        $y = intval($point["y"]);
        $yMin = min($y, $yMin);
        $yMax = max($y, $yMax);
        $coords_y[] = $y-$last_y; // int16
      }
      
      $flags[] = $flag;
      $last_x = $point["x"];
      $last_y = $point["y"];
    }
    
    $font = $this->getFont();
    
    $l = 0;
    $l += $font->writeInt16(count($endPtsOfContours)); // endPtsOfContours
    $l += $font->writeFWord(isset($this->xMin) ? $this->xMin : $xMin); // xMin
    $l += $font->writeFWord(isset($this->yMin) ? $this->yMin : $yMin); // yMin
    $l += $font->writeFWord(isset($this->xMax) ? $this->xMax : $xMax); // xMax
    $l += $font->writeFWord(isset($this->yMax) ? $this->yMax : $yMax); // yMax
    
    // Simple glyf
    $l += $font->w(array(self::uint16, count($endPtsOfContours)), $endPtsOfContours); // endPtsOfContours
    $l += $font->writeUInt16(0); // instructionLength
    $l += $font->w(array(self::uint8, count($flags)), $flags); // flags
    $l += $font->w(array(self::int16, count($coords_x)), $coords_x); // xCoordinates
    $l += $font->w(array(self::int16, count($coords_y)), $coords_y); // yCoordinates
    return $l;
  } 

  public function getSVGContours($points = null){
    $path = "";
    
    if (!$points) {
      if (empty($this->points)) {
        $this->parseData();
      }

      $points = $this->points;
    }
    
    $length = count($points);
    $firstIndex = 0;
    $count = 0;
    
    for($i = 0; $i < $length; $i++) {
      $count++;
      
      if ($points[$i]["endOfContour"]) {
        $path .= $this->getSVGPath($points, $firstIndex, $count);
        $firstIndex = $i + 1;
        $count = 0;
      }
    }
    
    return $path;
  }
  
  protected function getSVGPath($points, $startIndex, $count) {
    $offset = 0;
    $path = "";
    
    while($offset < $count) {
      $point    = $points[ $startIndex +  $offset   %$count ];
      $point_p1 = $points[ $startIndex + ($offset+1)%$count ];
      
      if($offset == 0) {
        $path .= "M{$point['x']},{$point['y']} ";
      }
      
      if ($point["onCurve"]) {
        if ($point_p1["onCurve"]) {
          $path .= "L{$point_p1['x']},{$point_p1['y']} ";
          $offset++;
        }
        else {
          $point_p2 = $points[ $startIndex + ($offset+2)%$count ];
          
          if ($point_p2["onCurve"]){
            $path .= "Q{$point_p1['x']},{$point_p1['y']},{$point_p2['x']},{$point_p2['y']} ";
          } 
          else {
            $path .= "Q{$point_p1['x']},{$point_p1['y']},".$this->midValue($point_p1['x'], $point_p2['x']).",".$this->midValue($point_p1['y'], $point_p2['y'])." ";
          } 
          
          $offset += 2;
        }
      }
      else {
        if ($point_p1["onCurve"]) {
          $path .= "Q{$point['x']},{$point['y']},{$point_p1['x']},{$point_p1['y']} ";
        }
        else {
          $path .= "Q{$point['x']},{$point['y']},".$this->midValue($point['x'], $point_p1['x']).",".$this->midValue($point['y'], $point_p1['y'])." ";
        }
        
        $offset++;
      }
    }
    
    $path .= "z ";
    
    return $path;
  }
  
  function midValue($a, $b){
    return $a + ($b - $a)/2;
  }
}