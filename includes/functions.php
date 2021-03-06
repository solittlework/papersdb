<?php

/**
 * Common functions used by all pages.
 *
 * These functions are used throughout the pages and are here to save on time
 * and complexity.
 *
 * @package PapersDB
 */

/** Requires DB functions and Table classes. */
require_once 'defines.php';

//$relative_files_path = "uploaded_files/";
//$absolute_files_path = FS_PATH . $relative_files_path;

/**
 *  Checks to see if the given string is nothing but letters or numbers and is
 *  shorter then a certain length.
 */
function isValid($string){
   for($a = 0; $a < strlen($string); $a++){
      $char = substr($string,$a,1);
      $isValid = false;
      // Numbers 0-9
      for($b = 48; $b <= 57; $b++)
         if($char == chr($b))
            $isValid = true;
      //Uppercase A to Z
      if(!$isValid)
         for($b = 65; $b <= 90; $b++)
            if($char == chr($b))
               $isValid = true;
      //Lowercase a to z
      if(!$isValid)
         for($b = 97; $b <= 122; $b++)
            if($char == chr($b))
               $isValid = true;
      if(!$isValid)
         return errorMessage();
   }
   return "";
}

/**
 * Converts an array into an object.
 */
function arr2obj($arg_array) {
   $tmp = new stdClass; // start off a new (empty) object
   foreach ($arg_array as $key => $value) {
      if (is_array($value)) { // if its multi-dimentional, keep going :)
         $tmp->$key = arr2obj($value);
      } else {
         if (is_numeric($key)) { // can't do it with numbers :(
            die("Cannot turn numeric arrays into objects!");
         }
         $tmp->$key = $value;
      }
   }
   return $tmp; // return the object!
}

/**
 * removes empty values from an array.
 * */
function cleanArray($array) {
   foreach ($array as $index => $value) {
      if (empty($value)) unset($array[$index]);
   }
   return $array;
}

/**
 * format text into multiple lines not exceeding 80 characters
 */
function format80($text) {
   if (!isset($text) || ($text == '')) return;

   $lines = explode("\n", $text);
   foreach($lines as $line) {
      preg_match("/^(\s+)/", $line, $m);

      $indent = '';
      if (isset($m[1]))
         $indent = $m[1];

      if (strlen($line) > 80) {
         while (strlen($line) > 80) {
            $splt = strrpos(substr($line, 0, 80), ' ');
            if (($splt === false) || ($splt == 0))
               break;
            else {
               $new_lines[] = substr($line, 0, $splt);
               $line = $indent . $indent . substr($line, $splt+1);
            }
         }
      }
      $new_lines[] = $line;
   }

   return implode("\n", $new_lines);
}


/**
 * catch the contents of a print_r into a string
 *
 * @access private
 * @param $data unknown variable
 * @return string print_r results
 * @global
 */
function debug_capture_print_r($data) {
   ob_start();
   print_r($data);
   $result = ob_get_contents();
   ob_end_clean();
   return $result;
}

function debugVar($name,$data) {
   $captured = explode("\n", debug_capture_print_r($data));

   if (PHP_SAPI == "cli") {
      echo $name, "\n";
      foreach  ($captured as $line)
         echo $line, "\n";
      return;
   }

   echo $name, "<br/>\n<pre>";
   foreach  ($captured as $line) {
      echo debug_colorize_string($line), "\n";
   }
   echo "</pre>\n";
}

/**
 * colorize a string for pretty display
 *
 * @access private
 * @param $string string info to colorize
 * @return string HTML colorized
 * @global
 */
function debug_colorize_string($string)
{
   /* turn array indexes to red */
   $string = str_replace('[','[<font color="red">',$string);
   $string = str_replace(']','</font>]',$string);
   /* turn the word Array blue */
   $string = str_replace('Array','<font color="blue">Array</font>',$string);
   /* turn arrows graygreen */
   $string = str_replace('=>','<font color="#556F55">=></font>',$string);
   return $string;
}

/**
 * show string for cli version.
 *
 * @access private
 * @param $string string info to colorize
 * @return string HTML colorized
 * @global
 */
