<?php

define('UNIXCOMP_NET_CLASSES_PATH', 'lib');
define('UNIXCOMP_NET_CLASSFILE_EXTENSION', 'php');

define('UNIXCOMP_NET_LIB_ROOT', 'lib');

// Регистрация автозагрузчика классов
spl_autoload_register('unixcomp_net_classes_autoloader');

// Установка пути загрузки
set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ .'/'. UNIXCOMP_NET_LIB_ROOT);

/** 
 * Автозагрузчик классов
 */
function unixcomp_net_classes_autoloader($namespaced_class_name) {
  $file = __DIR__ .'/'. UNIXCOMP_NET_CLASSES_PATH .'/';
  $items = explode("\\", $namespaced_class_name);
  $class = array_pop($items);
  
  if (!empty($items)) {
    foreach ($items as $item) {
      $file .= "$item/";
    }
  }
  $file .= $class .'.'. UNIXCOMP_NET_CLASSFILE_EXTENSION;

  if (file_exists($file)) {
    require_once $file;
  }
  else {
    $msg = sprintf("Unable to load class [ %s ] by path: %s", $class, $file);
    trigger_error($msg, E_USER_ERROR);
  }
}

/**
 * Загрузка отдельных файлов библиотек
 */
function unixcomp_net_require($file, $once = TRUE) {
  $self_path = __DIR__;

  $require_file = sprintf("%s/%s/%s", $self_path, UNIXCOMP_NET_LIB_ROOT, $file);
  if (is_file($require_file)) {
    if ($once) {
      require_once $require_file;
    }
    else {
      require $require_file;
    }
    return $require_file;
  }
  else {
    return FALSE;
  }
}

