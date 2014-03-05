<?php
/**
 * @package php-font-lib
 * @link    https://github.com/PhenX/php-font-lib
 * @author  Fabien Ménager <fabien.menager@gmail.com>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */

/**
 * Generic font table.
 * 
 * @package php-font-lib
 */
class Font_Table extends Font_Binary_Stream {
  /**
   * @var Font_Table_Directory_Entry
   */
  protected $entry;
  protected $def = array();
  
  public $data;
  
  final public function __construct(Font_Table_Directory_Entry $entry) {
    $this->entry = $entry;
    $entry->setTable($this);
  }
  
  /**
   * @return Font_TrueType
   */
  public function getFont(){
    return $this->entry->getFont();
  }
  
  protected function _encode(){
    if (empty($this->data)) {
      Font::d("  >> Table is empty");
      return 0;
    }
    
    return $this->getFont()->pack($this->def, $this->data);
  }
  
  protected function _parse(){
    $this->data = $this->getFont()->unpack($this->def);
  }
  
  protected function _parseRaw(){
    $this->data = $this->getFont()->read($this->entry->length);
  }
  
  protected function _encodeRaw(){
    return $this->getFont()->write($this->data, $this->entry->length);
  }
  
  public function toHTML(){
    return "<pre>".var_export($this->data, true)."</pre>"; 
  }
  
  final public function encode(){
    $this->entry->startWrite();
    
    if (false && empty($this->def)) {
      $length = $this->_encodeRaw();
    }
    else {
      $length = $this->_encode();
    }
    
    $this->entry->endWrite();
    
    return $length;
  }
  
  final public function parse(){
    $this->entry->startRead();
    
    if (false && empty($this->def)) {
      $this->_parseRaw();
    }
    else {
      $this->_parse();
    }
    
    $this->entry->endRead();
  }
}