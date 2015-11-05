<?php

if (!defined('IN_SUSTC')) {
  exit;
}

function is_post() {
  return $_SERVER['REQUEST_METHOD'] == 'POST';
}

function dheader($string, $replace = true, $http_response_code = 0) {
  $islocation = substr(strtolower(trim($string)), 0, 8) == 'location';
  /* if(defined('IN_MOBILE') && strpos($string, 'mobile') === false && $islocation) {
    if (strpos($string, '?') === false) {
      $string = $string.'?mobile=yes';
    } else {
      if(strpos($string, '#') === false) {
        $string = $string.'&mobile=yes';
      } else {
        $str_arr = explode('#', $string);
        $str_arr[0] = $str_arr[0].'&mobile=yes';
        $string = implode('#', $str_arr);
      }
    }
  } */
  $string = str_replace(array("\r", "\n"), array('', ''), $string);
  if(empty($http_response_code) || PHP_VERSION < '4.3' ) {
    @header($string, $replace);
  } else {
    @header($string, $replace, $http_response_code);
  }
  if($islocation) {
    exit();
  }
}

function dredirect($url) {
  dheader('Location: '.$url);
}

function dstripslashes($string) {
  if(empty($string)) return $string;
  if(is_array($string)) {
    foreach($string as $key => $val) {
      $string[$key] = dstripslashes($val);
    }
  } else {
    $string = stripslashes($string);
  }
  return $string;
}

function dimplode($array) {
  if(!empty($array)) {
    $array = array_map('addslashes', $array);
    return "'".implode("','", is_array($array) ? $array : array($array))."'";
  } else {
    return 0;
  }
}

function libfile($libname, $folder = '') {
  $libpath = '/src/'.$folder;
  if ($folder && substr($folder, -1) != '/') {
    $libpath .= '/';
  }
  if(strstr($libname, '/')) {
    list($pre, $name) = explode('/', $libname);
    $path = "{$libpath}{$pre}/{$name}";  //{$pre}_
  } else {
    $path = "{$libpath}{$libname}";
  }
  return preg_match('/^[\.\w\d\/_]+$/i', $path) ? realpath(SC_ROOT.$path.'.php') : false;
}

function getgeneratetime() {
  global $_G;
  return microtime(true) - $_G['starttime'];
}

function getglobal($key, $group = null) {
  global $_G;
  $key = explode('/', $group === null ? $key : $group.'/'.$key);
  $v = &$_G;
  foreach ($key as $k) {
    if (!isset($v[$k])) {
      return null;
    }
    $v = &$v[$k];
  }
  return $v;
}

function setglobal($key , $value, $group = null) {
  global $_G;
  $key = explode('/', $group === null ? $key : $group.'/'.$key);
  $p = &$_G;
  foreach ($key as $k) {
    if(!isset($p[$k]) || !is_array($p[$k])) {
      $p[$k] = array();
    }
    $p = &$p[$k];
  }
  $p = $value;
  return true;
}

function dgmdate($timestamp, $format = 'dt', $timeoffset = '9999', $uformat = '') {
  global $_G;
  $format == 'u' && !$_G['setting']['dateconvert'] && $format = 'dt';
  static $dformat, $tformat, $dtformat, $offset, $lang;
  if($dformat === null) {
    $dformat = getglobal('setting/dateformat');
    $tformat = getglobal('setting/timeformat');
    $dtformat = $dformat.' '.$tformat;
    $offset = getglobal('member/timeoffset');
    $lang = lang('common', 'date');
  }
  $timeoffset = $timeoffset == 9999 ? $offset : $timeoffset;
  $timestamp += $timeoffset * 3600;
  $format = empty($format) || $format == 'dt' ? $dtformat : ($format == 'd' ? $dformat : ($format == 't' ? $tformat : $format));
  if($format == 'u') {
    $todaytimestamp = TIMESTAMP - (TIMESTAMP + $timeoffset * 3600) % 86400 + $timeoffset * 3600;
    $s = gmdate(!$uformat ? $dtformat : $uformat, $timestamp);
    $time = TIMESTAMP + $timeoffset * 3600 - $timestamp;
    if($timestamp >= $todaytimestamp) {
      if($time > 3600) {
        return '<span title="'.$s.'">'.intval($time / 3600).'&nbsp;'.$lang['hour'].$lang['before'].'</span>';
      } elseif($time > 1800) {
        return '<span title="'.$s.'">'.$lang['half'].$lang['hour'].$lang['before'].'</span>';
      } elseif($time > 60) {
        return '<span title="'.$s.'">'.intval($time / 60).'&nbsp;'.$lang['min'].$lang['before'].'</span>';
      } elseif($time > 0) {
        return '<span title="'.$s.'">'.$time.'&nbsp;'.$lang['sec'].$lang['before'].'</span>';
      } elseif($time == 0) {
        return '<span title="'.$s.'">'.$lang['now'].'</span>';
      } else {
        return $s;
      }
    } elseif(($days = intval(($todaytimestamp - $timestamp) / 86400)) >= 0 && $days < 7) {
      if($days == 0) {
        return '<span title="'.$s.'">'.$lang['yday'].'&nbsp;'.gmdate($tformat, $timestamp).'</span>';
      } elseif($days == 1) {
        return '<span title="'.$s.'">'.$lang['byday'].'&nbsp;'.gmdate($tformat, $timestamp).'</span>';
      } else {
        return '<span title="'.$s.'">'.($days + 1).'&nbsp;'.$lang['day'].$lang['before'].'</span>';
      }
    } else {
      return $s;
    }
  } else {
    return gmdate($format, $timestamp);
  }
}

