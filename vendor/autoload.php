<?php
$path = __DIR__;
$path = explode(DIRECTORY_SEPARATOR, $path);
$path = array_slice($path, 0, count($path) - 1);
$path = implode(DIRECTORY_SEPARATOR, $path);

/** Modificações de headers **/
spl_autoload_register(function ($class) use ($path) {
  // SE FOR DE namespace USA ROTINA PARA IMPORTAR
  if (strpos($class, '\\') !== false) {
    $prefixes = array(
      'Express\\' => $path  . DIRECTORY_SEPARATOR,
    );

    list($prefix) = explode('\\', $class);
    $prefix = $prefix . '\\';
    if (!array_key_exists($prefix, $prefixes)) {
      return; // não é uma classe do nosso projeto
    }
    $base_dir = $prefixes[$prefix];
    $len      = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
      return; // Não é uma classe do nosso projeto
    }
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', DIRECTORY_SEPARATOR, $relative_class) . '.php';
    if (file_exists($file)) {
      require_once $file;
      return; // Não é uma classe do nosso projeto
    }
    $programa = explode('\\', $class);
    $programa = $programa[count($programa) - 1];
  }
});