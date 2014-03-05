<?php
/**
 * @package php-font-lib
 * @link    https://github.com/PhenX/php-font-lib
 * @author  Fabien Ménager <fabien.menager@gmail.com>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */

require_once dirname(__FILE__) . "/Font_Table_name_Record.php";

/**
 * `name` font table.
 * 
 * @package php-font-lib
 */
class Font_Table_name extends Font_Table {
  private static $header_format = array(
    "format"       => self::uint16,
    "count"        => self::uint16,
    "stringOffset" => self::uint16,
  );

  const NAME_COPYRIGHT          = 0;
  const NAME_NAME               = 1;
  const NAME_SUBFAMILY          = 2;
  const NAME_SUBFAMILY_ID       = 3;
  const NAME_FULL_NAME          = 4;
  const NAME_VERSION            = 5;
  const NAME_POSTSCRIPT_NAME    = 6;
  const NAME_TRADEMARK          = 7;
  const NAME_MANUFACTURER       = 8;
  const NAME_DESIGNER           = 9;
  const NAME_DESCRIPTION        = 10;
  const NAME_VENDOR_URL         = 11;
  const NAME_DESIGNER_URL       = 12;
  const NAME_LICENSE            = 13;
  const NAME_LICENSE_URL        = 14;
  const NAME_PREFERRE_FAMILY    = 16;
  const NAME_PREFERRE_SUBFAMILY = 17;
  const NAME_COMPAT_FULL_NAME   = 18;
  const NAME_SAMPLE_TEXT        = 19;

  static $nameIdCodes = array(
    0  => "Copyright",
    1  => "FontName",
    2  => "FontSubfamily",
    3  => "UniqueID",
    4  => "FullName",
    5  => "Version",
    6  => "PostScriptName",
    7  => "Trademark",
    8  => "Manufacturer",
    9  => "Designer",
    10 => "Description",
    11 => "FontVendorURL",
    12 => "FontDesignerURL",
    13 => "LicenseDescription",
    14 => "LicenseURL",
    // 15
    16 => "PreferredFamily",
    17 => "PreferredSubfamily",
    18 => "CompatibleFullName",
    19 => "SampleText",
  );

  static $platforms = array(
    0 => "Unicode",
    1 => "Macintosh",
    // 2 =>  Reserved
    3 => "Microsoft",
  );

  static $plaformSpecific = array(
    // Unicode
    0 => array(
      0 => "Default semantics",
      1 => "Version 1.1 semantics",
      2 => "ISO 10646 1993 semantics (deprecated)",
      3 => "Unicode 2.0 or later semantics",
    ),

    // Macintosh
    1 => array(
      0 => "Roman",
      1 => "Japanese",
      2 => "Traditional Chinese",
      3 => "Korean",
      4 => "Arabic",
      5 => "Hebrew",
      6 => "Greek",
      7 => "Russian",
      8 => "RSymbol",
      9 => "Devanagari",
      10 => "Gurmukhi",
      11 => "Gujarati",
      12 => "Oriya",
      13 => "Bengali",
      14 => "Tamil",
      15 => "Telugu",
      16 => "Kannada",
      17 => "Malayalam",
      18 => "Sinhalese",
      19 => "Burmese",
      20 => "Khmer",
      21 => "Thai",
      22 => "Laotian",
      23 => "Georgian",
      24 => "Armenian",
      25 => "Simplified Chinese",
      26 => "Tibetan",
      27 => "Mongolian",
      28 => "Geez",
      29 => "Slavic",
      30 => "Vietnamese",
      31 => "Sindhi",
    ),

    // Microsoft
    3 => array(
      0 => "Symbol",
      1 => "Unicode BMP (UCS-2)",
      2 => "ShiftJIS",
      3 => "PRC",
      4 => "Big5",
      5 => "Wansung",
      6 => "Johab",
      //  7 => Reserved
      //  8 => Reserved
      //  9 => Reserved
      10 => "Unicode UCS-4",
    ),
  );
  
  protected function _parse(){
    $font = $this->getFont();
    
    $tableOffset = $font->pos();
    
    $data = $font->unpack(self::$header_format);
    
    $records = array();
    for($i = 0; $i < $data["count"]; $i++) {
      $record = new Font_Table_name_Record();
      $record_data = $font->unpack(Font_Table_name_Record::$format);
      $record->map($record_data);
      
      $records[] = $record;
    }
    
    $names = array();
    foreach($records as $record) {
      $font->seek($tableOffset + $data["stringOffset"] + $record->offset);
      $s = $font->read($record->length);
      $record->string = Font::UTF16ToUTF8($s);
      $names[$record->nameID] = $record;
    }
    
    $data["records"] = $names;
    
    $this->data = $data;
  }
  
  protected function _encode(){
    $font = $this->getFont();

    /** @var Font_Table_name_Record[] $records */
    $records = $this->data["records"];
    $count_records = count($records);
    
    $this->data["count"] = $count_records;
    $this->data["stringOffset"] = 6 + $count_records * 12; // 6 => uint16 * 3, 12 => sizeof self::$record_format
    
    $length = $font->pack(self::$header_format, $this->data);
    
    $offset = 0;
    foreach($records as $record) {
      $record->length = mb_strlen($record->getUTF16(), "8bit");
      $record->offset = $offset;
      $offset += $record->length;
      $length += $font->pack(Font_Table_name_Record::$format, (array)$record);
    }
    
    foreach($records as $record) {
      $str = $record->getUTF16();
      $length += $font->write($str, mb_strlen($str, "8bit"));
    }
    
    return $length;
  }
}