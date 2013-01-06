<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @author  Fabien Ménager <fabien.menager@gmail.com>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
 
/**
 * DOMPDF autoload function
 *
 * If you have an existing autoload function, add a call to this function
 * from your existing __autoload() implementation.
 *
 * @param string $class
 */
function DOMPDF_autoload($class) {
  $filename = DOMPDF_INC_DIR . "/" . mb_strtolower($class) . ".cls.php";
  
  if ( is_file($filename) ) {
    include_once $filename;
  }
}

// If SPL autoload functions are available (PHP >= 5.1.2)
if ( function_exists("spl_autoload_register") ) {
  $autoload = "DOMPDF_autoload";
  $funcs = spl_autoload_functions();
  
  // No functions currently in the stack. 
  if ( !DOMPDF_AUTOLOAD_PREPEND || $funcs === false ) {
    spl_autoload_register($autoload); 
  }
  
  // If PHP >= 5.3 the $prepend argument is available
  else if ( PHP_VERSION_ID >= 50300 ) {
    spl_autoload_register($autoload, true, true); 
  }
  
  else {
    // Unregister existing autoloaders... 
    $compat = (PHP_VERSION_ID <= 50102 && PHP_VERSION_ID >= 50100);
              
    foreach ($funcs as $func) { 
      if (is_array($func)) { 
        // :TRICKY: There are some compatibility issues and some 
        // places where we need to error out 
        $reflector = new ReflectionMethod($func[0], $func[1]); 
        if (!$reflector->isStatic()) { 
          throw new Exception('This function is not compatible with non-static object methods due to PHP Bug #44144.'); 
        }
        
        // Suprisingly, spl_autoload_register supports the 
        // Class::staticMethod callback format, although call_user_func doesn't 
        if ($compat) $func = implode('::', $func); 
      }
      
      spl_autoload_unregister($func); 
    }
    
    // Register the new one, thus putting it at the front of the stack... 
    spl_autoload_register($autoload); 
    
    // Now, go back and re-register all of our old ones. 
    foreach ($funcs as $func) { 
      spl_autoload_register($func); 
    }
    
    // Be polite and ensure that userland autoload gets retained
    if ( function_exists("__autoload") ) {
      spl_autoload_register("__autoload");
    }
  }
}

else if ( !function_exists("__autoload") ) {
  /**
   * Default __autoload() function
   *
   * @param string $class
   */
  function __autoload($class) {
    DOMPDF_autoload($class);
  }
}
