<?php

namespace System
{
  abstract class Status
  {
    const DIR_LOGS = '/var/log';

    private static $log_files = array();


    public static function on_cli()
    {
      return php_sapi_name() == 'cli';
    }


    /** Introduce pwf name and version
     * @return string
     */
    public static function introduce()
    {
      return 'fudjan';
    }


    /**
     * Write error into log file
     *
     * @param string $type
     * @param string $msg
     * @return void
     */
    public static function report($type, $report)
    {
      $debug = \System\Settings::getSafe(array('dev', 'debug', 'backend'), false);
      \System\Directory::check(BASE_DIR.self::DIR_LOGS);
      $log_to = fopen(BASE_DIR.self::DIR_LOGS.'/'.$type.'.log', 'a+');

      if (!$debug && $type == 'error') {
        $rcpt = \System\Settings::getSafe(array('dev', 'mailing', 'errors'), null);

        if ($rcpt) {
          \Helper\Offcom\Mail::create('[Fudjan] Server error', $report, $rcpt)->send();
        }
      }

      if (is_resource($log_to)) {
        fwrite($log_to, $report);
      }
    }

    /**
     * Get name of the given exception
     *
     * @param object $exception
     * @return string
     */
    public static function getExceptionName($exception)
    {
        $name = get_class($exception);

        if (0 !== ($code = $exception->getCode())) {
            $name .= " ({$code})";
        }

        return $name;
    }

    public static function getExceptionMessage($exc)
    {
      return sprintf(
        "[%s] %s - %s in file %s on line %d\n",
        date('Y-m-d H:i:s'),
        static::getExceptionName($exc),
        $exc->getMessage(),
        $exc->getFile(),
        $exc->getLine()
      );
    }


    /**
     * General exception handler - Catches exception and displays error
     *
     * @param \Exception $e
     * @param bool $debug
     */
    public static function filterException($e, $debug = false)
    {
      if ($e instanceof \System\Error\Request && $e::REDIRECTABLE && $e->location) {
        header('Location: '. $e->location);
        exit(0);
      }

      if (!$debug) {
        header('HTTP/1.1 500 Internal Server Error');
        echo "Fatal error";
        exit(1);
      }

      if (!($e instanceof \System\Error)) {
        return;
      }

      // Get error display definition
      try {
        $errors = \System\Settings::get('output', 'errors');
        $cfg_ok = true;
      } catch(\System\Error\Config $exc) {
        return;
      }

      // Find error display template
      if (array_key_exists($e->get_name(), $errors)) {
        $errorPage = $errors[$e->get_name()];
      } else {
        return;
      }

      if (self::on_cli()) {
        return;
      }

      // Setup output format for error page
      $errorPage['format'] = 'html';
      $errorPage['render_with'] = 'basic';

      if (!is_array($errorPage['partial'])) {
        $errorPage['partial'] = array($errorPage['partial']);
      }

      $request = \System\Http\Request::from_hit();

      $response = $request->create_response($errorPage);
      $response->create_renderer();

      foreach ($errorPage['partial'] as $partial) {
        $response->renderer->partial($partial, array(
          'status'  => $e->get_http_status(),
          'desc'    => $e,
          'message' => $e->get_explanation(),
          'wrap' => false,
        ));
      }

      $response
        ->status($e->get_http_status())
        ->render()
        ->send_headers()
        ->send_content();

      exit(1);
    }


    public static function init()
    {
      $whoops = new \Whoops\Run;

      if (static::on_cli()) {
        $whoops->pushHandler(new \Whoops\Handler\PlainTextHandler());
      } else {
        $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler());
      }

      $whoops->pushHandler(function($exc, $inspector, $run) {
        $debug = \System\Settings::getSafe(array('dev', 'debug', 'backend'), true);
        static::filterException($exc, $debug);
        static::report('error', static::getExceptionMessage($exc));
      });

      $whoops->register();
    }
  }
}
