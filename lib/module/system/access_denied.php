<?php

namespace Module\System
{
  class AccessDenied extends \System\Module
  {
    public function run()
    {
      throw new \System\Error\AccessDenied();
    }
  }
}
