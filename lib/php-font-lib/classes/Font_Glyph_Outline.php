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
class Font_Glyph_Outline extends Font_Binary_Stream {
  /**
   * @var Font_Table_glyf
   */
  protected $table;

  protected $offset;
  protected $size;

  // Data
  public $numberOfContours;
  public $xMin;
  public $yMin;
  public $xMax;
  public $yMax;

  public $raw;

  /**
   * @param Font_Table_glyf $table
   * @param                 $offset
   * @param                 $size
   *
   * @return Font_Glyph_Outline
   */
  static function init(Font_Table_glyf $table, $offset, $size) {
    $font = $table->getFont();
    $font->seek($offset);

    if ($font->readInt16() > -1) {
      /** @var Font_Glyph_Outline_Simple $glyph */
      $glyph = new Font_Glyph_Outline_Simple($table, $offset, $size);
    }
    else {
      /** @var Font_Glyph_Outline_Composite $glyph */
      $glyph = new Font_Glyph_Outline_Composite($table, $offset, $size);
    }

    $glyph->parse();
    return $glyph;
  }

  /**
   * @return Font_TrueType
   */
  function getFont() {
    return $this->table->getFont();
  }

  function __construct(Font_Table_glyf $table, $offset = null, $size = null) {
    $this->table  = $table;
    $this->offset = $offset;
    $this->size   = $size;
  }

  function parse() {
    $font = $this->getFont();
    $font->seek($this->offset);

    if (!$this->size) {
      return;
    }

    $this->raw = $font->read($this->size);
  }

  function parseData(){
    $font = $this->getFont();
    $font->seek($this->offset);

    $this->numberOfContours = $font->readInt16();
    $this->xMin = $font->readFWord();
    $this->yMin = $font->readFWord();
    $this->xMax = $font->readFWord();
    $this->yMax = $font->readFWord();
  }

  function encode(){
    $font = $this->getFont();
    return $font->write($this->raw, strlen($this->raw));
  }

  function getSVGContours() {
    // Inherit
  }

  function getGlyphIDs(){
    return array();
  }
}

require_once dirname(__FILE__) . "/Font_Glyph_Outline_Simple.php";
require_once dirname(__FILE__) . "/Font_Glyph_Outline_Composite.php";
