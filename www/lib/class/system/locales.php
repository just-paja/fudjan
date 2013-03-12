<?

namespace System
{
	class Locales
	{
		const DIR = '/etc/locales';
		const DIR_MESSAGES = '/messages.d';
		const DIR_MODULES = '/modules.d';
		const ENCODING = 'UTF-8';
		const LANG_DEFAULT = 'en_US';
		const KEY_MESSAGES = 'messages';

		private static $lang;
		private static $messages = array();
		private static $files = array();


		/** Class init
		 * @return void
		 */
		public static function init()
		{
			mb_language('uni');
			mb_internal_encoding(self::ENCODING);
			date_default_timezone_set(cfg('locales', 'timezone'));
			self::set_locale();
		}


		/** Get list of all loaded locale files
		 * @return array
		 */
		public static function get_loaded_files()
		{
			return self::$files;
		}


		/** Set locale by configured language
		 * @return void
		 */
		private static function set_locale()
		{
			$lang = self::get_lang();
			$lang_parts = explode('_', $lang);

			if (isset($lang_parts[1])) {
				$lang_parts[1] = strtoupper($lang_parts[1]);
			}

			$lang = implode('_', $lang_parts);
			setlocale(LC_ALL, self::get_lang().'.'.self::ENCODING);
		}


		/** Get locale module data
		 * @param string      $str        Callstring
		 * @param null|string $force_lang Use this language
		 * @return mixed
		 */
		public static function get($str, $force_lang = NULL)
		{
			$lang = $force_lang ? $force_lang:self::get_lang();
			$src = &self::$messages[$lang];

			if (strpos($str, ':')) {
				list($module, $str) = explode(':', $str, 2);
				self::load($module, $lang);
				$src = &self::$messages[$lang][$module];
			}

			return isset($src[$str]) ? $src[$str]:$str;
		}


		/** Get all loaded messages for language
		 * @param string $lang
		 * @return array
		 */
		public static function get_all_messages($lang = null)
		{
			return self::$messages[is_null($lang) ? self::get_lang():$lang];
		}


		/** Translate string
		 * @param string      $str
		 * @param null|string $force_lang
		 * @return string
		 */
		public static function translate($str, $force_lang = NULL)
		{
			$lang = $force_lang ? $force_lang:self::get_lang();
			self::load_messages($lang);
			return isset(self::$messages[self::$lang][self::KEY_MESSAGES][$str]) ? self::$messages[self::$lang][self::KEY_MESSAGES][$str]:$str;
		}


		/** Translate and replace arguments if necessary
		 * @param string $str
		 * @param array  $data Data to replace
		 * @return string
		 */
		public static function translate_and_replace($str, array $data)
		{
			unset($data[0]);
			return vsprintf(self::translate($str), $data);
		}


		/** Load module
		 * @param string $module
		 * @param null|string $force_lang
		 * @return void
		 */
		private static function load($module, $force_lang = NULL)
		{
			if ($module === self::KEY_MESSAGES) {
				throw new \System\Error\Argument(sprintf('Locales module must not be named %s', $module));
			}

			$lang = $force_lang ? $force_lang:self::get_lang();

			if (!isset(self::$messages[$lang][$module])) {
				if (!file_exists($f = ($p = ROOT.self::DIR.'/'.$lang.self::DIR_MODULES.'/'.$module).'.json')) {
					$f = $p.'.core.json';
				}

				self::$messages[$lang][$module] = \System\Json::read($f);
				self::$files[$lang][] = str_replace(ROOT, '', $f);

				if (empty(self::$messages[$lang][$module])) {
					Status::report('error', sprintf('Locales module %s/%s is empty or broken', $lang, $module));
				}
			}
		}


		/** Get language and locale settings shortcut
		 * @return string
		 */
		static function get_lang()
		{
			if (self::$lang) {
				return self::$lang;
			} else {
				return self::set_lang(Input::get('lang') ?
					(Input::get('lang')):
					(isset($_SESSION['lang']) ?
						$_SESSION['lang']:
						cfg("locales", 'default_lang'))
				);
			}
		}


		/** Set locale environment
		 * @param string $lang
		 * @return string Resulted language
		 */
		static function set_lang($lang)
		{
			$_SESSION['lang'] = self::$lang = $lang;
			return self::$lang;
		}


		/** Translate date by locale standards
		 * @param string $date
		 * @param bool $hard
		 * @return string
		 */
		public static function translate_date($date, $hard = false)
		{
			static $find, $replace_std, $replace_hard;

			if (!isset($find))
			{
				$find = array_merge(
					Locales::get('date:days', self::LANG_DEFAULT),
					Locales::get('date:days-short', self::LANG_DEFAULT),
					Locales::get('date:months', self::LANG_DEFAULT),
					Locales::get('date:months-short', self::LANG_DEFAULT)
				);

				$replace_std = array_merge(
					Locales::get('date:days'),
					Locales::get('date:days-short'),
					Locales::get('date:months'),
					Locales::get('date:months-short')
				);

				$replace_hard = array_merge(
					Locales::get('date:days'),
					Locales::get('date:days-short'),
					Locales::get('date:months-date'),
					Locales::get('date:months-short')
				);
			}

			if ($hard) {
				$replace = &$replace_std;
			} else {
				$replace = &$replace_hard;
			}

			$date = str_replace($find, $replace, strtolower($date));
			return $date;
		}


		/** Load all messages by language
		 * @param string $lang
		 * @return void
		 */
		private static function load_messages($lang)
		{
			($d = !isset(self::$messages[$lang][self::KEY_MESSAGES])) && \System\Json::read_dist(
				ROOT.self::DIR.'/'.self::get_lang().self::DIR_MESSAGES,
				self::$messages[$lang][self::KEY_MESSAGES],
				false,
				self::$files[$lang]
			);
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
	}
}