function debug_string($string)
{
   /* turn array indexes to red */
   $string = str_replace('[','[<font color="red">',$string);
   $string = str_replace(']','</font>]',$string);
   /* turn the word Array blue */
   $string = str_replace('Array','<font color="blue">Array</font>',$string);
   /* turn arrows graygreen */
   $string = str_replace('=>','<font color="#556F55">=></font>',$string);
   return $string;
}

/**
 * Initializes a publication add / edit session.
 */
function pubSessionInit() {
   if (!isset($_SESSION)) return;

   unset($_SESSION['state']);
   unset($_SESSION['pub']);
   unset($_SESSION['paper']);
   unset($_SESSION['attachments']);
   unset($_SESSION['att_types']);
   unset($_SESSION['removed_atts']);
   unset($_SESSION['similar_pubs']);
}

/**
 * rm() -- Vigorously erase files and directories.
 *
 * @param $fileglob mixed If string, must be a file name (foo.txt), glob
 * pattern (*.txt), or directory name.  If array, must be an array of file
 * names, glob patterns, or directories.
 */
function rm($fileglob) {
   if (is_string($fileglob)) {
      if (is_file($fileglob)) {
         return unlink($fileglob);
      }
      else if (is_dir($fileglob)) {
         $ok = rm("$fileglob/*");
         if (! $ok) {
            return false;
         }
         return rmdir($fileglob);
      }
      else {
         $matching = glob($fileglob);
         if ($matching === false) {
            trigger_error(sprintf('No files match supplied glob %s', $fileglob), E_USER_WARNING);
            return false;
         }
         $rcs = array_map('rm', $matching);
         if (in_array(false, $rcs)) {
            return false;
         }
      }
   }
   else if (is_array($fileglob)) {
      $rcs = array_map('rm', $fileglob);
      if (in_array(false, $rcs)) {
         return false;
      }
   }
   else {
      trigger_error('Param #1 must be filename or glob pattern, or array of filenames or glob patterns', E_USER_ERROR);
      return false;
   }

   return true;
}

/**
 * Initializes a search session.
 */
function searchSessionInit() {
   unset($_SESSION['search_results']);
   unset($_SESSION['search_url']);
   unset($_SESSION['search_params']);
}

function papersdb_backtrace() {
   $s = '';
   $MAXSTRLEN = 64;

   $s = '<div class="backtrace"><pre align=left>';
   $traceArr = debug_backtrace();

   //print_r($traceArr);

   array_shift($traceArr);
   foreach($traceArr as $arr) {
      if (isset($arr['class'])) $s .= $arr['class'].'.';
      $args = array();
      if(!empty($arr['args'])) foreach($arr['args'] as $v) {
            if (is_null($v)) $args[] = 'null';
            else if (is_array($v)) $args[] = 'Array['.sizeof($v).']';
            else if (is_object($v)) $args[] = 'Object:'.get_class($v);
            else if (is_bool($v)) $args[] = $v ? 'true' : 'false';
            else {
               $v = (string) @$v;
               $str = htmlspecialchars(substr($v,0,$MAXSTRLEN));
               if (strlen($v) > $MAXSTRLEN) $str .= '...';
               $args[] = "\"".$str."\"";
            }
         }
      $s .= $arr['function'].'('.implode(', ',$args).')';
      $Line = (isset($arr['line'])? $arr['line'] : "unknown");
      $File = (isset($arr['file'])? $arr['file'] : "unknown");
      $s .= sprintf("<br/>  line %4d, file: <a href=\"file:/%s\">%s</a>",
                    $Line, $File, $File);
      $s .= "\n";
   }
   $s .= '</pre></div>';
   echo $s;
}

/**
 * Use our own error handling function.
 */
