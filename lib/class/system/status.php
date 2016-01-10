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
      try {
        $debug = \System\Settings::get('dev', 'debug', 'backend');
      } catch(\System\Error\Config $e) {
        $debug = false;
      }

      \System\Directory::check(BASE_DIR.self::DIR_LOGS);
      $log_to = fopen(BASE_DIR.self::DIR_LOGS.'/'.$type.'.log', 'a+');

      if (!$debug && $type == 'error') {
        try {
          $rcpt = \System\Settings::get('dev', 'mailing', 'errors');
        } catch(\System\Error\Config $e) {
          $rcpt = null;
        }

        if ($rcpt) {
          \Helper\Offcom\Mail::create('[Fudjan] Server error', $report, $rcpt)->send();
        }
      }

      if (is_resource($log_to)) {
        fwrite($log_to, $report);
      }
    }


    public static function getExceptionMessage(\Exception $exc)
    {
      return sprintf(
        "[%s] %s - %s in file %s on line %d\n",
        date('Y-m-d H:i:s'),
        \Kuria\Error\Util\Debug::getExceptionName($exc),
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
    public static function filterException(\Exception $e, $debug = false)
    {
      if (!($e instanceof \System\Error)) {
        return;
      }

      if ($e instanceof \System\Error\Request && $e::REDIRECTABLE && $e->location) {
        header('Location: '. $e->location);
        exit(0);
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

      try {
        $response->create_renderer();

        foreach ($errorPage['partial'] as $partial) {
          $response->renderer->partial($partial, array(
            'status'  => $e->get_http_status(),
            'desc'    => $e,
            'message' => $e->get_explanation(),
            'wrap' => false,
          ));
        }
      } catch (\Exception $exc) {
        header('HTTP/1.1 500 Internal Server Error');
        echo "Fatal error when rendering exception details";
        exit(1);
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
      try {
        $debug = \System\Settings::get('dev', 'debug', 'backend');
      } catch(\System\Error\Config $e) {
        $debug = false;
      }

      $errorHandler = new \Kuria\Error\ErrorHandler($debug);
      $errorHandler->register();

      $errorHandler->on('fatal', function($exc, $debug, &$handler) {
        static::filterException($exc, $debug);
        static::report('error', static::getExceptionMessage($exc));
      });

      ini_set('log_errors',     true);
      ini_set('display_errors', false);
      ini_set('html_errors',    false);
    }
  }
}
