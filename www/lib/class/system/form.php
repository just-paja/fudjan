<?

namespace System
{
	class Form extends \System\Model\Attr
	{
		const SEPARATOR_ID = '_';
		const SEPARATOR_INPUT_METHOD = 'input_';
		const TEMPLATE_DEFAULT = 'system/form';
		const LABEL_SUBMIT_DEFAULT = 'send';

		protected static $attrs = array(
			"id"      => array('varchar'),
			"method"  => array('varchar'),
			"action"  => array('varchar'),
			"enctype" => array('varchar'),
			"heading" => array('varchar'),
			"desc"    => array('varchar'),
			"anchor"  => array('varchar'),
			"bool"    => array('no_prefix'),
			"class"   => array('array'),
		);

		private static $methods_allowed = array('get', 'post', 'put', 'delete');

		protected $data_default  = array();
		protected $data_commited = array();
		protected $data_hidden   = array();

		private $objects = array();
		private $rendering = array(
			"group" => false,
			"tab"   => false,
		);

		private $prefix = '';
		protected $checkboxes = array();
		protected $counts = array(
			'inputs' => 1,
		);
		protected $errors = array();

		private static $inputs_datetime = array("datetime", "date", "time");
		private static $inputs_button = array("button", "submit");

		/** Constructor addon
		 * @return void
		 */
		protected function construct()
		{
			!$this->method  && $this->method = 'post';
			!$this->action  && $this->action = \System\Input::get('path');
			!$this->id      && $this->id = self::get_generic_id();
			!$this->anchor  && $this->anchor = \System\Model\Database::gen_seoname($this->id, true);
			!$this->enctype && $this->enctype = 'multipart/form-data';

			if (is_array($this->default)) {
				$this->data_default = $this->default;
			}

			$this->class = array_merge((array) $this->class, array('yaform'));
			$this->take_data_from_input();

			$this->hidden('submited', true);
			$this->data_default['submited'] = false;
		}


		/** Alias to create simple input type
		 * @param string $name Name of called method
		 * @param array  $args Arguments to the function
		 */
		public function __call($name, $args)
		{
			if (strpos($name, self::SEPARATOR_INPUT_METHOD) === 0) {
				$type = substr($name, strlen(self::SEPARATOR_INPUT_METHOD));

				if (!isset($args[0])) {
					throw new \System\Error\Argument(sprintf('You must enter input name as first argument for System\\Form::%s method', $name));
				}

				$this->input(array(
					"type"     => $type,
					"name"     => $args[0],
					"label"    => def($args[1], ''),
					"required" => def($args[2], false),
					"info"     => def($args[3], ''),
				));

			} else throw new \System\Error\Wtf(sprintf('There is no form method "%s".', $name));
		}


		/** Lookup commited data in input class
		 * @returns void
		 */
		protected function take_data_from_input()
		{
			$this->data_commited = \System\Input::get_by_prefix($this->get_prefix());

			if (isset($this->data_commited['data_hidden'])) {
				$this->data_hidden = json_decode(htmlspecialchars_decode($this->data_commited['data_hidden']), true);

				foreach ($this->data_hidden as $key=>$val) {
					$this->data_commited[$key] = $val;
				}

				unset($this->data_commited['data_hidden']);
			}

			$this->submited = isset($this->data_commited['submited']) ? !!$this->data_commited['submited']:false;
		}


		/** Get value of input by name
		 * @param array $attrs Input attributes
		 * @returns mixed
		 */
		protected function get_input_value($attrs)
		{
			$value = null;

			if (isset($attrs['value'])) {
				$value = $this->data_default[$attrs['name']] = $attrs['value'];
			} else if (isset($this->data_default[$attrs['name']])) {
				$value = $this->data_default[$attrs['name']];
			}

			if ($this->submited) {
				if (isset($this->data_commited[$attrs['name']])) {
					$value = $this->data_commited[$attrs['name']];
				} else {
					unset($attrs['value']);
				}
			}

			return $value;
		}


		protected function get_input_value_by_name($name, $default = false)
		{
			$value = null;

			if (($default || !$this->submited) && isset($this->data_default[$name])) {
				$value = $this->data_default[$name];
			}

			if (!$default && $this->submited && isset($this->data_commited[$name])) {
				$value = $this->data_commited[$name];
			}

			return $value;
		}