function strexists($string, $find) {
  return !(strpos($string, $find) === FALSE);
}

function hookscriptoutput($tplfile) {
}

function lang($file, $langvar = null, $vars = array(), $default = null) {
  global $_G;
  $fileinput = $file;
  $locale = $_G['locale'];

  $path = '';
  $tsplit = explode('/', $file);
  if (count($tsplit) > 1 && $tsplit[0]) {
    $path = $tsplit[0];
    $file = $tsplit[1];
  }
  /*list($path, $file) = explode('/', $file);
  if(!$file) {
    $file = $path;
    $path = '';
  }
  
  if(strpos($file, ':') !== false) {
    $path = 'plugin';
    list($file) = explode(':', $file);
  }

  if($path != 'plugin') {*/
    $key = $path == '' ? $file : $path.'_'.$file;
    if(!isset($_G['lang'][$key])) {
      include SC_ROOT.'./src/lang/'.$locale.'/'.($path == '' ? '' : $path.'/').''.$file.'.php';
      $_G['lang'][$key] = $lang;
    }
    /*if(defined('IN_MOBILE') && !defined('TPL_DEFAULT')) {
      include SC_ROOT.'./source/language/mobile/lang_template.php';
      $_G['lang'][$key] = array_merge($_G['lang'][$key], $lang);
    }
    if($file != 'error' && !isset($_G['cache']['pluginlanguage_system'])) {
      loadcache('pluginlanguage_system');
    }
    if(!isset($_G['hooklang'][$fileinput])) {
      if(isset($_G['cache']['pluginlanguage_system'][$fileinput]) && is_array($_G['cache']['pluginlanguage_system'][$fileinput])) {
        $_G['lang'][$key] = array_merge($_G['lang'][$key], $_G['cache']['pluginlanguage_system'][$fileinput]);
      }
      $_G['hooklang'][$fileinput] = true;
    }*/
    $returnvalue = &$_G['lang'];
  /*} else {
    if(empty($_G['config']['plugindeveloper'])) {
      loadcache('pluginlanguage_script');
    } elseif(!isset($_G['cache']['pluginlanguage_script'][$file]) && preg_match("/^[a-z]+[a-z0-9_]*$/i", $file)) {
      if(@include(SC_ROOT.'./data/plugindata/'.$file.'.lang.php')) {
        $_G['cache']['pluginlanguage_script'][$file] = $scriptlang[$file];
      } else {
        loadcache('pluginlanguage_script');
      }
    }
    $returnvalue = & $_G['cache']['pluginlanguage_script'];
    $key = &$file;
  }*/
  $return = $langvar !== null ? (isset($returnvalue[$key][$langvar]) ? $returnvalue[$key][$langvar] : null) : $returnvalue[$key];
  $return = $return === null ? ($default !== null ? $default : $langvar) : $return;
  $searchs = $replaces = array();
  if($vars && is_array($vars)) {
    foreach($vars as $k => $v) {
      $searchs[] = '{'.$k.'}';
      $replaces[] = $v;
    }
  }
  if(is_string($return) && strpos($return, '{_G/') !== false) {
    preg_match_all('/\{_G\/(.+?)\}/', $return, $gvar);
    foreach($gvar[0] as $k => $v) {
      $searchs[] = $v;
      $replaces[] = getglobal($gvar[1][$k]);
    }
  }
  $return = str_replace($searchs, $replaces, $return);
  return $return;
}

