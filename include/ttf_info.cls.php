<?php
/**
 * ttfInfo class
 * Retrieve data stored in a TTF files 'name' table
 *
 * @original author Unknown
 * found at http://www.phpclasses.org/browse/package/2144.html
 *
 * @ported for used on http://www.nufont.com
 * @author Jason Arencibia
 * @version 0.2
 * @copyright (c) 2006 GrayTap Media
 * @website http://www.graytap.com
 * @license GPL 2.0
 * @access public
 *
 * @todo: Make it Retrieve additional information from other tables
 * 
 */
class TTF_Info {
	/**
	* variable $_dirRestriction
	* Restrict the resource pointer to this directory and above.
	* Change to 1 for to allow the class to look outside of it current directory
	* @protected
	* @var int
	*/
	protected $_dirRestriction = true;
	/**
	* variable $_dirRestriction
	* Restrict the resource pointer to this directory and above.
	* Change to 1 for nested directories
	* @protected
	* @var int
	*/
	protected $_recursive = true;

	/**
	* variable $fontsdir
	* This is to declare this variable as protected
	* don't edit this!!!
	* @protected
	*/
	protected $fontsdir;
	/**
	* variable $filename
	* This is to declare this varable as protected
	* don't edit this!!!
	* @protected
	*/
	protected $filename;

	/**
	* function setFontFile()
	* set the filename
	* @public
	* @param string $data the new value
	* @return object reference to this
	*/
	public function setFontFile($data)
	{
		if ($this->_dirRestriction && preg_match('[\.\/|\.\.\/]', $data))
		{
			$this->exitClass('Error: Directory restriction is enforced!');
		}

		$this->filename = $data;
		return $this;
	} // public function setFontFile

	/**
	* function setFontsDir()
	* set the Font Directory
	* @public
	* @param string $data the new value
	* @return object referrence to this
	*/
	public function setFontsDir($data)
	{
		if ($this->_dirRestriction && preg_match('[\.\/|\.\.\/]', $data))
		{
			$this->exitClass('Error: Directory restriction is enforced!');
		}

		$this->fontsdir = $data;
		return $this;
	} // public function setFontsDir

	/**
	* function readFontsDir() 
	* @public
	* @return information contained in the TTF 'name' table of all fonts in a directory.
	*/
	public function readFontsDir()
	{
		if (empty($this->fontsdir)) { $this->exitClass('Error: Fonts Directory has not been set with setFontsDir().'); }
		if (empty($this->backupDir)){ $this->backupDir = $this->fontsdir; }

		//$this->array = array();
		$d = dir($this->fontsdir);

		while (false !== ($e = $d->read()))
		{
			if($e != '.' && $e != '..')
			{
				$e = $this->fontsdir . $e;
				if($this->_recursive && is_dir($e))
				{
					$this->setFontsDir($e);
					$this->readFontsDir();
				}
				else if ($this->is_ttf($e) === true)
				{
					$this->setFontFile($e);
					$this->array[] = $this->getFontInfo();
				}
			}
		}

		if (!empty($this->backupDir)){ $this->fontsdir = $this->backupDir; }

		$d->close();
		return $this;
	} // public function readFontsDir

