<?php

namespace Module\System
{
  class NotFound extends \System\Module
  {
    public function run()
    {
      throw new \System\Error\NotFound();
    }
  }
}