		/** Get generic ID for this form
		 * @returns string
		 */
		protected function get_generic_id()
		{
			return implode(self::SEPARATOR_ID, array('form', substr(md5($this->action), 0, 8)));
		}


		/** Add object to forms' set of objects
		 * @param System\Form\Element $element
		 * @returns void
		 */
		protected function add_object(\System\Form\Element $element)
		{
			$this->objects[] = $element;
		}


		/** Start rendering form element container
		 * @param string $type Type of element container (see System\Form\Container)
		 * @param string $name
		 * @param string $label
		 */
		public function group_start($type, $name = '', $label = '')
		{
			$el = new \System\Form\Container(array(
				"name"  => $name ? $name:'',
				"label" => $label,
				"form"  => &$this,
				"type"  => $type,
			));

			$this->objects[$el->name] = $el;
			$this->rendering['group'] = $this->objects[$el->name];
			return $this->rendering['group'];
		}


		/** Stop rendering form element container
		 * @returns void
		 */
		public function group_end()
		{
			$this->rendering['group'] = false;
		}


		/** Check if form container is on, start it otherwise
		 * @param string $type
		 * @returns void
		 */
		public function check_rendering_group($type)
		{
			if ($this->rendering['group'] === false || $this->rendering['group']->type != $type) {
				$this->group_start($type, count($this->objects));
			}

			return $this->rendering['group'];
		}


		/** Get generic object name
		 * @returns string
		 */
		public function gen_obj_name($type)
		{
			return implode(self::SEPARATOR_ID, array($this->id, $this->inputs_count));
		}


		/** Is the form ready for processing
		 * @returns bool
		 */
		public function passed()
		{
			return $this->submited;
		}


		/** Get count of element type
		 * @param string $type
		 */
		public function get_count($type)
		{
			if (!isset($this->counts[$type])) {
				$this->counts[$type] = 0;
			}

			return $this->counts[$type];
		}


		/** Add hidden data
		 * @param string $name
		 * @param mixed  $value
		 */
		public function hidden($name, $value)
		{
			$this->data_hidden[$name] = $value;
		}


		/** Add input
		 * @param array $attrs
		 * @returns System\Form\Input
		 */
		public function input(array $attrs)
		{
			if (in_array($attrs['type'], self::$inputs_button)) {
				$this->check_rendering_group('buttons');
			} else {
				$this->check_rendering_group('inputs');
			}

			$attrs['form'] = &$this;
			$attrs['value'] = $this->get_input_value($attrs);

			if ($attrs['type'] == 'checkbox') {
				$this->checkboxes[] = $attrs['name'];

				// Preset value to checkbox since checkboxes are not sending any value if not checked
				if (!isset($this->data_commited[$attrs['name']])) {
					$this->data_commited[$attrs['name']] = null;
				}
			}

			if (in_array($attrs['type'], array('checkbox', 'radio'))) {
				if ($this->submited) {
					$attrs['checked'] = !!$this->data_commited[$attrs['name']];
				} else {
					$attrs['checked'] = isset($this->data_default[$attrs['name']]) && $this->data_default[$attrs['name']];
				}
			}

			if ($attrs['type'] === 'image') {
				!isset($attrs['thumb_size']) && ($attrs['thumb_size'] = \System\Form\Input::IMAGE_INPUT_SIZE_DEFAULT);
				$attrs['tools'] = $this->get_image_input_tools($attrs);
				$attrs['value'] = $this->get_image_input_value($attrs);
			}

			if ($attrs['type'] === 'location') {
				$attrs['tools'] = $this->get_location_input_tools($attrs);
				$attrs['value'] = $this->get_location_input_value($attrs);
			}

			if ($attrs['type'] === 'gps') {
				$attrs['tools'] = $this->get_gps_input_tools($attrs);
				$attrs['value'] = $this->get_gps_input_value($attrs);
			}

			if (in_array($attrs['type'], self::$inputs_datetime)) {
				$attrs['value'] = $this->get_datetime_input_value($attrs);
			}

			return $this->rendering['group']->add_element(new \System\Form\Input($attrs));
		}


