<?

namespace System\Form\Input
{
	class File extends \System\Form\Input
	{
		private $resolved_value;


		public function is_valid()
		{
			$valid = parent::is_valid();
			$value = $this->val();

			if ($value) {
			}

			return $valid;
		}


		public function val_get()
		{
			if ($this->resolved_value) {
				$val = $this->resolved_value;
			} else {
				$val = $this->form()->input_value($this->name);
			}

			if (gettype($val) == 'string') {
				$val = \System\Json::decode($val);
			}

			if (is_array($val)) {
				if ($val['method'] == 'url') {
					$val = \System\File::fetch($val['url'])->temp()->unload();
					$val->temp = false;
					$val->method = 'keep';
				} else if ($val['method'] == 'upload') {
					$file = $this->form()->request->post($val['upload']);
					$file = \System\File::from_tmp($file['tmp_name'], $val['name']);

					$file->temp();
					$file->temp = false;
					$file->method = 'keep';
					$file->mime = $val['mime'];
					$val = $file;
				} else if ($val['method'] == 'save') {
					$file = \System\File::from_path($val['path']);
					$file->read_meta();
					$val = $file;
				} else if ($val['method'] == 'drop') {
					$val = null;
				}
			}

			$this->resolved_value = $val;

			return $val;
		}
	}
}
