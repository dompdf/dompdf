<?php
/**
 * @package php-font-lib
 * @link    https://github.com/PhenX/php-font-lib
 * @author  Fabien Ménager <fabien.menager@gmail.com>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */

/**
 * `OS/2` font table.
 * 
 * @package php-font-lib
 */
class Font_Table_os2 extends Font_Table {
  protected $def = array(
    "version"             => self::uint16,
    "xAvgCharWidth"       => self::int16,
    "usWeightClass"       => self::uint16,
    "usWidthClass"        => self::uint16,
    "fsType"              => self::int16,
    "ySubscriptXSize"     => self::int16,
    "ySubscriptYSize"     => self::int16,
    "ySubscriptXOffset"   => self::int16,
    "ySubscriptYOffset"   => self::int16,
    "ySuperscriptXSize"   => self::int16,
    "ySuperscriptYSize"   => self::int16,
    "ySuperscriptXOffset" => self::int16,
    "ySuperscriptYOffset" => self::int16,
    "yStrikeoutSize"      => self::int16,
    "yStrikeoutPosition"  => self::int16,
    "sFamilyClass"        => self::int16,
    "panose"              => array(self::uint8, 10),
    "ulCharRange"         => array(self::uint32, 4),
    "achVendID"           => array(self::char,   4),
    "fsSelection"         => self::uint16,
    "fsFirstCharIndex"    => self::uint16,
    "fsLastCharIndex"     => self::uint16,
    "typoAscender"        => self::int16,
    "typoDescender"       => self::int16,
    "typoLineGap"         => self::int16,
    "winAscent"           => self::int16,
    "winDescent"          => self::int16,
  );
}