<?php

namespace App\Utils;

class ClassUtils extends BaseUtils
{
  /**
   * Creates and returns an instance of the requested class.
   * 
   * @param string $className
   * @param array $args
   * @param string $namespace The namespace to prepend to $className if it is not a FQN.
   * @return mixed
   */
  public function instantiate(
    string $className, array $args, string $namespace = ''
  ) {
    if (!empty($namespace) && substr($className, 0, 1) !== '\\') {
      if (substr($namespace, 0, 1) !== '\\') {
        $namespace = "\\${namespace}";
      }

      $className = "${namespace}\\${className}";
    }

    return(new $className(...$args));
  }
}