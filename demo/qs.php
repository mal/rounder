<?php

  class Querystring
  {
    private $vars = null;
    
    public function __construct()
    {
      $this->vars = ($_SERVER['REQUEST_METHOD'] == 'GET') ? $_GET : $_POST;
      foreach ($this->vars as $key => &$val)
        $val = is_array($val) ? $this->arrayEscape($val) : addslashes($val);
    }
    
    public function get($key)
    {
      if ( $this->exists($key) )
        return $this->vars[$key] ? $this->vars[$key] : false;
      else
        return false;
    }
    
    public function exists($key)
    {
      return array_key_exists($key, $this->vars);
    }
  }

?>