		/** Get additional inputs for image input
		 * @param array $attrs
		 * @returns array Set of input attrs
		*/
		private function get_image_input_tools(array $attrs)
		{
			$opts = \System\Form\Input::get_input_opts('image');
			$action = \System\Form\Input::ACTION_KEEP;

			if (!$attrs['value']) {
				unset($opts[\System\Form\Input::ACTION_KEEP]);
				$action = \System\Form\Input::ACTION_UPLOAD;
			}

			if (any($attrs['required'])) {
				unset($opts[\System\Form\Input::ACTION_NONE]);
			}

			if (empty($attrs['allow_url'])) unset($opts[\System\Form\Input::ACTION_URL]);
			if (any($attrs['disallow_upload'])) unset($opts[\System\Form\Input::ACTION_UPLOAD]);

			$input_action_attrs = array(
				"name"     => $attrs['name'].'_action',
				"type"     => 'radio',
				"label"    => l('form_image_input_action'),
				"options"  => $opts,
				"multiple" => true,
				"value"    => $action,
			);

			$input_file_attrs = array(
				"name"     => $attrs['name'].'_file',
				"type"     => 'file',
				"label"    => l('form_image_input_file'),
			);

			$input_url_attrs = array(
				"name"     => $attrs['name'].'_url',
				"type"     => 'url',
				"label"    => l('form_image_input_url'),
			);

			$input_action_attrs['value'] = $this->get_input_value($input_action_attrs);
			$input_file_attrs['value']   = $this->get_input_value($input_file_attrs);
			$input_url_attrs['value']    = $this->get_input_value($input_url_attrs);

			$input_action = new \System\Form\Input($input_action_attrs);
			$input_file   = new \System\Form\Input($input_file_attrs);
			$input_url    = new \System\Form\Input($input_url_attrs);

			$input_action->use_form($this);
			$input_file->use_form($this);
			$input_url->use_form($this);
			$inputs = array();

			if (!(count($opts) === 1 && any($opts[\System\Form\Input::ACTION_UPLOAD]))) {
				$inputs[] = $input_action;
			}

			if (empty($attrs['disallow_upload'])) {
				$inputs[] = $input_file;
			}

			if (any($attrs['allow_url'])) {
				$inputs[] = $input_url;
			}

			return $inputs;
		}


		private function get_location_input_tools(array $attrs)
		{
			$opts = \System\Form\Input::get_input_opts('location');
			$action = \System\Form\Input::ACTION_NEW;

			if ($attrs['value']) {
				unset($opts[\System\Form\Input::ACTION_NEW]);
				$action = \System\Form\Input::ACTION_EDIT;
			} else {
				unset($opts[\System\Form\Input::ACTION_EDIT]);
				$action = \System\Form\Input::ACTION_NEW;
			}

			if (any($attrs['required'])) {
				unset($opts[\System\Form\Input::ACTION_NONE]);
			}

			$input_action_attrs = array(
				"name"     => $attrs['name'].'_action',
				"type"     => 'radio',
				"label"    => l('form_location_input_action'),
				"options"  => $opts,
				"multiple" => true,
				"value"    => $action,
			);

			$input_name_attrs = array(
				"name"     => $attrs['name'].'_name',
				"type"     => 'text',
				"label"    => l('form_location_input_name'),
			);

			$input_addr_attrs = array(
				"name"     => $attrs['name'].'_addr',
				"type"     => 'text',
				"label"    => l('form_location_input_addr'),
			);

			$input_site_attrs = array(
				"name"     => $attrs['name'].'_site',
				"type"     => 'url',
				"label"    => l('form_location_input_site'),
			);

			$input_gps_attrs = array(
				"name"     => $attrs['name'].'_gps',
				"type"     => 'gps',
				"label"    => l('form_location_input_gps'),
			);

			$value = $this->get_input_value($attrs);

			if ($value instanceof \System\Location) {
				$input_name_attrs['value'] = $value->name;
				$input_addr_attrs['value'] = $value->addr;
				$input_site_attrs['value'] = $value->site;
				$input_gps_attrs['value']  = $value->gps;
			}

			$input_gps_attrs['tools'] = $this->get_gps_input_tools($input_gps_attrs);

			$input_action_attrs['value'] = $this->get_input_value($input_action_attrs);
			$input_name_attrs['value']   = $this->get_input_value($input_name_attrs);
			$input_addr_attrs['value']   = $this->get_input_value($input_addr_attrs);
			$input_site_attrs['value']   = $this->get_input_value($input_site_attrs);
			$input_gps_attrs['value']    = $this->get_gps_input_value($input_gps_attrs);


			$input_action = new \System\Form\Input($input_action_attrs);
			$input_name   = new \System\Form\Input($input_name_attrs);
			$input_addr   = new \System\Form\Input($input_addr_attrs);
			$input_site   = new \System\Form\Input($input_site_attrs);
			$input_gps    = new \System\Form\Input($input_gps_attrs);


			$input_action->use_form($this);
			$input_name->use_form($this);
			$input_addr->use_form($this);
			$input_site->use_form($this);
			$input_gps->use_form($this);
			$inputs = array();

			if (count($opts) !== 1) {
				$inputs[] = $input_action;
			}

			$inputs[] = $input_name;
			$inputs[] = $input_addr;
			$inputs[] = $input_site;
			$inputs[] = $input_gps;
			return $inputs;
		}


