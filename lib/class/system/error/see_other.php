<?

namespace System\Error
{
	class SeeOther extends \System\Error\Request
	{
		const HTTP_STATUS = 303;
		const REDIRECTABLE = true;
	}
}