function checktplrefresh($maintpl, $subtpl, $timecompare, $templateid, $cachefile, $tpldir, $file) {
  static $tplrefresh, $timestamp, $targettplname;
  if($tplrefresh === null) {
    $tplrefresh = getglobal('config/output/tplrefresh');
    $timestamp = getglobal('timestamp');
  }

  if(empty($timecompare) || $tplrefresh == 1 || ($tplrefresh > 1 && !($timestamp % $tplrefresh))) {
    if(empty($timecompare) || @filemtime(SC_ROOT.$subtpl) > $timecompare) {
      require_once SC_ROOT.'/src/class/template.php';
      $template = new template();
      $template->parse_template($maintpl, $templateid, $tpldir, $file, $cachefile);
      if($targettplname === null) {
        $targettplname = getglobal('style/tplfile');
        if(!empty($targettplname)) {
          include_once libfile('func/block');
          $targettplname = strtr($targettplname, ':', '_');
          update_template_block($targettplname, getglobal('style/tpldirectory'), $template->blocks);
        }
        $targettplname = true;
      }
      return TRUE;
    }
  }
  return FALSE;
}

function template_sim($templ) {
  return SC_ROOT.'template/'.$templ;
}

function template($file, $templateid = 0, $tpldir = '', $gettplfile = 0, $primaltpl='') {
  global $_G;

  /*static $_init_style = false;
  if($_init_style === false) {
    C::app()->_init_style();
    $_init_style = true;
  }*/
  $oldfile = $file;
  /*
  if(strpos($file, ':') !== false) {
    $clonefile = '';
    list($templateid, $file, $clonefile) = explode(':', $file);
    $oldfile = $file;
    $file = empty($clonefile) ? $file : $file.'_'.$clonefile;
    if($templateid == 'diy') {
      $indiy = false;
      $_G['style']['tpldirectory'] = $tpldir ? $tpldir : (defined('TPLDIR') ? TPLDIR : '');
      $_G['style']['prefile'] = '';
      $diypath = SC_ROOT.'./data/diy/'.$_G['style']['tpldirectory'].'/'; //DIY模板文件目录
      $preend = '_diy_preview';
      $_GET['preview'] = !empty($_GET['preview']) ? $_GET['preview'] : '';
      $curtplname = $oldfile;
      $basescript = $_G['mod'] == 'viewthread' && !empty($_G['thread']) ? 'forum' : $_G['basescript'];
      if(isset($_G['cache']['diytemplatename'.$basescript])) {
        $diytemplatename = &$_G['cache']['diytemplatename'.$basescript];
      } else {
        if(!isset($_G['cache']['diytemplatename'])) {
          loadcache('diytemplatename');
        }
        $diytemplatename = &$_G['cache']['diytemplatename'];
      }
      $tplsavemod = 0;
      if(isset($diytemplatename[$file]) && file_exists($diypath.$file.'.htm') && ($tplsavemod = 1) || empty($_G['forum']['styleid']) && ($file = $primaltpl ? $primaltpl : $oldfile) && isset($diytemplatename[$file]) && file_exists($diypath.$file.'.htm')) {
        $tpldir = 'data/diy/'.$_G['style']['tpldirectory'].'/';
        !$gettplfile && $_G['style']['tplsavemod'] = $tplsavemod;
        $curtplname = $file;
        if(isset($_GET['diy']) && $_GET['diy'] == 'yes' || isset($_GET['diy']) && $_GET['preview'] == 'yes') { //DIY模式或预览模式下做以下判断
          $flag = file_exists($diypath.$file.$preend.'.htm');
          if($_GET['preview'] == 'yes') {
            $file .= $flag ? $preend : '';
          } else {
            $_G['style']['prefile'] = $flag ? 1 : '';
          }
        }
        $indiy = true;
      } else {
        $file = $primaltpl ? $primaltpl : $oldfile;
      }
      $tplrefresh = $_G['config']['output']['tplrefresh'];
      if($indiy && ($tplrefresh ==1 || ($tplrefresh > 1 && !($_G['timestamp'] % $tplrefresh))) && filemtime($diypath.$file.'.htm') < filemtime(SC_ROOT.$_G['style']['tpldirectory'].'/'.($primaltpl ? $primaltpl : $oldfile).'.htm')) {
        if (!updatediytemplate($file, $_G['style']['tpldirectory'])) {
          unlink($diypath.$file.'.htm');
          $tpldir = '';
        }
      }

      if (!$gettplfile && empty($_G['style']['tplfile'])) {
        $_G['style']['tplfile'] = empty($clonefile) ? $curtplname : $oldfile.':'.$clonefile;
      }

      $_G['style']['prefile'] = !empty($_GET['preview']) && $_GET['preview'] == 'yes' ? '' : $_G['style']['prefile'];

    } else {
      $tpldir = './source/plugin/'.$templateid.'/template';
    }
  }*/

  $file .= !empty($_G['inajax']) && ($file == 'common/header' || $file == 'common/footer') ? '_ajax' : '';
  $tpldir = $tpldir ? $tpldir : (defined('TPLDIR') ? TPLDIR : '');
  $templateid = $templateid ? $templateid : (defined('TEMPLATEID') ? TEMPLATEID : '');
  $filebak = $file;

  /*if(defined('IN_MOBILE') && !defined('TPL_DEFAULT') && strpos($file, $_G['mobiletpl'][IN_MOBILE].'/') === false || (isset($_G['forcemobilemessage']) && $_G['forcemobilemessage'])) {
    if(IN_MOBILE == 2) {
      $oldfile .= !empty($_G['inajax']) && ($oldfile == 'common/header' || $oldfile == 'common/footer') ? '_ajax' : '';
    }
    $file = $_G['mobiletpl'][IN_MOBILE].'/'.$oldfile;
  }*/

  if(!$tpldir) {
    $tpldir = './template/default';
  }
  if (strpos($file, '.') !== false) {
    $tplfile = $tpldir.'/'.$file;
    return SC_ROOT.$tplfile;
  }
  $tplfile = $tpldir.'/'.$file.'.htm';

  $file == 'common/header' && defined('CURMODULE') && CURMODULE && $file = 'common/header_'.$_G['basescript'].'_'.CURMODULE;

  /*if(defined('IN_MOBILE') && !defined('TPL_DEFAULT')) {
    if(strpos($tpldir, 'plugin')) {
      if(!file_exists(SC_ROOT.$tpldir.'/'.$file.'.htm') && !file_exists(SC_ROOT.$tpldir.'/'.$file.'.php')) {
        discuz_error::template_error('template_notfound', $tpldir.'/'.$file.'.htm');
      } else {
        $mobiletplfile = $tpldir.'/'.$file.'.htm';
      }
    }
    !$mobiletplfile && $mobiletplfile = $file.'.htm';
    if(strpos($tpldir, 'plugin') && (file_exists(SC_ROOT.$mobiletplfile) || file_exists(substr(SC_ROOT.$mobiletplfile, 0, -4).'.php'))) {
      $tplfile = $mobiletplfile;
    } elseif(!file_exists(SC_ROOT.TPLDIR.'/'.$mobiletplfile) && !file_exists(substr(SC_ROOT.TPLDIR.'/'.$mobiletplfile, 0, -4).'.php')) {
      $mobiletplfile = './template/default/'.$mobiletplfile;
      if(!file_exists(SC_ROOT.$mobiletplfile) && !$_G['forcemobilemessage']) {
        $tplfile = str_replace($_G['mobiletpl'][IN_MOBILE].'/', '', $tplfile);
        $file = str_replace($_G['mobiletpl'][IN_MOBILE].'/', '', $file);
        define('TPL_DEFAULT', true);
      } else {
        $tplfile = $mobiletplfile;
      }
    } else {
      $tplfile = TPLDIR.'/'.$mobiletplfile;
    }
  }*/

  $cachefile = './data/template/'.(defined('STYLEID') ? STYLEID.'_' : '_').$templateid.'_'.str_replace('/', '_', $file).'.tpl.php';
  if($templateid != 1 && !file_exists(SC_ROOT.$tplfile) && !file_exists(substr(SC_ROOT.$tplfile, 0, -4).'.php')
      && !file_exists(SC_ROOT.($tplfile = $tpldir.$filebak.'.htm'))) {
    $tplfile = './template/default/'.$filebak.'.htm';
  }

  if($gettplfile) {
    return $tplfile;
  }
  checktplrefresh($tplfile, $tplfile, @filemtime(SC_ROOT.$cachefile), $templateid, $cachefile, $tpldir, $file);
  return SC_ROOT.$cachefile;
}

?>