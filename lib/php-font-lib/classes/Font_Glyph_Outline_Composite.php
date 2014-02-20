<?php
/**
 * @package php-font-lib
 * @link    https://github.com/PhenX/php-font-lib
 * @author  Fabien Ménager <fabien.menager@gmail.com>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 * @version $Id: Font_Table_glyf.php 46 2012-04-02 20:22:38Z fabien.menager $
 */

require_once dirname(__FILE__) . "/Font_Glyph_Outline_Component.php";

/**
 * Composite glyph outline
 *
 * @package php-font-lib
 */
class Font_Glyph_Outline_Composite extends Font_Glyph_Outline {
  const ARG_1_AND_2_ARE_WORDS    = 0x0001;
  const ARGS_ARE_XY_VALUES       = 0x0002;
  const ROUND_XY_TO_GRID         = 0x0004;
  const WE_HAVE_A_SCALE          = 0x0008;
  const MORE_COMPONENTS          = 0x0020;
  const WE_HAVE_AN_X_AND_Y_SCALE = 0x0040;
  const WE_HAVE_A_TWO_BY_TWO     = 0x0080;
  const WE_HAVE_INSTRUCTIONS     = 0x0100;
  const USE_MY_METRICS           = 0x0200;
  const OVERLAP_COMPOUND         = 0x0400;

  /**
   * @var Font_Glyph_Outline_Component[]
   */
  public $components = array();

  function getGlyphIDs(){
    if (empty($this->components)) {
      $this->parseData();
    }

    $glyphIDs = array();
    foreach ($this->components as $_component) {
      $glyphIDs[] = $_component->glyphIndex;

      $_glyph = $this->table->data[$_component->glyphIndex];
      $glyphIDs = array_merge($glyphIDs, $_glyph->getGlyphIDs());
    }

    return $glyphIDs;
  }

  /*function parse() {
    //$this->parseData();
  }*/

  function parseData(){
    parent::parseData();

    $font = $this->getFont();

    do {
      $flags      = $font->readUInt16();
      $glyphIndex = $font->readUInt16();

      $a = 1.0; $b = 0.0;
      $c = 0.0; $d = 1.0;
      $e = 0.0; $f = 0.0;

      $point_compound  = null;
      $point_component = null;

      $instructions = null;

      if ($flags & self::ARG_1_AND_2_ARE_WORDS) {
        if ($flags & self::ARGS_ARE_XY_VALUES) {
          $e = $font->readInt16();
          $f = $font->readInt16();
        }
        else {
          $point_compound  = $font->readUInt16();
          $point_component = $font->readUInt16();
        }
      }
      else {
        if ($flags & self::ARGS_ARE_XY_VALUES) {
          $e = $font->readInt8();
          $f = $font->readInt8();
        }
        else {
          $point_compound  = $font->readUInt8();
          $point_component = $font->readUInt8();
        }
      }

      if ($flags & self::WE_HAVE_A_SCALE) {
        $a = $d = $font->readInt16();
      }
      elseif ($flags & self::WE_HAVE_AN_X_AND_Y_SCALE) {
        $a = $font->readInt16();
        $d = $font->readInt16();
      }
      elseif ($flags & self::WE_HAVE_A_TWO_BY_TWO) {
        $a = $font->readInt16();
        $b = $font->readInt16();
        $c = $font->readInt16();
        $d = $font->readInt16();
      }

      //if ($flags & self::WE_HAVE_INSTRUCTIONS) {
      //
      //}

      $component = new Font_Glyph_Outline_Component();
      $component->flags = $flags;
      $component->glyphIndex = $glyphIndex;
      $component->a = $a;
      $component->b = $b;
      $component->c = $c;
      $component->d = $d;
      $component->e = $e;
      $component->f = $f;
      $component->point_compound = $point_compound;
      $component->point_component = $point_component;
      $component->instructions = $instructions;

      $this->components[] = $component;

    } while ($flags & self::MORE_COMPONENTS);
  }

  function encode(){
    $font = $this->getFont();

    $gids = $font->getSubset();

    $size  = $font->writeInt16(-1);
    $size += $font->writeFWord($this->xMin);
    $size += $font->writeFWord($this->yMin);
    $size += $font->writeFWord($this->xMax);
    $size += $font->writeFWord($this->yMax);

    foreach ($this->components as $_i => $_component) {
      $flags = 0;
      if ($_component->point_component === null && $_component->point_compound === null) {
        $flags |= self::ARGS_ARE_XY_VALUES;

        if (abs($_component->e) > 0x7F || abs($_component->f) > 0x7F) {
          $flags |= self::ARG_1_AND_2_ARE_WORDS;
        }
      }
      elseif ($_component->point_component > 0xFF || $_component->point_compound > 0xFF) {
        $flags |= self::ARG_1_AND_2_ARE_WORDS;
      }

      if ($_component->b == 0 && $_component->c == 0) {
        if ($_component->a == $_component->d) {
          if ($_component->a != 1.0) {
            $flags |= self::WE_HAVE_A_SCALE;
          }
        }
        else {
          $flags |= self::WE_HAVE_AN_X_AND_Y_SCALE;
        }
      }
      else {
        $flags |= self::WE_HAVE_A_TWO_BY_TWO;
      }

      if ($_i < count($this->components)-1) {
        $flags |= self::MORE_COMPONENTS;
      }

      $size += $font->writeUInt16($flags);

      $new_gid = array_search($_component->glyphIndex, $gids);
      $size += $font->writeUInt16($new_gid);

      if ($flags & self::ARG_1_AND_2_ARE_WORDS) {
        if ($flags & self::ARGS_ARE_XY_VALUES) {
          $size += $font->writeInt16($_component->e);
          $size += $font->writeInt16($_component->f);
        }
        else {
          $size += $font->writeUInt16($_component->point_compound);
          $size += $font->writeUInt16($_component->point_component);
        }
      }
      else {
        if ($flags & self::ARGS_ARE_XY_VALUES) {
          $size += $font->writeInt8($_component->e);
          $size += $font->writeInt8($_component->f);
        }
        else {
          $size += $font->writeUInt8($_component->point_compound);
          $size += $font->writeUInt8($_component->point_component);
        }
      }

      if ($flags & self::WE_HAVE_A_SCALE) {
        $size += $font->writeInt16($_component->a);
      }
      elseif ($flags & self::WE_HAVE_AN_X_AND_Y_SCALE) {
        $size += $font->writeInt16($_component->a);
        $size += $font->writeInt16($_component->d);
      }
      elseif ($flags & self::WE_HAVE_A_TWO_BY_TWO) {
        $size += $font->writeInt16($_component->a);
        $size += $font->writeInt16($_component->b);
        $size += $font->writeInt16($_component->c);
        $size += $font->writeInt16($_component->d);
      }
    }

    return $size;
  }

  public function getSVGContours(){
    $contours = array();

    /** @var Font_Table_glyf $glyph_data */
    $glyph_data = $this->getFont()->getTableObject("glyf");

    /** @var Font_Glyph_Outline[] $glyphs */
    $glyphs = $glyph_data->data;

    foreach ($this->components as $component) {
      $contours[] = array(
        "contours"  => $glyphs[$component->glyphIndex]->getSVGContours(),
        "transform" => $component->getMatrix(),
      );
    }

    return $contours;
  }
}