function userErrorHandler($errno, $errmsg, $filename, $linenum, $vars) {
   if (PHP_VERSION >= 5)
      if ($errno >= E_STRICT) return;

   //if ($errno == E_NOTICE) return;

   // timestamp for the error entry
   $dt = date("Y-m-d H:i:s (T)");

   // define an assoc array of error string
   // in reality the only entries we should
   // consider are E_WARNING, E_NOTICE, E_USER_ERROR,
   // E_USER_WARNING and E_USER_NOTICE
   $errortype = array (
      E_ERROR           => "Error",
      E_WARNING         => "Warning",
      E_PARSE           => "Parsing Error",
      E_NOTICE          => "Notice",
      E_CORE_ERROR      => "Core Error",
      E_CORE_WARNING    => "Core Warning",
      E_COMPILE_ERROR   => "Compile Error",
      E_COMPILE_WARNING => "Compile Warning",
      E_USER_ERROR      => "User Error",
      E_USER_WARNING    => "User Warning",
      E_USER_NOTICE     => "User Notice"
      //E_STRICT          => "Runtime Notice"
      );
   // set of errors for which a var trace will be saved
   $user_errors = array(E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE);

   $err = "<ul>\n";
   $err .= "\t<li>" . $dt . "</li>\n";
   $err .= "\t<li>Errno: " . $errno . ', ' . $errortype[$errno] . "</li>\n";
   $err .= "\t<li>" . $errmsg . "</li>\n";
   $err .= "\t<li>" . $filename . ":" . $linenum . "</li>\n";

   if (in_array($errno, $user_errors)) {
      $err .= "\t<li>" . wddx_serialize_value($vars, "Variables") . "</li>\n";
   }
   $err .= "</ul>\n\n";

   // for testing
   echo $err;
   papersdb_backtrace();
   echo "include path: ", ini_get("include_path"), "\n";
   exit(1);
}

/**
 * Converts PHP array to its Javascript analog
 *
 * @access private
 * @param  array     PHP array to convert
 * @param  bool      Generate Javascript object literal (default, works like PHP's associative array) or array literal
 * @return string    Javascript representation of the value
 */
function convertArrayToJavascript($array, $assoc = true) {
   if (!is_array($array)) {
      return $convertScalarToJavascript($array);
   } else {
      $items = array();
      foreach ($array as $key => $val) {
         $item = $assoc? "'" . escapeString($key) . "': ": '';
         if (is_array($val)) {
            $item .= convertArrayToJavascript($val, $assoc);
         } else {
            $item .= convertScalarToJavascript($val);
         }
         $items[] = $item;
      }
   }
   $js = implode(', ', $items);
   return $assoc? '{ ' . $js . ' }': '[' . $js . ']';
}

/**
 * Converts PHP's scalar value to its Javascript analog
 *
 * @access private
 * @param  mixed     PHP value to convert
 * @return string    Javascript representation of the value
 */
function convertScalarToJavascript($val)
{
   if (is_bool($val)) {
      return $val ? 'true' : 'false';
   } elseif (is_int($val) || is_double($val)) {
      return $val;
   } elseif (is_string($val)) {
      return "'" . escapeString($val) . "'";
   } elseif (is_null($val)) {
      return 'null';
   } else {
      // don't bother
      return '{}';
   }
}

/**
 * Quotes the string so that it can be used in Javascript string constants
 *
 * @access private
 * @param  string
 * @return string
 */
function escapeString($str) {
   return strtr($str,array(
                   "\r"    => '\r',
                   "\n"    => '\n',
                   "\t"    => '\t',
                   "'"     => "\\'",
                   '"'     => '\"',
                   '\\'    => '\\\\'
                   ));
}

function __autoload($class_name) {
   $files = array(
      $class_name . '.php',
      str_replace('_', '/', $class_name) . '.php',
      );
   foreach (explode(PATH_SEPARATOR, ini_get('include_path')) as $base_path)
   {
      foreach ($files as $file)
      {
         $path = "$base_path/$file";
         if (file_exists($path) && is_readable($path))
         {
            include_once $path;
            return;
         }
      }
   }
}

function date2Timestamp($date) {
   $datesplit = split('-', $date);
   if (count($datesplit) != 3)
      throw new Exception("invalid date format " . $date);

   return mktime(0, 0, 0, $datesplit[1], $datesplit[2], $datesplit[0]);
}

if (PHP_SAPI != "cli") {
   $old_error_handler = set_error_handler("userErrorHandler");
   assert_options(ASSERT_CALLBACK, 'papersdb_backtrace');
}

?>
