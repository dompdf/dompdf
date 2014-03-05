<?php
/**
 * @package php-font-lib
 * @link    https://github.com/PhenX/php-font-lib
 * @author  Fabien Ménager <fabien.menager@gmail.com>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */

require_once dirname(__FILE__) . "/Font_Table_Directory_Entry.php";

/**
 * TrueType table directory entry.
 * 
 * @package php-font-lib
 */
class Font_TrueType_Table_Directory_Entry extends Font_Table_Directory_Entry {
  function __construct(Font_TrueType $font) {
    parent::__construct($font);
  }
  
  function parse(){
    parent::parse();
    
    $font = $this->font;
    $this->checksum = $font->readUInt32();
    $this->offset   = $font->readUInt32();
    $this->length   = $font->readUInt32();
    $this->entryLength += 12;
  }
}

