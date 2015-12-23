<?php


/** Module that displays partial without locals
 * @package modules
 */
namespace Module\System\Text
{
	class Show extends \System\Module
	{
		public function run()
		{
			try {
				$this->req('ident');
			} catch(\System\Error\Argument $e) {
				$this->req('id');
			}

			def($this->template, 'system/text/show');
			def($this->show_heading, true);

			$conds = array();

			if (isset($ident)) {
				$conds['ident'] = $ident;
			} else {
				$conds['id_system_text'] = $id;
			}

			if ($text = \System\Text::get_first($conds)->fetch()) {

				$this->partial($template, array(
					"text" => $text,
					"show_heading" => $show_heading,
				));

			} else {
				if (cfg('dev', 'debug', 'backend')) {
					throw new \System\Error\Config('Text was not found', isset($ident) ? $ident:$id);
				} else {
					throw new \System\Error\NotFound();
				}
			}
		}
	}
}
