<?php

if (!defined('IN_SUSTC'))
{
  exit;
}

require_once SC_ROOT.'src/inc/utf/utf_tools.php';
require_once SC_ROOT.'src/inc/utf/utf_normalizer.php';

// Removes any "bad" characters (characters which mess with the display of a page, are invisible, etc) from user input
function forum_remove_bad_characters()
{
  global $bad_utf8_chars;

  $bad_utf8_chars = array("\0", "\xc2\xad", "\xcc\xb7", "\xcc\xb8", "\xe1\x85\x9F", "\xe1\x85\xA0", "\xe2\x80\x80", "\xe2\x80\x81", "\xe2\x80\x82", "\xe2\x80\x83", "\xe2\x80\x84", "\xe2\x80\x85", "\xe2\x80\x86", "\xe2\x80\x87", "\xe2\x80\x88", "\xe2\x80\x89", "\xe2\x80\x8a", "\xe2\x80\x8b", "\xe2\x80\x8e", "\xe2\x80\x8f", "\xe2\x80\xaa", "\xe2\x80\xab", "\xe2\x80\xac", "\xe2\x80\xad", "\xe2\x80\xae", "\xe2\x80\xaf", "\xe2\x81\x9f", "\xe3\x80\x80", "\xe3\x85\xa4", "\xef\xbb\xbf", "\xef\xbe\xa0", "\xef\xbf\xb9", "\xef\xbf\xba", "\xef\xbf\xbb", "\xE2\x80\x8D");

  //($hook = get_hook('fn_remove_bad_characters_start')) ? eval($hook) : null;

  function _forum_remove_bad_characters($array)
  {
    global $bad_utf8_chars;
    return is_array($array) ? array_map('_forum_remove_bad_characters', $array) : str_replace($bad_utf8_chars, '', $array);
  }

  $_GET = _forum_remove_bad_characters($_GET);
  $_POST = _forum_remove_bad_characters($_POST);
  $_COOKIE = _forum_remove_bad_characters($_COOKIE);
  $_REQUEST = _forum_remove_bad_characters($_REQUEST);
}

function init_utf_tools() {
  global $phpbb_root_path, $phpEx;

  $phpbb_root_path = SC_ROOT . 'src/';
  $phpEx = 'php';

  forum_remove_bad_characters();
}

?>