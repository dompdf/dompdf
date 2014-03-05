<?php
/**
 * @package php-font-lib
 * @link    https://github.com/PhenX/php-font-lib
 * @author  Fabien Ménager <fabien.menager@gmail.com>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */

require_once dirname(__FILE__) . "/Font_Table_Directory_Entry.php";

/**
 * WOFF font file table directory entry.
 * 
 * @package php-font-lib
 */
class Font_WOFF_Table_Directory_Entry extends Font_Table_Directory_Entry {
  public $origLength;

  function __construct(Font_WOFF $font) {
    parent::__construct($font);
  }
  
  function parse(){
    parent::parse();
    
    $font = $this->font;
    $this->offset     = $font->readUInt32();
    $this->length     = $font->readUInt32();
    $this->origLength = $font->readUInt32();
    $this->checksum   = $font->readUInt32();
  }
}
