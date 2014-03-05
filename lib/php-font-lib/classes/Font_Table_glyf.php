<?php
/**
 * @package php-font-lib
 * @link    https://github.com/PhenX/php-font-lib
 * @author  Fabien Ménager <fabien.menager@gmail.com>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */

require_once dirname(__FILE__) . "/Font_Glyph_Outline.php";

/**
 * `glyf` font table.
 *
 * @package php-font-lib
 * @property Font_Glyph_Outline[] $data
 */
class Font_Table_glyf extends Font_Table {
  protected function _parse(){
    $font = $this->getFont();
    $offset = $font->pos();

    $loca = $font->getData("loca");
    $real_loca = array_slice($loca, 0, -1); // Not the last dummy loca entry

    $data = array();

    foreach($real_loca as $gid => $location) {
      $_offset = $offset + $loca[$gid];
      $_size   = $loca[$gid+1] - $loca[$gid];
      $data[$gid] = Font_Glyph_Outline::init($this, $_offset, $_size);
    }

    $this->data = $data;
  }

  public function getGlyphIDs($gids = array()){
    $glyphIDs = array();

    foreach ($gids as $_gid) {
      $_glyph = $this->data[$_gid];
      $glyphIDs = array_merge($glyphIDs, $_glyph->getGlyphIDs());
    }

    return array_unique(array_merge($gids, $glyphIDs));
  }

  public function toHTML(){
    $max = 160;
    $font = $this->getFont();

    $head = $font->getData("head");
    $head_json = json_encode($head);

    $os2 = $font->getData("OS/2");
    $os2_json  = json_encode($os2);

    $hmtx = $font->getData("hmtx");
    $hmtx_json = json_encode($hmtx);

    $names = $font->getData("post", "names");
    $glyphIndexArray = array_flip($font->getUnicodeCharMap());

    $width  = (abs($head["xMin"]) + $head["xMax"]);
    $height = (abs($head["yMin"]) + $head["yMax"]);

    $ratio = 1;
    if ($width > $max || $height > $max) {
      $ratio = max($width, $height) / $max;
      $width  = round($width/$ratio);
      $height = round($height/$ratio);
    }

    $n = 500;

    $s = "<h3>"."Only the first $n simple glyphs are shown (".count($this->data)." total)
    <div class='glyph-view simple'>Simple glyph</div>
    <div class='glyph-view composite'>Composite glyph</div>
    Zoom: <input type='range' value='100' max='400' onchange='Glyph.resize(this.value)' />
    </h3>
    <script>
      Glyph.ratio  = $ratio;
      Glyph.head   = $head_json;
      Glyph.os2    = $os2_json;
      Glyph.hmtx   = $hmtx_json;
      Glyph.width  = $width;
      Glyph.height = $height;
    </script>";

    foreach($this->data as $g => $glyph) {
      if ($n-- <= 0) {
        break;
      }

      $glyph->parseData();

      $shape = array(
        "SVGContours" => $glyph->getSVGContours(),
        "xMin" => $glyph->xMin,
        "yMin" => $glyph->yMin,
        "xMax" => $glyph->xMax,
        "yMax" => $glyph->yMax,
      );
      $shape_json = json_encode($shape);

      $type = ($glyph instanceof Font_Glyph_Outline_Simple ? "simple" : "composite");
      $char = isset($glyphIndexArray[$g]) ? $glyphIndexArray[$g] : 0;
      $name = isset($names[$g]) ? $names[$g] : sprintf("uni%04x", $char);
      $char = $char ? "&#{$glyphIndexArray[$g]};" : "";

      $s .= "<div class='glyph-view $type' id='glyph-$g'>
              <span class='glyph-id'>$g</span> 
              <span class='char'>$char</span>
              <span class='char-name'>$name</span>
              ";

      if ($type == "composite") {
        foreach ($glyph->getGlyphIDs() as $_id) {
          $s .= "<a href='#glyph-$_id' class='glyph-component-id'>$_id</a> ";
        }
      }

      $s .= "<br />
            <canvas width='$width' height='$height' id='glyph-canvas-$g'></canvas>
            </div>
            <script>Glyph.glyphs.push([$g,$shape_json]);</script>";
    }

    return $s;
  }


  protected function _encode() {
    $font = $this->getFont();
    $subset = $font->getSubset();
    $data = $this->data;

    $loca = array();

    $length = 0;
    foreach($subset as $gid) {
      $loca[] = $length;
      $length += $data[$gid]->encode();
    }

    $loca[] = $length; // dummy loca
    $font->getTableObject("loca")->data = $loca;

    return $length;
  }
}