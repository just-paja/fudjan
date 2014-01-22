<?

/** System locale settings
 * @package system
 */
namespace System
{
	/** System locale settings
	 * @package system
	 */
	class Locales
	{
		const DIR = '/etc/locales';
		const ENCODING = 'UTF-8';
		const LANG_DEFAULT = 'en';
		const TZ_DEFAULT = 'Europe/Prague';

		const TRANS_NONE = 0;
		const TRANS_STD  = 1;
		const TRANS_INF  = 2;


		/** Loaded files */
		private $files = array();

		private $locale;
		private $lang;
		private $date_trans;
		private $response;

		/** Loaded messages */
		private $messages = array();

		/** Static messages */
		private static $messages_static = array(
			"date" => array(
				"std"        => 'D, d M Y G:i:s e',
				"sql"        => 'Y-m-d H:i:s',
				"sql-date"   => 'Y-m-d',
				"sql-time"   => 'H:i:s',
				"html5"      => 'Y-m-d\\TH:i:s',
				"html5-full" => 'Y-m-d\\TH:i:sP',
			)
		);

		private static $attrs_common = array('author', 'created_at', 'updated_at');


		/** Class init. Inits mb extension and sets default timezone for dates
		 * @return void
		 */
		public static function init()
		{
			mb_language('uni');
			mb_internal_encoding(self::ENCODING);
			date_default_timezone_set(self::get_default_timezone());
		}


		/** Is selected locale available
		 * @param string $locale
		 * @return bool
		 */
		public static function is_locale_available($locale)
		{
			return is_dir(ROOT.self::DIR.'/'.$locale);
		}


		/** Get default language, if settings fail, skip
		 * @return string
		 */
		public static function get_default_lang()
		{
			try {
				return cfg('locales', 'default_lang');
			} catch (\System\Error\Config $e) {
				return self::LANG_DEFAULT;
			}
		}


		/** Get default language, if settings fail, skip
		 * @return string
		 */
		public static function get_default_timezone()
		{
			try {
				return cfg('locales', 'timezone');
			} catch (\System\Error\Config $e) {
				return self::TZ_DEFAULT;
			}
		}


		public static function create(\System\Http\Response $response, $locale)
		{
			$obj = new self();
			$obj->response = $response;

			return $obj->set_locale($locale);
		}


		/** Set a locale to object
		 * @param string $locale
		 * @return $this
		 */
		public function set_locale($locale = null)
		{
			$this->locale = (is_null($locale) || !self::is_locale_available($locale)) ? self::get_default_lang():$locale;
			return $this->load_messages();
		}


		public function make_syswide()
		{
			setlocale(LC_ALL, $this->locale.'.'.self::ENCODING);
			return $this;
		}


		/** Get locale description
		 * @return string
		 */
		public function get_locale()
		{
			return $this->locale;
		}


		/** Get language description
		 * @return string
		 */
		public function get_lang()
		{
			return $this->lang;
		}


		/** Get list of all loaded locale files
		 * @return array
		 */
		public function get_loaded_files()
		{
			return $this->files;
		}


		/** Get locale module data
		 * @param string      $str        Callstring
		 * @param null|string $force_lang Use this language
		 * @return mixed
		 */
		public function get_path($str)
		{
			$src = &$this->messages;

			if (strpos($str, ':')) {
				list($module, $str) = explode(':', $str, 2);
				$this->load_module($module);
				$src = &$this->messages[$module];
			}

			if (isset($src[$str])) {
				return $src[$str];
			}

			if (isset(self::$messages_static[$module][$str])) {
				return self::$messages_static[$module][$str];
			}

			return null;
		}


		/** Get all loaded messages for language
		 * @param string $lang
		 * @return array
		 */
		public function get_messages($locale = null)
		{
			return is_null($locale) ? $this->messages:$this->messages[$locale];
		}


		/** Translate string
		 * @param string       $str
		 * @param array|string $args Arguments to paste into the string. If not array, all arguments after str are written inside.
		 * @return string
		 */
		public function trans($str, $args = null)
		{
			$msg = isset($this->messages[$str]) ? $this->messages[$str]:$str;

			if (is_array($args) || (!is_null($args) && func_num_args() > 1)) {
				if (!is_array($args)) {
					$args = func_get_args();
					array_shift($args);
				}

				return vsprintf($msg, $args);
			} else return $msg;
		}


