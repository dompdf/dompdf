<?php
/**
 * @package php-font-lib
 * @link    https://github.com/PhenX/php-font-lib
 * @author  Fabien Ménager <fabien.menager@gmail.com>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 * @version $Id: Font_Table_glyf.php 46 2012-04-02 20:22:38Z fabien.menager $
 */

/**
 * Glyph outline component
 * 
 * @package php-font-lib
 */
class Font_Glyph_Outline_Component {
  public $flags;
  public $glyphIndex;
  public $a, $b, $c, $d, $e, $f;
  public $point_compound;
  public $point_component;
  public $instructions;

  function getMatrix(){
    return array(
      $this->a, $this->b,
      $this->c, $this->d,
      $this->e, $this->f,
    );
  }
}