	/**
	* function setProtectedVar()
	* @public
	* @param string $var the new variable
	* @param string $data the new value
	* @return object reference to this

	* DISABLED, NO REAL USE YET

	public function setProtectedVar($var, $data)
	{
		if ($var == 'filename')
		{
			$this->setFontFile($data);
		} else {
			//if (isset($var) && !empty($data))
			$this->$var = $data;
		}
		return $this;
	}
	*/
	/**
	* function getFontInfo() 
	* @public
	* @return information contained in the TTF 'name' table.
	*/
	public function getFontInfo()
	{
		$fd = fopen ($this->filename, "r");
		$this->text = fread ($fd, filesize($this->filename));
		fclose ($fd);

		$number_of_tables = hexdec($this->dec2ord($this->text[4]).$this->dec2ord($this->text[5]));

		for ($i=0;$i<$number_of_tables;$i++)
		{
			$tag = $this->text[12+$i*16].$this->text[12+$i*16+1].$this->text[12+$i*16+2].$this->text[12+$i*16+3];

			if ($tag == 'name')
			{
				$this->ntOffset = hexdec(
					$this->dec2ord($this->text[12+$i*16+8]).$this->dec2ord($this->text[12+$i*16+8+1]).
					$this->dec2ord($this->text[12+$i*16+8+2]).$this->dec2ord($this->text[12+$i*16+8+3]));

				$offset_storage_dec = hexdec($this->dec2ord($this->text[$this->ntOffset+4]).$this->dec2ord($this->text[$this->ntOffset+5]));
				$number_name_records_dec = hexdec($this->dec2ord($this->text[$this->ntOffset+2]).$this->dec2ord($this->text[$this->ntOffset+3]));
			}
		}

		$storage_dec = $offset_storage_dec + $this->ntOffset;
		$storage_hex = strtoupper(dechex($storage_dec));

		for ($j=0;$j<$number_name_records_dec;$j++)
		{
			$platform_id_dec	= hexdec($this->dec2ord($this->text[$this->ntOffset+6+$j*12+0]).$this->dec2ord($this->text[$this->ntOffset+6+$j*12+1]));
			$name_id_dec		= hexdec($this->dec2ord($this->text[$this->ntOffset+6+$j*12+6]).$this->dec2ord($this->text[$this->ntOffset+6+$j*12+7]));
			$string_length_dec	= hexdec($this->dec2ord($this->text[$this->ntOffset+6+$j*12+8]).$this->dec2ord($this->text[$this->ntOffset+6+$j*12+9]));
			$string_offset_dec	= hexdec($this->dec2ord($this->text[$this->ntOffset+6+$j*12+10]).$this->dec2ord($this->text[$this->ntOffset+6+$j*12+11]));

			if (!empty($name_id_dec) and empty($font_tags[$name_id_dec]))
			{
				for($l=0;$l<$string_length_dec;$l++)
				{
					if (ord($this->text[$storage_dec+$string_offset_dec+$l]) == '0') { continue; }
					else { $font_tags[$name_id_dec] .= ($this->text[$storage_dec+$string_offset_dec+$l]); }
				}
			}
		}
		return $font_tags;
	} // public function getFontInfo

	/**
	* function getCopyright() 
	* @public
	* @return 'Copyright notice' contained in the TTF 'name' table at index 0
	*/
	public function getCopyright()
	{
		$this->info = $this->getFontInfo();
		return $this->info[0];
	} // public function getCopyright

	/**
	* function getFontFamily() 
	* @public
	* @return 'Font Family name' contained in the TTF 'name' table at index 1
	*/
	public function getFontFamily()
	{
		$this->info = $this->getFontInfo();
		return $this->info[1];
	} // public function getFontFamily

	/**
	* function getFontSubFamily() 
	* @public
	* @return 'Font Subfamily name' contained in the TTF 'name' table at index 2
	*/
	public function getFontSubFamily()
	{
		$this->info = $this->getFontInfo();
		return $this->info[2];
	} // public function getFontSubFamily

	/**
	* function getFontId() 
	* @public
	* @return 'Unique font identifier' contained in the TTF 'name' table at index 3
	*/
	public function getFontId()
	{
		$this->info = $this->getFontInfo();
		return $this->info[3];
	} // public function getFontId

	/**
	* function getFullFontName() 
	* @public
	* @return 'Full font name' contained in the TTF 'name' table at index 4
	*/
	public function getFullFontName()
	{
		$this->info = $this->getFontInfo();
		return $this->info[4];
	} // public function getFullFontName

	/**
	* function dec2ord()
	* Used to lessen redundant calls to multiple functions.
	* @protected
	* @return object
	*/
	protected function dec2ord($dec)
	{
		return $this->dec2hex(ord($dec));
	} // protected function dec2ord

	/**
	* function dec2hex()
	* private function to perform Hexadecimal to decimal with proper padding.
	* @protected
	* @return object
	*/
	protected function dec2hex($dec)
	{
		return str_repeat('0', 2-strlen(($hex=strtoupper(dechex($dec))))) . $hex;
	} // protected function dec2hex

	/**
	* function dec2hex()
	* private function to perform Hexadecimal to decimal with proper padding.
	* @protected
	* @return object
	*/
	protected function exitClass($message)
	{
		echo $message;
		exit;
	} // protected function dec2hex

	/**
	* function dec2hex()
	* private helper function to test in the file in question is a ttf.
	* @protected
	* @return object
	*/
	protected function is_ttf($file)
	{
		$ext = explode('.', $file);
		$ext = $ext[count($ext)-1];
		return preg_match("/ttf$/i",$ext) ? true : false;
	} // protected function is_ttf
} // class ttfInfo

function getFontInfo($resource)
{
	$ttfInfo = new TTF_Info;
	$ttfInfo->setFontFile($resource);
	return $ttfInfo->getFontInfo();
}
?>