		/** Prepare data for translating datetimes
		 * @return $this
		 */
		private function load_date_translations()
		{
			if (is_null($this->date_trans)) {
				$def = self::create($this->response, self::LANG_DEFAULT);
				$this->date_trans = array(
					"find" => array_merge(
						$def->get_path('date:days'),
						$def->get_path('date:days-short'),
						$def->get_path('date:months'),
						$def->get_path('date:months-short')
					),

					"replace" => array_merge(
						$this->get_path('date:days'),
						$this->get_path('date:days-short'),
						$this->get_path('date:months'),
						$this->get_path('date:months-short')
					),

					"replace_hard" => array_merge(
						$this->get_path('date:days'),
						$this->get_path('date:days-short'),
						$this->get_path('date:months-date'),
						$this->get_path('date:months-short')
					),
				);
			}

			return $this;
		}


		/** Translate date by locale standards
		 * @param string $date
		 * @param bool $hard
		 * @return string
		 */
		public function translate_date($date, $hard = false)
		{
			$this->load_date_translations();
			$replace_key = 'replace';
			if ($hard) {
				$replace_key = 'replace_hard';
			}

			return str_replace($this->date_trans['find'], $this->date_trans[$replace_key], strtolower($date));
		}


		/** Load all messages by language
		 * @param string $lang
		 * @return void
		 */
		private function load_messages($locale = null)
		{
			if (is_null($locale)) {
				$locale = $this->locale;
			}

			if (!isset($this->messages[$locale])) {
				$this->messages[$locale] = \System\Settings::read(self::DIR.'/'.$locale, false, $this->files);
			}

			return $this;
		}


		/** Calculate binary length of UTF-8 string
		 * @param string $str
		 * @return int
		 */
		public static function strlen_binary($str)
		{
			$strlen_var = strlen($str);
			$d = 0;

			for ($c = 0; $c < $strlen_var; ++$c) {
				$ord_var_c = ord($str{$d});

				switch (true) {
					case (($ord_var_c >= 0x20) && ($ord_var_c <= 0x7F)):
						$d++;
						break;

					case (($ord_var_c & 0xE0) == 0xC0):
						$d+=2;
						break;

					case (($ord_var_c & 0xF0) == 0xE0):
						$d+=3;
						break;

					case (($ord_var_c & 0xF8) == 0xF0):
						$d+=4;
						break;

					case (($ord_var_c & 0xFC) == 0xF8):
						$d+=5;
						break;

					case (($ord_var_c & 0xFE) == 0xFC):
						$d+=6;
						break;

					default:
						$d++;
				}
			}

			return $d;
		}


		/** Format and translate datetime format
		 * @param DateTime|int|null $date      Date to format. Takes current time if null.
		 * @param string            $format    Format name or format directly
		 * @param int               $translate 0 for no translation, 1 for standart translation, 2 for special translation
		 * @return string
		 */
		public function format_date($date, $format = 'std', $translate = self::TRANS_STD)
		{
			if (\System\Template::is_date($date)) {
				if (is_null($date)) {
					$date = new \DateTime();
				} elseif (is_numeric($date)) {
					$helper = new \DateTime();
					$helper->setTimestamp($date);
					$date = $helper;
				}

				$local_format = \System\Locales::get_path('date:'.$format);
				$d = $date->format(is_null($local_format) ? $format:$local_format);

				if ($translate == self::TRANS_NONE) {
					return $d;
				} else {
					return $this->translate_date($d, $translate == self::TRANS_INF);
				}

			} else throw new \System\Error\Argument(sprintf("Method format_date accepts only date type arguments. Instance of DateTime or utime number. '%s' was given.", gettype($date)));
		}


		/** Get class translation from class format
		 * @param string $class_name Class name in class format
		 * @param bool   $plural     Return plural
		 * @return string
		 */
		public function trans_class_name($class_name, $plural = false)
		{
			return $this->trans('model_'.\System\Loader::get_link_from_class($class_name).($plural ? '_plural':''));
		}



		/** Get translated attribute name
		 * @param string $model
		 * @param string $attr
		 * @return string
		 */
		public function trans_model_attr_name($model, $attr)
		{
			return $this->trans(self::get_common_attr_trans_name($model, $attr));
		}


		/** Get translated attribute description
		 * @param string $model
		 * @param string $attr
		 * @return string
		 */
		public function trans_model_attr_desc($model, $attr)
		{
			return $this->trans('attr_'.\System\Loader::get_link_from_class($model).'_'.$attr.'_desc');
		}


		/** Get string constant for common attributes
		 * @param string $model
		 * @param string $attr
		 * @return string
		 */
		public static function get_common_attr_trans_name($model, $attr)
		{
			return 'attr_'.(in_array($attr, self::$attrs_common) ? $attr:(\System\Loader::get_link_from_class($model).'_'.$attr));
		}
	}
}
