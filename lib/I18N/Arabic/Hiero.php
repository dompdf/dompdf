<?php
/**
 * ----------------------------------------------------------------------
 *  
 * Copyright (c) 2006-2016 Khaled Al-Sham'aa.
 *  
 * http://www.ar-php.org
 *  
 * PHP Version 5 
 *  
 * ----------------------------------------------------------------------
 *  
 * LICENSE
 *
 * This program is open source product; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public License (LGPL)
 * as published by the Free Software Foundation; either version 3
 * of the License, or (at your option) any later version.
 *  
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *  
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/lgpl.txt>.
 *  
 * ----------------------------------------------------------------------
 *  
 * Class Name: Translate English word into Hieroglyphics
 *  
 * Filename:   Hiero.php
 *  
 * Original    Author(s): Khaled Al-Sham'aa <khaled@ar-php.org>
 *  
 * Purpose:    Translate English word into Hieroglyphics
 *              
 * ----------------------------------------------------------------------
 *  
 * Translate English word into Hieroglyphics
 *
 * Royality is made affordable, and within your reach. Now you can have The 
 * Royal Cartouche custome made in Egypt in 18 Kt. Gold with your name 
 * translated and inscribed in Hieroglyphic.
 * 
 * Originally, the Cartouche was worn only by the Pharaohs or Kings of Egypt. 
 * The Pharaoh was considered a living God and his Cartouche was his insignia. 
 * The "Magical Oval" in which the Pharaoh's first name was written was intended 
 * to protect him from evil spirits both while he lived and in the afterworld 
 * when entombed.
 * 
 * Over the past 5000 years the Cartouche has become a universal symbol of long 
 * life, good luck and protection from any evil.
 * 
 * Now you can acquire this ancient pendent handmade in Egypt from pure 18 Karat 
 * Egyptian gold with your name spelled out in the same way as King Tut, Ramses, 
 * Queen Nefertiti did.  
 *
 * Example:
 * <code>
 *     include('./I18N/Arabic.php');
 *     $obj = new I18N_Arabic('Hiero');
 * 
 *     $word = $_GET['w'];
 *     $im   = $obj->str2hiero($word);
 *      
 *     header ("Content-type: image/jpeg");
 *     imagejpeg($im, '', 80);
 *     ImageDestroy($im);
 * </code>
 *             
 * @category  I18N 
 * @package   I18N_Arabic
 * @author    Khaled Al-Sham'aa <khaled@ar-php.org>
 * @copyright 2006-2016 Khaled Al-Sham'aa
 *    
 * @license   LGPL <http://www.gnu.org/licenses/lgpl.txt>
 * @link      http://www.ar-php.org 
 */

/**
 * Translate English word into Hieroglyphics
 *  
 * @category  I18N 
 * @package   I18N_Arabic
 * @author    Khaled Al-Sham'aa <khaled@ar-php.org>
 * @copyright 2006-2016 Khaled Al-Sham'aa
 *    
 * @license   LGPL <http://www.gnu.org/licenses/lgpl.txt>
 * @link      http://www.ar-php.org 
 */ 
class I18N_Arabic_Hiero
{
    private $_language = 'Hiero';

    /**
     * Loads initialize values
     *
     * @ignore
     */         
    public function __construct ()
    {
    }

    /**
     * Set the output language
     *      
     * @param string $value Output language (Hiero or Phoenician)
     *      
     * @return object $this to build a fluent interface
     * @author Khaled Al-Sham'aa <khaled@ar-php.org>
     */
    public function setLanguage($value)
    {
        $value = strtolower($value);
        
        if ($value == 'hiero' || $value == 'phoenician') {
            $this->_language = $value;
        }
        
        return $this;
    }

    /**
     * Get the output language
     *      
     * @return string return current setting of the output language
     * @author Khaled Al-Sham'aa <khaled@ar-php.org>
     */
    public function getLanguage()
    {
        return ucwords($this->_language);
    }
            
