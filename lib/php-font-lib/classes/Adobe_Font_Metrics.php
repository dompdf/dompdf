<?php
/**
 * @package php-font-lib
 * @link    https://github.com/PhenX/php-font-lib
 * @author  Fabien Ménager <fabien.menager@gmail.com>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */

require_once dirname(__FILE__) . "/Encoding_Map.php";

/**
 * Adobe Font Metrics file creation utility class.
 * 
 * @package php-font-lib
 */
class Adobe_Font_Metrics {
  private $f;
  
  /**
   * @var Font_TrueType
   */
  private $font;
  
  function __construct(Font_TrueType $font) {
    $this->font = $font;
  }
  
  function write($file, $encoding = null){
    $map_data = array();

    if ($encoding) {
      $encoding = preg_replace("/[^a-z0-9-_]/", "", $encoding);
      $map_file = dirname(__FILE__)."/../maps/$encoding.map";
      if (!file_exists($map_file)) {
        throw new Exception("Unkown encoding ($encoding)");
      }
      
      $map = new Encoding_Map($map_file);
      $map_data = $map->parse();
    }
    
    $this->f = fopen($file, "w+");
    
    $font = $this->font;
    
    $this->startSection("FontMetrics", 4.1);
    $this->addPair("Notice", "Converted by PHP-font-lib");
    $this->addPair("Comment", "https://github.com/PhenX/php-font-lib");
    
    $encoding_scheme = ($encoding ? $encoding : "FontSpecific");
    $this->addPair("EncodingScheme", $encoding_scheme);
    
    $records = $font->getData("name", "records");
    foreach($records as $id => $record) {
      if (!isset(Font_Table_name::$nameIdCodes[$id]) || preg_match("/[\r\n]/", $record->string)) {
        continue;
      }
      
      $this->addPair(Font_Table_name::$nameIdCodes[$id], $record->string);
    }
    
    $os2 = $font->getData("OS/2");
    $this->addPair("Weight", ($os2["usWeightClass"] > 400 ? "Bold" : "Medium"));
    
    $post = $font->getData("post");
    $this->addPair("ItalicAngle",        $post["italicAngle"]);
    $this->addPair("IsFixedPitch",      ($post["isFixedPitch"] ? "true" : "false"));
    $this->addPair("UnderlineThickness", $font->normalizeFUnit($post["underlineThickness"]));
    $this->addPair("UnderlinePosition",  $font->normalizeFUnit($post["underlinePosition"]));
    
    $hhea = $font->getData("hhea");
    
    if (isset($hhea["ascent"])) {
      $this->addPair("FontHeightOffset",  $font->normalizeFUnit($hhea["lineGap"]));
      $this->addPair("Ascender",  $font->normalizeFUnit($hhea["ascent"]));
      $this->addPair("Descender", $font->normalizeFUnit($hhea["descent"]));
    }
    else {
      $this->addPair("FontHeightOffset",  $font->normalizeFUnit($os2["typoLineGap"]));
      $this->addPair("Ascender",  $font->normalizeFUnit($os2["typoAscender"]));
      $this->addPair("Descender", -abs($font->normalizeFUnit($os2["typoDescender"])));
    }
    
    $head = $font->getData("head");
    $this->addArray("FontBBox", array(
      $font->normalizeFUnit($head["xMin"]),
      $font->normalizeFUnit($head["yMin"]),
      $font->normalizeFUnit($head["xMax"]),
      $font->normalizeFUnit($head["yMax"]),
    ));
    
    $glyphIndexArray = $font->getUnicodeCharMap();
    
    if ($glyphIndexArray) {
      $hmtx = $font->getData("hmtx");
      $names = $font->getData("post", "names");
      
      $this->startSection("CharMetrics", count($hmtx));
        
      if ($encoding)  {
        foreach($map_data as $code => $value) {
          list($c, $name) = $value;
          
          if (!isset($glyphIndexArray[$c])) continue;
          
          $g = $glyphIndexArray[$c];
          
          if (!isset($hmtx[$g])) {
            $hmtx[$g] = $hmtx[0];
          }
          
          $this->addMetric(array(
            "C"  => ($code > 255 ? -1 : $code),
            "WX" => $font->normalizeFUnit($hmtx[$g][0]),
            "N"  => $name,
          ));
        }
      }
      else {
        foreach($glyphIndexArray as $c => $g) {
          if (!isset($hmtx[$g])) {
            $hmtx[$g] = $hmtx[0];
          }
          
          $this->addMetric(array(
            "U" => $c,
            "WX" => $font->normalizeFUnit($hmtx[$g][0]),
            "N" => (isset($names[$g]) ? $names[$g] : sprintf("uni%04x", $c)),
            "G" => $g,
          ));
        }
      }
        
      $this->endSection("CharMetrics");
    
      $kern = $font->getData("kern", "subtable");
      $tree = $kern["tree"];
      
      if (!$encoding && is_array($tree)) {
        $this->startSection("KernData");
          $this->startSection("KernPairs", count($tree, COUNT_RECURSIVE) - count($tree));
            
          foreach($tree as $left => $values) {
            if (!is_array($values)) continue;
            if (!isset($glyphIndexArray[$left])) continue;
            
            $left_gid = $glyphIndexArray[$left];
            
            if (!isset($names[$left_gid])) continue;
            
            $left_name = $names[$left_gid];
            
            $this->addLine("");
            
            foreach($values as $right => $value) {
              if (!isset($glyphIndexArray[$right])) continue;
              
              $right_gid = $glyphIndexArray[$right];
            
              if (!isset($names[$right_gid])) continue;
              
              $right_name = $names[$right_gid];
              $this->addPair("KPX", "$left_name $right_name $value");
            }
          }
            
          $this->endSection("KernPairs");
        $this->endSection("KernData");
      }
    }
      
    $this->endSection("FontMetrics");
  }
  
  function addLine($line) {
    fwrite($this->f, "$line\n");
  }
  
  function addPair($key, $value) {
    $this->addLine("$key $value");
  }
  
  function addArray($key, $array) {
    $this->addLine("$key ".implode(" ", $array));
  }
  
  function addMetric($data) {
    $array = array();
    foreach($data as $key => $value) {
      $array[] = "$key $value";
    }
    $this->addLine(implode(" ; ", $array));
  }

  function startSection($name, $value = "") {
    $this->addLine("Start$name $value");
  }
  
  function endSection($name) {
    $this->addLine("End$name");
  }
}
