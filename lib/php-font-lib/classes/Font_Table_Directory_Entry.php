<?php
/**
 * @package php-font-lib
 * @link    https://github.com/PhenX/php-font-lib
 * @author  Fabien Ménager <fabien.menager@gmail.com>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */

/**
 * Generic Font table directory entry.
 * 
 * @package php-font-lib
 */
class Font_Table_Directory_Entry extends Font_Binary_Stream {
  /**
   * @var Font_TrueType
   */
  protected $font;
  
  /**
   * @var Font_Table
   */
  protected $font_table;
  
  public $entryLength = 4;
  
  public $tag;
  public $checksum;
  public $offset;
  public $length;
  
  protected $origF;
  
  static function computeChecksum($data){
    $len = strlen($data);
    $mod = $len % 4;
    
    if ($mod) { 
      $data = str_pad($data, $len + (4 - $mod), "\0");
    }
    
    $len = strlen($data);
    
    $hi = 0x0000;
    $lo = 0x0000;
    
    for ($i = 0; $i < $len; $i += 4) {
      $hi += (ord($data[$i]  ) << 8) + ord($data[$i+1]);
      $lo += (ord($data[$i+2]) << 8) + ord($data[$i+3]);
      $hi += $lo >> 16;
      $lo = $lo & 0xFFFF;
      $hi = $hi & 0xFFFF;
    }
    
    return ($hi << 8) + $lo;
  }
  
  function __construct(Font_TrueType $font) {
    $this->font = $font;
    $this->f = $font->f;
  }
  
  function parse() {
    $this->tag = $this->font->read(4);
  }
  
  function open($filename, $mode = self::modeRead) {
    // void
  }
  
  function setTable(Font_Table $font_table) {
    $this->font_table = $font_table;
  }
  
  function encode($entry_offset){
    Font::d("\n==== $this->tag ====");
    //Font::d("Entry offset  = $entry_offset");
    
    $data = $this->font_table;
    $font = $this->font;
    
    $table_offset = $font->pos();
    $this->offset = $table_offset;
    $table_length = $data->encode();
    
    $font->seek($table_offset);
    $table_data = $font->read($table_length);
    
    $font->seek($entry_offset);
    
    $font->write($this->tag, 4);
    $font->writeUInt32(self::computeChecksum($table_data));
    $font->writeUInt32($table_offset);
    $font->writeUInt32($table_length);
    
    Font::d("Bytes written = $table_length");
    
    $font->seek($table_offset + $table_length);
  }
  
  /**
   * @return Font_TrueType
   */
  function getFont() {
    return $this->font;
  }
  
  function startRead() {
    $this->font->seek($this->offset);
  }
  
  function endRead() {
    //
  }
  
  function startWrite() {
    $this->font->seek($this->offset);
  }
  
  function endWrite() {
    //
  }
}