		private function get_gps_input_tools(array $attrs)
		{
			$input_lat_attrs = array(
				"name"     => $attrs['name'].'_lat',
				"type"     => 'text',
				"label"    => l('form_gps_input_lat'),
				"required" => !empty($attrs['required']),
			);

			$input_lng_attrs = array(
				"name"     => $attrs['name'].'_lng',
				"type"     => 'text',
				"label"    => l('form_gps_input_lng'),
				"required" => !empty($attrs['required']),
			);

			$value = $this->get_input_value($attrs);

			if ($value instanceof \System\Gps) {
				$input_lat_attrs['value'] = $value->latf();
				$input_lng_attrs['value'] = $value->lngf();
			}

			$input_lat_attrs['value'] = number_format($this->get_input_value($input_lat_attrs), 20);
			$input_lng_attrs['value'] = number_format($this->get_input_value($input_lng_attrs), 20);

			$input_lat = new \System\Form\Input($input_lat_attrs);
			$input_lng = new \System\Form\Input($input_lng_attrs);

			$input_lat->use_form($this);
			$input_lng->use_form($this);

			return array($input_lat, $input_lng);
		}


		private function get_location_input_value(array $attrs)
		{
			$value = $this->get_input_value($attrs);

			if ($this->submited) {
				$name_action = $attrs['name'].'_action';
				$name_name   = $attrs['name'].'_name';
				$name_addr   = $attrs['name'].'_addr';
				$name_gps    = $attrs['name'].'_gps';
				$name_site   = $attrs['name'].'_site';

				$action = $this->get_input_value_by_name($name_action);
				$name   = $this->get_input_value_by_name($name_name);
				$addr   = $this->get_input_value_by_name($name_addr);
				$gps    = $this->get_input_value_by_name($name_gps);
				$site   = $this->get_input_value_by_name($name_site);

				if (is_null($action)) {
					$action = $this->data_default[$name_action];
				}

				if ($action == \System\Form\Input::ACTION_NONE) {
					$value = null;
				}

				if ($action == \System\Form\Input::ACTION_NEW || \System\Form\Input::ACTION_EDIT) {
					$value = get_first('\System\Location')->where(array("name" => $name))->fetch();

					if (!$value) {
						$value = new \System\Location(array(
							"name" => $name,
							"addr" => $addr,
							"gps"  => $gps,
							"site" => $site,
						));
					}
				}

				unset($this->data_commited[$name_name], $this->data_commited[$name_addr], $this->data_commited[$name_gps], $this->data_commited[$name_site]);
				$this->data_commited[$attrs['name']] = $value;
			}

			return $value;
		}


		private function get_gps_input_value(array $attrs)
		{
			$value = $this->get_input_value($attrs);

			if ($this->submited) {
				$name_lat = $attrs['name'].'_lat';
				$name_lng = $attrs['name'].'_lng';

				$this->data_commited[$attrs['name']] = $value = \System\Gps::from_array(array(
					"lat" => $this->get_input_value_by_name($name_lat),
					"lng" => $this->get_input_value_by_name($name_lng),
				));

				unset($this->data_commited[$name_lat], $this->data_commited[$name_lng]);
			}

			return $value;
		}


