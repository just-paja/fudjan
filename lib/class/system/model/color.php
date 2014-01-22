<?

/** Color model handling
 * @package system
 * @subpackage models
 */
namespace System\Model
{
	/** Standartized model for colors. Used when you give user color options.
	 * @package system
	 * @subpackage models
	 */
	abstract class Color extends Database
	{
		/** Get color
		 * @return array
		 */
		public function get_color()
		{
			return array($this->red, $this->green, $this->blue, $this->alpha);
		}
	}
}
