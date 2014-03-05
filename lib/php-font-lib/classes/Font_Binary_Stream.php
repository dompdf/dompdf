<?php
/**
 * @package php-font-lib
 * @link    https://github.com/PhenX/php-font-lib
 * @author  Fabien Ménager <fabien.menager@gmail.com>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */

/**
 * Generic font file binary stream.
 * 
 * @package php-font-lib
 */
class Font_Binary_Stream {
  /**
   * @var resource The file pointer
   */
  protected $f;
  
  const uint8     = 1;
  const  int8     = 2;
  const uint16    = 3;
  const  int16    = 4;
  const uint32    = 5;
  const  int32    = 6;
  const shortFrac = 7;
  const Fixed     = 8;
  const  FWord    = 9;
  const uFWord    = 10;
  const F2Dot14   = 11;
  const longDateTime = 12;
  const char      = 13;
  
  const modeRead      = "rb";
  const modeWrite     = "wb";
  const modeReadWrite = "rb+";
  
  static function backtrace(){
    var_dump(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
  }

  /**
   * Open a font file in read mode
   * 
   * @param string $filename The file name of the font to open
   *
   * @return bool
   */
  public function load($filename) {
    return $this->open($filename, self::modeRead);
  }

  /**
   * Open a font file in a chosen mode
   *
   * @param string $filename The file name of the font to open
   * @param string $mode     The opening mode
   *
   * @throws Exception
   * @return bool
   */
  public function open($filename, $mode = self::modeRead) {
    if (!in_array($mode, array(self::modeRead, self::modeWrite, self::modeReadWrite))) {
      throw new Exception("Unkown file open mode");
    }
    
    $this->f = fopen($filename, $mode);
    return $this->f != false;
  }
  
  /**
   * Close the internal file pointer
   */
  public function close() {
    return fclose($this->f) != false;
  }

  /**
   * Change the internal file pointer
   *
   * @param resource $fp
   *
   * @throws Exception
   */
  public function setFile($fp) {
    if (!is_resource($fp)) {
      throw new Exception('$fp is not a valid resource');
    }
    
    $this->f = $fp;
  }

  /**
   * Create a temporary file in write mode
   *
   * @param bool $allow_memory Allow in-memory files
   *
   * @return resource the temporary file pointer resource
   */
  public static function getTempFile($allow_memory = true) {
    $f = null;
    
    if ($allow_memory) {
      // PHP 5.1+
      @fopen("php://temp", "rb+");
    }
    
    if (!$f) {
      $f = fopen(tempnam(sys_get_temp_dir(), "fnt"), "rb+");
    }
    
    return $f;
  }
  
  /**
   * Move the internal file pinter to $offset bytes
   * 
   * @param int $offset
   *
   * @return bool True if the $offset position exists in the file
   */
  public function seek($offset) {
    return fseek($this->f, $offset, SEEK_SET) == 0;
  }
  
  /**
   * Gives the current position in the file
   * 
   * @return int The current position
   */
  public function pos() {
    return ftell($this->f);
  }
  
  public function skip($n) {
    fseek($this->f, $n, SEEK_CUR);
  }
  
  public function read($n) {
    if ($n < 1) {
      return "";
    }

    return fread($this->f, $n);
  }

  public function write($data, $length = null) {
    if ($data === null || $data === "") {
      return 0;
    }
    
    return fwrite($this->f, $data, $length);
  }

  public function readUInt8() {
    return ord($this->read(1));
  }

  public function writeUInt8($data) {
    return $this->write(chr($data), 1);
  }

  public function readInt8() {
    $v = $this->readUInt8();
    
    if ($v >= 0x80) {
      $v -= 0x100;
    }
      
    return $v;
  }

  public function writeInt8($data) {
    if ($data < 0) {
      $data += 0x100;
    }
    
    return $this->writeUInt8($data);
  }

  public function readUInt16() {
    $a = unpack("nn", $this->read(2));
    return $a["n"];
  }
  
  public function readUFWord(){
    return $this->readUInt16();
  }

  public function writeUInt16($data) {
    return $this->write(pack("n", $data), 2);
  }
  
  public function writeUFWord($data){
    return $this->writeUInt16($data);
  }

  public function readInt16() {
    $v = $this->readUInt16();
    
    if ($v >= 0x8000) {
      $v -= 0x10000;
    }
      
    return $v;
  }
  
  public function readFWord(){
    return $this->readInt16();
  }

  public function writeInt16($data) {
    if ($data < 0) {
      $data += 0x10000;
    }
    
    return $this->writeUInt16($data);
  }
  
  public function writeFWord($data){
    return $this->writeInt16($data);
  }

  public function readUInt32() {
    $a = unpack("NN", $this->read(4));
    return $a["N"];
  }

  public function writeUInt32($data) {
    return $this->write(pack("N", $data), 4);
  }

  public function readFixed() {
    $d = $this->readInt16();
    $d2 = $this->readUInt16();
    return round($d + $d2 / 0x10000, 4);
  }

  public function writeFixed($data) {
    $left = floor($data);
    $right = ($data - $left) * 0x10000;
    return $this->writeInt16($left) + $this->writeUInt16($right);
  }
  
  public function readLongDateTime() {
    $this->readUInt32(); // ignored 
    $date = $this->readUInt32() - 2082844800;
    
    return strftime("%Y-%m-%d %H:%M:%S", $date);
  }
  
  public function writeLongDateTime($data) {
    $date = strtotime($data);
    $date += 2082844800;
    
    return $this->writeUInt32(0) + $this->writeUInt32($date);
  }
  
  public function unpack($def) {
    $d = array();
    foreach($def as $name => $type) {
      $d[$name] = $this->r($type);
    }
    return $d;
  }
  
  public function pack($def, $data) {
    $bytes = 0;
    foreach($def as $name => $type) {
      $bytes += $this->w($type, $data[$name]);
    }
    return $bytes;
  }
  
  /**
   * Read a data of type $type in the file from the current position
   * 
   * @param mixed $type The data type to read
   *
   * @return mixed The data that was read
   */
  public function r($type) {
    switch($type) {
      case self::uint8:     return $this->readUInt8();
      case self::int8:      return $this->readInt8();
      case self::uint16:    return $this->readUInt16();
      case self::int16:     return $this->readInt16();
      case self::uint32:    return $this->readUInt32();
      case self::int32:     return $this->readUInt32(); 
      case self::shortFrac: return $this->readFixed();
      case self::Fixed:     return $this->readFixed();
      case self::FWord:     return $this->readInt16();
      case self::uFWord:    return $this->readUInt16();
      case self::F2Dot14:   return $this->readInt16();
      case self::longDateTime: return $this->readLongDateTime();
      case self::char:      return $this->read(1);
      default: 
        if ( is_array($type) ) {
          if ($type[0] == self::char) {
            return $this->read($type[1]);
          }
          
          $ret = array();
          for($i = 0; $i < $type[1]; $i++) {
            $ret[] = $this->r($type[0]);
          }
          return $ret;
        }

        return null;
    }
  }
  
  /**
   * Write $data of type $type in the file from the current position
   * 
   * @param mixed $type The data type to write
   * @param mixed $data The data to write
   *
   * @return int The number of bytes read
   */
  public function w($type, $data) {
    switch($type) {
      case self::uint8:     return $this->writeUInt8($data);
      case self::int8:      return $this->writeInt8($data);
      case self::uint16:    return $this->writeUInt16($data);
      case self::int16:     return $this->writeInt16($data);
      case self::uint32:    return $this->writeUInt32($data);
      case self::int32:     return $this->writeUInt32($data); 
      case self::shortFrac: return $this->writeFixed($data);
      case self::Fixed:     return $this->writeFixed($data);
      case self::FWord:     return $this->writeInt16($data);
      case self::uFWord:    return $this->writeUInt16($data);
      case self::F2Dot14:   return $this->writeInt16($data);
      case self::longDateTime: return $this->writeLongDateTime($data);
      case self::char:      return $this->write($data, 1);
      default: 
        if ( is_array($type) ) {
          if ($type[0] == self::char) {
            return $this->write($data, $type[1]);
          }
          
          $ret = 0;
          for($i = 0; $i < $type[1]; $i++) {
            $ret += $this->w($type[0], $data[$i]);
          }
          return $ret;
        }

        return null;
    }
  }
  
  /**
   * Converts a Uint32 value to string
   * 
   * @param int $uint32
   *
   * @return string The string
   */
  public function convertUInt32ToStr($uint32) {
    return chr(($uint32 >> 24) & 0xFF).chr(($uint32 >> 16) & 0xFF).chr(($uint32 >> 8) & 0xFF).chr($uint32 & 0xFF);
  }
}
