<?php
/**
 * @package dompdf
 * @link    http://www.dompdf.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @author  Fabien Ménager <fabien.menager@gmail.com>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */

class CSS_Color {
  static $cssColorNames = array(
    "aliceblue" => "F0F8FF",
    "antiquewhite" => "FAEBD7",
    "aqua" => "00FFFF",
    "aquamarine" => "7FFFD4",
    "azure" => "F0FFFF",
    "beige" => "F5F5DC",
    "bisque" => "FFE4C4",
    "black" => "000000",
    "blanchedalmond" => "FFEBCD",
    "blue" => "0000FF",
    "blueviolet" => "8A2BE2",
    "brown" => "A52A2A",
    "burlywood" => "DEB887",
    "cadetblue" => "5F9EA0",
    "chartreuse" => "7FFF00",
    "chocolate" => "D2691E",
    "coral" => "FF7F50",
    "cornflowerblue" => "6495ED",
    "cornsilk" => "FFF8DC",
    "crimson" => "DC143C",
    "cyan" => "00FFFF",
    "darkblue" => "00008B",
    "darkcyan" => "008B8B",
    "darkgoldenrod" => "B8860B",
    "darkgray" => "A9A9A9",
    "darkgreen" => "006400",
    "darkgrey" => "A9A9A9",
    "darkkhaki" => "BDB76B",
    "darkmagenta" => "8B008B",
    "darkolivegreen" => "556B2F",
    "darkorange" => "FF8C00",
    "darkorchid" => "9932CC",
    "darkred" => "8B0000",
    "darksalmon" => "E9967A",
    "darkseagreen" => "8FBC8F",
    "darkslateblue" => "483D8B",
    "darkslategray" => "2F4F4F",
    "darkslategrey" => "2F4F4F",
    "darkturquoise" => "00CED1",
    "darkviolet" => "9400D3",
    "deeppink" => "FF1493",
    "deepskyblue" => "00BFFF",
    "dimgray" => "696969",
    "dimgrey" => "696969",
    "dodgerblue" => "1E90FF",
    "firebrick" => "B22222",
    "floralwhite" => "FFFAF0",
    "forestgreen" => "228B22",
    "fuchsia" => "FF00FF",
    "gainsboro" => "DCDCDC",
    "ghostwhite" => "F8F8FF",
    "gold" => "FFD700",
    "goldenrod" => "DAA520",
    "gray" => "808080",
    "green" => "008000",
    "greenyellow" => "ADFF2F",
    "grey" => "808080",
    "honeydew" => "F0FFF0",
    "hotpink" => "FF69B4",
    "indianred" => "CD5C5C",
    "indigo" => "4B0082",
    "ivory" => "FFFFF0",
    "khaki" => "F0E68C",
    "lavender" => "E6E6FA",
    "lavenderblush" => "FFF0F5",
    "lawngreen" => "7CFC00",
    "lemonchiffon" => "FFFACD",
    "lightblue" => "ADD8E6",
    "lightcoral" => "F08080",
    "lightcyan" => "E0FFFF",
    "lightgoldenrodyellow" => "FAFAD2",
    "lightgray" => "D3D3D3",
    "lightgreen" => "90EE90",
    "lightgrey" => "D3D3D3",
    "lightpink" => "FFB6C1",
    "lightsalmon" => "FFA07A",
    "lightseagreen" => "20B2AA",
    "lightskyblue" => "87CEFA",
    "lightslategray" => "778899",
    "lightslategrey" => "778899",
    "lightsteelblue" => "B0C4DE",
    "lightyellow" => "FFFFE0",
    "lime" => "00FF00",
    "limegreen" => "32CD32",
    "linen" => "FAF0E6",
    "magenta" => "FF00FF",
    "maroon" => "800000",
    "mediumaquamarine" => "66CDAA",
    "mediumblue" => "0000CD",
    "mediumorchid" => "BA55D3",
    "mediumpurple" => "9370DB",
    "mediumseagreen" => "3CB371",
    "mediumslateblue" => "7B68EE",
    "mediumspringgreen" => "00FA9A",
    "mediumturquoise" => "48D1CC",
    "mediumvioletred" => "C71585",
    "midnightblue" => "191970",
    "mintcream" => "F5FFFA",
    "mistyrose" => "FFE4E1",
    "moccasin" => "FFE4B5",
    "navajowhite" => "FFDEAD",
    "navy" => "000080",
    "oldlace" => "FDF5E6",
    "olive" => "808000",
    "olivedrab" => "6B8E23",
    "orange" => "FFA500",
    "orangered" => "FF4500",
    "orchid" => "DA70D6",
    "palegoldenrod" => "EEE8AA",
    "palegreen" => "98FB98",
    "paleturquoise" => "AFEEEE",
    "palevioletred" => "DB7093",
    "papayawhip" => "FFEFD5",
    "peachpuff" => "FFDAB9",
    "peru" => "CD853F",
    "pink" => "FFC0CB",
    "plum" => "DDA0DD",
    "powderblue" => "B0E0E6",
    "purple" => "800080",
    "red" => "FF0000",
    "rosybrown" => "BC8F8F",
    "royalblue" => "4169E1",
    "saddlebrown" => "8B4513",
    "salmon" => "FA8072",
    "sandybrown" => "F4A460",
    "seagreen" => "2E8B57",
    "seashell" => "FFF5EE",
    "sienna" => "A0522D",
    "silver" => "C0C0C0",
    "skyblue" => "87CEEB",
    "slateblue" => "6A5ACD",
    "slategray" => "708090",
    "slategrey" => "708090",
    "snow" => "FFFAFA",
    "springgreen" => "00FF7F",
    "steelblue" => "4682B4",
    "tan" => "D2B48C",
    "teal" => "008080",
    "thistle" => "D8BFD8",
    "tomato" => "FF6347",
    "turquoise" => "40E0D0",
    "violet" => "EE82EE",
    "wheat" => "F5DEB3",
    "white" => "FFFFFF",
    "whitesmoke" => "F5F5F5",
    "yellow" => "FFFF00",
    "yellowgreen" => "9ACD32",
  );

