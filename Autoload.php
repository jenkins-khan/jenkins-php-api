<?php


class Jenkins_Autoloader
{

  /**
   * @static
   *
   */
  static public function register()
  {
    ini_set('unserialize_callback_func', 'spl_autoload_call');
    spl_autoload_register(array(new self, 'autoload'));
  }

  /**
   * @static
   *
   * @param string $class
   *
   * @return null
   */
  static public function autoload($class)
  {
    if (0 !== strpos($class, 'Jenkins'))
    {
      return;
    }

    if (is_file($file = dirname(__FILE__) . '/' . str_replace(array('_', "\0"), array('/', ''), $class) . '.php'))
    {
      require $file;
    }

  }
}