    /**
    * Translate Arabic or English word into Hieroglyphics
    *      
    * @param string  $word  Arabic or English word
    * @param string  $dir   Writing direction [ltr, rtl, ttd, dtt] (default ltr)
    * @param string  $lang  Input language [en, ar] (default en)
    * @param integer $red   Value of background red component (default is null)
    * @param integer $green Value of background green component (default is null)
    * @param integer $blue  Value of background blue component (default is null)
    *      
    * @return resource Image resource identifier
    * @author Khaled Al-Sham'aa <khaled@ar-php.org>
    */
    public function str2graph(
        $word, $dir = 'ltr', $lang = 'en', $red = null, $green = null, $blue = null
    ) {
        if ($this->_language == 'phoenician') {
            define(MAXH, 40);
            define(MAXW, 50);
        } else {
            define(MAXH, 100);
            define(MAXW, 75);
        }

        // Note: there is no theh, khah, thal, dad, zah, and ghain in Phoenician
        $arabic = array(
            'ا' => 'alef',
            'ب' => 'beh',
            'ت' => 'teh',
            'ث' => 'theh',
            'ج' => 'jeem',
            'ح' => 'hah',
            'خ' => 'khah',
            'د' => 'dal',
            'ذ' => 'thal',
            'ر' => 'reh',
            'ز' => 'zain',
            'س' => 'seen',
            'ش' => 'sheen',
            'ص' => 'sad',
            'ض' => 'dad',
            'ط' => 'tah',
            'ظ' => 'zah',
            'ع' => 'ain',
            'غ' => 'ghain',
            'ف' => 'feh',
            'ق' => 'qaf',
            'ك' => 'kaf',
            'ل' => 'lam',
            'م' => 'meem',
            'ن' => 'noon',
            'ه' => 'heh',
            'و' => 'waw',
            'ي' => 'yeh'
        );
                
        if ($lang != 'ar' && $this->_language == 'phoenician') {
            include dirname(__FILE__).'/Transliteration.php';

            $temp = new Transliteration();
            $word = $temp->en2ar($word);

            $temp = null;
            $lang = 'ar';
        }

        if ($lang != 'ar') {
            $word = strtolower($word);
        } else {
            $word = str_replace('ة', 'ت', $word);
            $alef = array('ى', 'ؤ', 'ئ', 'ء', 'آ', 'إ', 'أ');
            $word = str_replace($alef, '?', $word);
        }
        
        $chars = array();
        $max   = mb_strlen($word, 'UTF-8');

        for ($i = 0; $i < $max; $i++) {
            $chars[] = mb_substr($word, $i, 1, 'UTF-8');
        }

        if ($dir == 'rtl' || $dir == 'btt') {
            $chars = array_reverse($chars);
        }

        $max_w = 0;
        $max_h = 0;
        
        foreach ($chars as $char) {
            if ($lang == 'ar') {
                $char = $arabic[$char];
            }

            if (file_exists(dirname(__FILE__)."/images/{$this->_language}/$char.gif")
            ) {
                list($width, $height) = getimagesize(
                    dirname(__FILE__)."/images/{$this->_language}/$char.gif"
                );
            } else {
                $width  = MAXW;
                $height = MAXH;
            }
            
            if ($dir == 'ltr' || $dir == 'rtl') {
                $max_w += $width;
                if ($height > $max_h) { 
                    $max_h = $height; 
                }
            } else {
                $max_h += $height;
                if ($width > $max_w) { 
                    $max_w = $width; 
                }
            }
        }

        $im = imagecreatetruecolor($max_w, $max_h);
        
        if ($red == null) {
            $bck = imagecolorallocate($im, 0, 0, 255);
            imagefill($im, 0, 0, $bck);

            // Make the background transparent
            imagecolortransparent($im, $bck);
        } else {
            $bck = imagecolorallocate($im, $red, $green, $blue);
            imagefill($im, 0, 0, $bck);
        }

        $current_x = 0;
        
        foreach ($chars as $char) {
            if ($lang == 'ar') {
                $char = $arabic[$char];
            }
            $filename = dirname(__FILE__)."/images/{$this->_language}/$char.gif";
            
            if ($dir == 'ltr' || $dir == 'rtl') {
                if (file_exists($filename)) {
                    list($width, $height) = getimagesize($filename);

                    $image = imagecreatefromgif($filename);
                    imagecopy(
                        $im, $image, $current_x, $max_h - $height, 
                        0, 0, $width, $height
                    );
                } else {
                    $width = MAXW;
                }
    
                $current_x += $width;
            } else {
                if (file_exists($filename)) {
                    list($width, $height) = getimagesize($filename);

                    $image = imagecreatefromgif($filename);
                    imagecopy($im, $image, 0, $current_y, 0, 0, $width, $height);
                } else {
                    $height = MAXH;
                }
    
                $current_y += $height;
            }
        }
        
        return $im;
    }
}