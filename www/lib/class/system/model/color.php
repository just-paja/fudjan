<?

namespace System\Model
{
	abstract class Color extends Basic
	{
		public function get_color()
		{
			return array($this->red, $this->green, $this->blue, $this->alpha);
		}
	}
}