  static function parse($colour) {
    if ( is_array($colour) ) {
      // Assume the array has the right format...
      // FIXME: should/could verify this.
      return $colour;
    }
    
    static $cache = array();
    
    $colour = strtolower($colour);
    
    if ( isset($cache[$colour]) ) {
      return $cache[$colour];
    }
      
    if ( in_array($colour, array("transparent", "inherit")) ) {
      return $cache[$colour] = $colour;
    }
    
    if ( isset(self::$cssColorNames[$colour]) ) {
      return $cache[$colour] = self::getArray(self::$cssColorNames[$colour]);
    }
    
    $length = mb_strlen($colour);
    
    // #rgb format
    if ( $length == 4 && $colour[0] === "#" ) {
      return $cache[$colour] = self::getArray($colour[1].$colour[1].$colour[2].$colour[2].$colour[3].$colour[3]);
    }

    // #rrggbb format
    else if ( $length == 7 && $colour[0] === "#" ) {
      return $cache[$colour] = self::getArray(mb_substr($colour, 1, 6));
    }
    
    // rgb( r,g,b ) format
    else if ( mb_strpos($colour, "rgb") !== false ) {
      $i = mb_strpos($colour, "(");
      $j = mb_strpos($colour, ")");
      
      // Bad colour value
      if ( $i === false || $j === false ) {
        return null;
      }
      
      $triplet = explode(",", mb_substr($colour, $i+1, $j-$i-1));

      if ( count($triplet) != 3 ) {
        return null;
      }
      
      foreach (array_keys($triplet) as $c) {
        $triplet[$c] = trim($triplet[$c]);
        
        if ( $triplet[$c][mb_strlen($triplet[$c]) - 1] === "%" ) {
          $triplet[$c] = round($triplet[$c] * 2.55);
        }
      }
      
      return $cache[$colour] = self::getArray(vsprintf("%02X%02X%02X", $triplet));
    
    }
    
    // cmyk( c,m,y,k ) format
    // http://www.w3.org/TR/css3-gcpm/#cmyk-colors
    else if ( mb_strpos($colour, "cmyk") !== false ) {
      $i = mb_strpos($colour, "(");
      $j = mb_strpos($colour, ")");
      
      // Bad colour value
      if ( $i === false || $j === false ) {
        return null;
      }

      $values = explode(",", mb_substr($colour, $i+1, $j-$i-1));

      if ( count($values) != 4 ) {
        return null;
      }
      
      foreach ($values as &$c) {
        $c = floatval(trim($c));
        if ($c > 1.0) $c = 1.0;
        if ($c < 0.0) $c = 0.0;
      }
      
      return $cache[$colour] = self::getArray($values);
    }
  }
  
  static function getArray($colour) {
    $c = array(null, null, null, null, "hex" => null);
    
    if (is_array($colour)) {
      $c = $colour;
      $c["c"] = $c[0];
      $c["m"] = $c[1];
      $c["y"] = $c[2];
      $c["k"] = $c[3];
      $c["hex"] = "cmyk($c[0],$c[1],$c[2],$c[3])";
    }
    else {
      $c[0] = hexdec(mb_substr($colour, 0, 2)) / 0xff;
      $c[1] = hexdec(mb_substr($colour, 2, 2)) / 0xff;
      $c[2] = hexdec(mb_substr($colour, 4, 2)) / 0xff;
      $c["r"] = $c[0];
      $c["g"] = $c[1];
      $c["b"] = $c[2];
      $c["hex"] = "#$colour";
    }
    
    return $c;
  }
}
