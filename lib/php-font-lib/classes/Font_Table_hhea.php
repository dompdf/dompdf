<?php
/**
 * @package php-font-lib
 * @link    https://github.com/PhenX/php-font-lib
 * @author  Fabien Ménager <fabien.menager@gmail.com>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */

/**
 * `hhea` font table.
 * 
 * @package php-font-lib
 */
class Font_Table_hhea extends Font_Table {
  protected $def = array(
    "version"             => self::Fixed,
    "ascent"              => self::FWord,
    "descent"             => self::FWord,
    "lineGap"             => self::FWord,
    "advanceWidthMax"     => self::uFWord,
    "minLeftSideBearing"  => self::FWord,
    "minRightSideBearing" => self::FWord,
    "xMaxExtent"          => self::FWord,
    "caretSlopeRise"      => self::int16,
    "caretSlopeRun"       => self::int16,
    "caretOffset"         => self::FWord,
                             self::int16,
                             self::int16,
                             self::int16,
                             self::int16,
    "metricDataFormat"    => self::int16,
    "numOfLongHorMetrics" => self::uint16,
  );
  
  function _encode(){
    $font = $this->getFont();
    $this->data["numOfLongHorMetrics"] = count($font->getSubset());
    
    return parent::_encode();
  }
}