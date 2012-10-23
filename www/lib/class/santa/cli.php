<?

namespace Santa
{
	abstract class Cli
	{
		private static $branch_colors = array(
			'stable'   => 'light_green',
			'master'   => 'light_green',
			'unstable' => 'yellow',
			'testing'  => 'light_red',
		);


		public static function get_branch_color($branch)
		{
			return isset(self::$branch_colors[$branch]) ? self::$branch_colors[$branch]:'normal';
		}


		public static function get_branch_color_text($text, $branch)
		{
			return \System\Cli::term_color($text, self::get_branch_color($branch));
		}

	}
}
