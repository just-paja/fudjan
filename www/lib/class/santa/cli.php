<?

/** CLI methods for Santa
 * @package santa
 */
namespace Santa
{
	/** CLI methods for santa
	 */
	abstract class Cli
	{
		/** Available colors for branches
		 * @param array
		 */
		private static $branch_colors = array(
			'stable'   => 'light_green',
			'master'   => 'light_green',
			'unstable' => 'yellow',
			'testing'  => 'light_red',
		);


		/** Get color by branch name
		 * @param string $branch Branch name
		 * @return string
		 */
		public static function get_branch_color($branch)
		{
			return isset(self::$branch_colors[$branch]) ? self::$branch_colors[$branch]:'normal';
		}


		/** Get colored text by branch name
		 * @param string $text   Text
		 * @param string $branch Branch name
		 * @return string
		 */
		public static function get_branch_color_text($text, $branch)
		{
			return \System\Cli::term_color($text, self::get_branch_color($branch));
		}

	}
}