		private function get_image_input_value(array $attrs)
		{
			$value = $this->get_input_value($attrs);

			if ($this->submited) {
				$name_action = $attrs['name'].'_action';
				$name_file   = $attrs['name'].'_file';
				$name_url    = $attrs['name'].'_url';

				$action = $this->get_input_value_by_name($name_action);
				$file   = $this->get_input_value_by_name($name_file);
				$url    = $this->get_input_value_by_name($name_url);

				if ($action == \System\Form\Input::ACTION_KEEP) {
					$value = $this->get_input_value_by_name($attrs['name'], true);
				}

				if ($action == \System\Form\Input::ACTION_UPLOAD || is_null($action)) {
					$value = $file;
					$value = \System\Image::from_path($value['tmp_name']);
					$value->tmp = true;
				}

				if ($action == \System\Form\Input::ACTION_URL || (is_null($value) && is_null($action))) {
					$f = \System\File::fetch($url);
					$value = \System\Image::from_path($f->tmp_name);
					$value->tmp = true;
				}

				if ($value->tmp) {
					if ($value->is_image()) {
						$value->cache();
					} else {
						$value = null;
						$this->report_error($name_file, l('form_input_image_is_not_image'));
					}
				}

				if ($action == \System\Form\Input::ACTION_NONE) {
					$value = \System\Image::from_scratch();
				}

				$this->data_commited[$attrs['name']] = $value;
			}

			return $value;
		}


		private function get_datetime_input_value(array $attrs)
		{
			$value = $this->get_input_value($attrs);

			if (!is_object($value) && $value) {
				$value = new \DateTime($value);
			}

			if ($this->submited) {
				$this->data_commited[$attrs['name']] = $value;
			}

			return $value;
		}


		/** Add label
		 * @param string $text
		 * @param input  $for
		 */
		public function label($text, \System\Form\Input &$for = null)
		{
			$this->check_rendering_group('inputs');
			$attrs['form'] = &$this;
			return $this->rendering['group']->add_element(new Form\Label(array("content" => $text, "input" => $for)));
		}


		public function text($label, $text)
		{
			$this->check_rendering_group('inputs');
			$attrs['form'] = &$this;
			return $this->rendering['group']->add_element(new Form\Text(array("name" => crc32($label), "label" => $label, "content" => $text)));
		}


		/** Add common submit button
		 * @param string $label
		 */
		public function submit($label = self::LABEL_SUBMIT_DEFAULT)
		{
			return $this->input(array(
				"name"    => 'button_submited',
				"value"   => true,
				"type"    => 'submit',
				"label"   => $label,
			));
		}


		/** Render form or add form to processing
		 * @param System\Module $obj    Module to render the form in
		 * @param array         $locals Extra local data
		 * @returns mixed
		 */
		public function out(\System\Module $obj = NULL, array $locals = array())
		{
			$this->group_end();
			//~ $this->tab_group_end();

			return $obj instanceof \System\Module ?
				$obj->template(self::get_default_template(), (array) $locals + array("f" => $this)):
				\System\Template::partial(self::get_default_template(), array("f" => $this));
		}


		public static function get_default_template()
		{
			return self::TEMPLATE_DEFAULT;
		}


		public function get_hidden_data()
		{
			return $this->data_hidden;
		}


		public function get_prefix()
		{
			!$this->prefix && !$this->no_prefix && $this->setup_prefix();
			return $this->prefix;
		}


		/** Set default form prefix
		 * @returns string
		 */
		protected function setup_prefix()
		{
			$this->prefix = $this->id.'_';
		}


		public function get_objects()
		{
			return $this->objects;
		}


		public function report_error($input_name, $msg)
		{
			if (!isset($this->errors[$input_name])) {
				$this->errors[$input_name] = array();
			}

			$this->errors[$input_name][] = $msg;
		}


		public function get_attr_data()
		{
			return parent::get_data();
		}


		public function get_data()
		{
			if ($this->submited) {
				$data = $this->data_commited;
			} else {
				$data = &$this->data_default;
			}

			return $data;
		}


		public function get_errors($name = '')
		{
			if ($name) {
				if (isset($this->errors[$name])) {
					$error_list = &$this->errors[$name];
				} else $error_list = array();
			} else {
				$error_list = &$this->errors;
			}

			return $error_list;
		}


		public static function create_delete_checker(array $data)
		{
			$f = new self($data);

			foreach ($data['info'] as $i=>$text) {
				$f->text($i, $text);
			}

			$f->submit(isset($data['submit']) ? $data['submit']:l('delete'));
			return $f;
		}
	}
}
