<?

namespace System
{
	class Form
	{
		static private $instances = array();
		static private $preservers = array();
		static private $default = array("id" => 'form', "method" => 'post', "action" => '', "enctype" => 'multipart/form-data');
		static private $attr_names = array("id", "method", "action", "enctype", "class", "heading", "antispam", "desc");

		// Validace, regexpy
		static private $validations = array(
			"email" =>
				"/^([a-z0-9!#$%*\/?|^{}`~&'+=_-]|[a-z0-9!#$%*\/?|^{}`~&'+=_-][.a-z0-9!#$%*\/?|^{}`~&'+=_-]*[a-z0-9!#$%*\/?|^{}`~&'+=_-]|\".+\")@[a-z0-9]+(.[a-z0-9]+)*$/i",
			"url" =>
				"/^[a-z]+[:\/\/]+[a-z0-9\-_]+\\.+[a-z0-9\.\/%&=\?\-_]+$/i"
		);

		static private $val_kinds = array(
			'input', 'textarea', 'select', 'button', 'list', 'image'
		);

		private $attrs = array();
		private $data = array();
		private $raw_data = array();
		private $objects = array();
		private $hidden = array();
		private $inputs = array();
		private $tabs = array();
		private $submited = false;
		private $prefix = null;
		private $errors = 0;

		private $plotting_inputs = false;
		private $plotting_group = false;
		private $plotting_tab_group = false;
		private $plotting_tab = false;
		private $current_tab = null;
		private $tabbers = array();

		// INIT
		function __construct(array $dataray = array())
		{
			def($dataray['default'], array());

			self::$instances = &$this;

			foreach(self::$attr_names as $attr)
				$this->attrs[$attr] = def($dataray[$attr], def(self::$default[$attr]));

			if(!$this->get('id')) $this->attrs['id'] = 'noname-form-'.self::$instances;
			if(!$this->get('method')) $this->attrs['method'] = 'post';

			$this->prefix = $this->get('id').'-';
			if(!$this->get('action')) $this->set('action', System\Page::get_path());

			if (any($dataray['hidden']) && is_array($dataray['hidden'])) {
				foreach($hidden as $k=>$v){
					$this->hidden($k, array("value" => $v));
				}
			}

			$input = System\Input::get_by_prefix($this->prefix);
			$this->submited = !!def($input['submited'], false);
			$this->raw_data = $this->is_submited() ? $input:(array)$dataray['default'];
			$this->compile_data();

			self::modify_data($this->data);
			self::modify_data($this->raw_data);

			$this->hidden('submited', true);

			// Predani dat k zachovani
			foreach (self::$preservers as &$form) {
				$form->independent_hidden($this->get_raw_data());
			}

			// Zachovani dat z ostatnich formularu
			if (!!def($dataray['preserve-other-forms'], false)) {
				self::$preservers[] = &$this;
				foreach (self::$instances as &$form) {
					if ($form != $this) {
						$this->idependent_hidden($form->get_raw_data());
					}
				}
			}
		}


		public function update($update)
		{
			$this->data = array_merge($this->data, $update);
		}


		public function is_submited()
		{
			return !!$this->submited;
		}


		public function is_processed()
		{
			return $this->submited = false;
		}


		public function is_completed()
		{
			return $this->is_submited() && !$this->errors;
		}


		public function error(&$obj, $msg)
		{
			$obj['errors'][] = $msg;
			return $this->errors++;
		}


		// Gettery, settery
		public function get($what = null)
		{
			return $what ? $this->attrs[$what]:$this->attrs;
		}


		public function &get_objects()
		{
			return $this->objects;
		}


		public function &get_hidden()
		{
			return $this->hidden;
		}


		public function &get_input($name)
		{
			return $this->objects[$name];
		}


		public function &get_tabs()
		{
			return $this->tabs;
		}


		public function get_prefix()
		{
			return $this->prefix;
		}


		public function get_switch_opts($k)
		{
			return $this->switches[$k];
		}


		private function set($attr, $value)
		{
			return $this->attrs[$attr] = $value;
		}



		public function get_raw_data($name = null)
		{
			$this->compile_data();
			return $name ? (isset($this->data[$name]) ? $this->data[$name]:null):$this->data;
		}


		public function get_data($name = null)
		{
			$this->decompile_data();
			return $name ? (isset($this->raw_data[$name]) ? $this->raw_data[$name]:null):$this->raw_data;
		}



		// Obecne pridavani objektu
		private function &object_add(array $object)
		{
			def($object['kind'], 'object');
			def($object['eclass'], '');

			// Pokud se nam do formulare dostanou objekty
			if (any($object['options']) && is_array($object['options'])) {
				foreach ($object['options'] as $key=>&$val) {
					if (is_object($val)) {
						$obj = $val;
						unset($object['options'][$key]);
						$object['options'][$obj->name] = $obj->id;
					}
				}
			}

			if (isset($object['disabled']) && !$object['disabled']) unset($object['disabled']);
			if (isset($object['disabled']) && !$object['required']) unset($object['required']);

			$name = def($object['name'], 'form-object-'.rand());
			$object['name'] = $this->prefix.$object['name'];

			if (in_array($object['kind'], array('list', 'input', 'textarea', 'select', 'button'))) {
				if ((!$this->is_submited() && empty($object['value'])) || $this->is_submited()) {
					$object['value'] = $this->get_object_value($object['name']);
				}

				if ($object['kind'] == 'button') {
					if ($object['name'] == $this->prefix) {
						unset($object['name'], $object['value']);
					}
				}

				if (isset($object['type'])) {

					if ($object['type'] == 'checkbox') {
						if (empty($object['options'])) {
							if (any($object['value'])) {
								$object['checked'] = true;
							} else {
								$this->set_object_value($object['name'], false);
							}

							$object['value'] = true;
						} else {
							$object['value'] = $this->get_object_value($object['name']);
						}
					}

					if (in_array($object['type'], array('number', 'range')) && $object['value']) {
						if(!isset($object['step']) || intval($object['step']) == floatval($object['step'])) $object['value'] = intval($object['value']);
						else $object['value'] = floatval($object['value']);
					}

				}


				$object['class'] =
					(isset($object['class']) ? $object['class'].' ':null)
					.$object['kind'].'-'
					.(isset($object['type']) ? $object['type']:'input')
					." form-input-".count($this->objects);

				if ($this->plotting_tab) {
					$keys = array_keys($this->tabs[count($this->tabs)-1]);
					$object['onfocus-show-tab'] = array(
						$this->current_tab_group,
						end($keys)
					);
				}
			}

			$this->validate_obj($object);

			def($object['id'], $object['kind'].'-'.count($this->objects));
			$object['id'] = str_replace(array('-', '[', ']'), array('_', '_', ''), $object['id']);

			$this->objects[$object['id']] = $object;
			if (isset($object['type']) && $object['type'] == 'file') $this->files[$object['id']] = &$this->objects[$object['id']];

			return $this->objects[$object['id']];
		}


		// HTML kontejnery
		public function inputs_start(){ $this->plotting_inputs = true; $this->object_add(array("kind" => 'inputs-start')); }
		public function inputs_end(){ $this->plotting_inputs = false; $this->object_add(array("kind" => 'inputs-end')); }
		public function check_inputs_start(){ if(!$this->plotting_inputs){ $this->inputs_start(); return true; } }
		public function check_inputs_end(){ if($this->plotting_inputs){ $this->inputs_end(); return true; } }
		public function check_tab_group_start(){ if(!$this->plotting_tab_group){ $this->tab_group_start(); return true; } }
		public function check_tab_group_end(){ if($this->plotting_tab_group){ $this->tab_group_end(); return true; } }
		public function check_tab_end(){ if($this->plotting_tab){ $this->tab_end(); return true; } }
		public function check_switch_end(){ if ($this->plotting_switch) { return $this->input_switch_end(); }  }
		public function check_group_end(){ if ($this->plotting_group) { return $this->group_end(); }  }


		public function &tab_group_start($object = array())
		{
			$this->check_inputs_end();
			$this->tabs[] = array();
			$this->plotting_tab_group = true;
			$object['kind'] = 'tabs-group-start';
			$object['id'] = $this->prefix.'tabber-id-'.count($this->tabs);
			$object['tabs'] = &$this->tabbers[$object['id']];
			$this->tabbers[$object['id']] = array();
			return $this->current_tab_group = &$this->object_add($object);
		}


		public function tab_group_end()
		{
			$this->plotting_tab_group = false;
			$this->check_tab_end();
			$this->object_add(array("kind" => 'tabs-group-end'));
		}


		public function &input_switch_start($name, $label)
		{
			$this->switches[] = array();
			$k = array_keys($this->switches);
			$this->plotting_switch = end($k);
			return $this->object_add(array("label" => $label, "class" => 'input_switch', "kind" => 'input-switch', "name" => $name, "id" => 'input-switch_'.$this->plotting_switch, "switch-id" => $this->plotting_switch));
		}


		// $obj is passed to group
		public function input_switch($label, array $obj = array())
		{
			if ($this->plotting_switch === NULL) {
				$this->input_switch_start($label, $label);
			} else {
				$this->check_group_end();
			}

			$this->switches[$this->plotting_switch][] = $label;
			$k = array_keys($this->switches[$this->plotting_switch]);
			$adc = end($k) === 0 ? ' form-group-switch-default':'';

			$this->group($label, array_merge($obj, array("class" => 'input_switch_'.$this->plotting_switch.$adc.' '.$obj['class'].' form-group-input_switch_'.$this->plotting_switch.'_'.end($k).' form-group-switch')));
			return true;
		}


		public function &input_switch_end()
		{
			$this->group_end();
			$this->plotting_switch = NULL;
			return $this->object_add(array("kind" => 'input-switch-end'));
		}


		public function &tab($label, $object = array())
		{
			def($object['class'], '');

			$this->check_tab_end();
			$this->check_tab_group_start();

			$this->plotting_tab = true;
			$last = &$this->tabs[count($this->tabs)-1];
			$last[] = array("label" => $label, "inputs" => array());
			$tab = $this->object_add(array("kind" => 'tab-start', "title" => $label, "id" => $this->prefix.'tab-'.count($last).'-'.count($this->tabs), "class" => 'tabbertab'.($object['class']? ' '.$object['class']:null)));
			$this->current_tab = &$tab;
			$this->check_inputs_start();

			$kk = array_keys($this->tabbers);
			$this->tabbers[end($kk)][] = $tab;
			return $tab;
		}


		public function tab_end()
		{
			$this->current_tab = null;
			$this->plotting_tab = false;
			$this->check_inputs_end();
			$this->object_add(array("kind" => 'tab-end'));
		}


		public function &group($label, array $obj = array())
		{
			$o = $this->object_add(array_merge(array("kind" => 'group-start'), $obj));
			$this->plotting_group = true;
			$this->label($label);
			return $o;
		}


		public function group_end()
		{
			$this->plotting_group = false;
			$this->object_add(array("kind" => 'group-end'));
		}


		// Vkladani inputu
		public function &input($name, $object)
		{
			if (!isset($name)) throw new \MissingArgumentException('Input must have name!');

			def($object['check'], false);
			def($object['type'], 'text');

			$object['type'] != 'hidden' && $this->check_inputs_start();
			$object['id'] = $object['name'] = $name;

			if ($object['type'] == 'list') {
				$object['instance'] = new ItemList(
					$this->get('id').'-'.$object['name'],
					is_array($this->get_data($object['name'])) ? $this->get_data($object['name']):array(),
					$object['label']
				);
			}

			if ($object['type'] == 'hidden') {
				$object['id'] = str_replace(array('-', '[', ']'), array('_', '_', ''), $object['id']);
				$this->hidden[$object['id']] = $object;
			} else {
				$object['kind'] = $object['type'] == 'list' ? 'list':'input';

				if($object['type'] == 'password' && $object['check']){
					$check = $object;
					$check['name'] = strtr($check['name'], array($this->prefix => null));

					if (strpos(strrev($check['name']), ']') === 0) {
						$check['name'] = substr($check['name'], 0, strlen($check['name'])-1). '_check]';
					} else {
						$check['name'] .= '_check';
					}

					$check['label'] .= ' ('._('kontrola').')';
					$object['checker'] = $check['name'];
					unset($check['check']);
				}
			}

			$this->inputs[$object['id']] = &$this->object_add((array) $object);

			if (isset($check)) {
				$this->input($check['name'], $check);
			}
			return $this->inputs[$object['id']];
		}


		public function &textarea($name, array $object = array())
		{
			if (!isset($name)) throw new \MissingArgumentException('Input must have name!');
			def($object['rows'], 10);

			$this->check_inputs_start();
			$object['id'] = $name;
			$object['name'] = $name;

			return $this->inputs[$object['id']] = &$this->object_add(array_merge(array("kind" => 'textarea'), (array) $object));
		}


		public function &select($name, array $object = array())
		{
			if (!isset($name)) throw new \MissingArgumentException('Input must have name!');
			$this->check_inputs_start();
			$object['id'] = $name;
			$object['name'] = $name;

			if (isset($object['entry']) && $object['entry']) {
				$object['options'][_('Hodnota v poli níže')] = '-?-';
			}

			if (empty($object['value']) || $this->is_submited()) {
				$object['value'] = $this->get_data($object['name']);
			}
			
			if (any($object['options'])) {

				$opts = array();
				foreach ($object['options'] as $key=>$obj) {
					if (is_object($obj)) {
						if ($obj instanceof \Core\System\BasicModel) {
							$opts[is_callable(array($obj, 'get_name')) ? $obj->get_name():$obj->name] = $obj->id;
						} else {
							throw new \Exception("Cannot handle object of class '".get_class($obj)."' in form select");
						}
					} else {
						$opts[$key] = $obj;
					}
				}
				$object['options'] = $opts;

			}

			if (empty($object['required'])) {
				$temp = $object['options'];
				$object['options'] = array_merge(array("" => ''), $temp);
			}

			return $this->inputs[$object['id']] = &$this->object_add(array_merge(array("kind" => 'select'), (array) $object));
		}


		public function &hidden($name, $value)
		{
			if (!isset($name)) throw new \MissingArgumentException('Input must have name!');
			return $this->input($this->prefix.$name, array("value" => $value, "type" => 'hidden'));
		}


		public function &button($object)
		{
			$this->check_inputs_end();
			def($object['type'], 'submit');
			def($object['label'], _('Odeslat'));
			return $this->object_add(array_merge(array("kind" => 'button'), (array) $object));
		}


		// Aliasy
		public function &input_datetime($name, $label, $required=false){ return $this->input($name, array("label" => $label, "type" => 'datetime-local', "required" => !!$required)); }
		public function &input_date($name, $label, $required=false){ return $this->input($name, array("label" => $label, "type" => 'date', "required" => !!$required)); }
		public function &input_text($name, $label, $required=false){ return $this->input($name, array("label" => $label, "type" => 'text', "required" => !!$required)); }
		public function &input_url($name, $label, $required=false){ return $this->input($name, array("label" => $label, "type" => 'url', "required" => !!$required)); }
		public function &input_passwd($name, $label, $required=false){ return $this->input($name, array("label" => $label, "type" => 'password', "required" => !!$required)); }
		public function &input_mail($name, $label, $required=false){ return $this->input($name, array("label" => $label, "type" => 'email', "required" => !!$required)); }
		public function &input_checkbox($name, $label, $required=false){ return $this->input($name, array("label" => $label, "type" => 'checkbox', "required" => !!$required)); }
		public function &input_int($name, $label, $required=false, $min=NULL, $max=NULL) { return $this->input($name, array("label" => $label, "type" => 'number', "required" => !!$required, "min" => $min, "max" => $max)); }
		public function &input_range($name, $label, $required=false, $step=NULL, $min=NULL, $max=NULL) { return $this->input($name, array("label" => $label, "type" => 'range', "required" => !!$required, "step" => $step, "min" => $min, "max" => $max)); }
		public function &submit($label){ return $this->button(array("label" => $label, "name" => 'submited', "value" => true)); }


		public function &input_image($name, array $object)
		{
			if (!isset($name)) throw new \MissingArgumentException('Input must have name!');

			def($object['th-size'], '');
			def($object['required'], false);
			def($object['allow-url'], false);
			def($object['eclass'], '');

			$obj = array("kind" => 'image');
			$val = $this->get_object_value($name);

			if ($object['th-size']) {
				list($object['th-width'], $object['th-width']) = explode('x', $object['th-size']);
			}

			$this->html('<li class="image-uploader'.($object['eclass'] ? ' '.$object['eclass']:null).'">');

			$fu = $object['required'] && !$val;
			$switch_opts = array();

			$val && $switch_opts[_('Ponechat stávající')] = 'actual';

			if (!$object['required']) {
				$switch_opts[_('Žádný obrázek')] = 'none';
			}

				if (!$this->is_submited()) {
					$this->set_object_value($name.'[src]', $val ? 'actual':'none');
				}

				if ($fu) {
					$obj[] = &$this->hidden($name.'[src]', 'upload');
				} else {
					$obj[] = &$this->html('<label class="label-left">'.$object['label'].':'.'</label>');
					$obj[] = &$this->html('<ul class="fup-cont">');
					if ($val instanceof Image) {
						$obj[] = &$this->html('<li class="thumb"><img src="'.$val->thumb(def($object['th-width'], 64), def($object['th-height'], 64)).'" /></li>');
					}
					$obj[] = &$this->input($name.'[src]', array("type" => 'radio', "label" => _('Akce'), "options" => &$switch_opts, "eclass" => 'controls'));
				}

				$switch_opts[_('Nahrát')] = 'upload';
				$obj[] = &$this->input($name.'[file]', array("type" => 'file', "label" => $fu ? $object['label']:_('Soubor z disku'), "required" => $fu));

				if ($object['allow-url']) {
					$switch_opts[_('Použít URL')] = 'url';
					$obj[] = &$this->input($name.'[url]', array("type" => 'url'));
				}

			$obj[] = &$this->html('</ul>');
			$obj[] = &$this->html('</li>');

			return $this->object_add($obj);
		}


		public function &input_list($name, array $object)
		{
			if (!isset($name)) throw new \MissingArgumentException('Input must have name!');

			def($object['options'], array());
			def($object['open'], false);
			def($object['show-keys'], true);
			def($object['type'], 'set');
			def($object['input-key'], array());
			def($object['input-value'], array());

			def($object['input-key']['label'], _('Klíč'));
			def($object['input-key']['type'], 'text');
			def($object['input-key']['eclass'], 'key');
			def($object['input-value']['label'], _('Hodnota'));
			def($object['input-value']['type'], 'text');
			def($object['input-value']['eclass'], 'value');

			$object['kind'] = 'list';
			$object['type'] = 'set';
			$object['name'] = $name;
			$store_name = 'store-'.$name;

			if ($this->is_submited()) {
				$this->set_object_value($name, $this->get_object_value($store_name));
			} elseif (!$this->get_object_value($name)) {
				$this->set_object_value($name, array());
			}

			$ret = &$this->object_add($object);
			$j = $this->get_object_value($name);

			if (is_array($j) || strpos($j, 'json:') !== 0) {
				$j = json_encode($j);
				$j == '[]'  && $j = '{}';
				$j = 'json:'.$j;
			}

			$this->set_object_value($store_name, $j);
			$h = $this->hidden($store_name, $this->get_object_value($store_name));

			$ret['bind-store'] = $h['id'];
			$object['target'] = $ret['id'];
			$this->input_list_editor($object);

			return $ret;
		}


		private function &input_list_editor(array $object)
		{
			$obj = array();
			$object['kind'] = 'list-editor';
			$obj['start'] = &$this->object_add($object);

			$this->inputs_start();
			$obj['input-key'] = &$this->input($object['target'].'_key', $object['input-key']);
			$obj['input-key'] = &$this->input($object['target'].'_value', $object['input-value']);

			$obj['reset'] = &$this->button(array("name" => $object['target'].'_reset', "type" => 'reset', "label" => _('Zrušit')));
			$obj['submit'] = &$this->button(array("name" => $object['target'].'_submit', "type" => 'submit', "label" => _('Uložit')));

			$end_attrs = $object;
			$end_attrs['kind'] = 'list-editor-end';
			$obj['end']   = &$this->object_add($end_attrs);
			$this->plotting_inputs = true;

			return $this->object_add($obj);
		}



		// Vkladani ostatnich objektu, prevazne html blbustky do sablony
		public function &footnote($text)
		{
			$this->footnote[] = $text;
		}


		public function &separator()
		{
			$between_inputs = $this->check_inputs_end();
			$obj = &$this->object_add(array("kind" => 'separator'));
			if($between_inputs) $this->inputs_start();
			return $obj;
		}


		public function &text($text, $label = null)
		{
			$this->check_inputs_start();
			return $this->object_add(array("kind" => 'text', "text" => $text, "label" => $label));
		}


		public function &tip($text)
		{
			return $this->object_add(array("kind" => 'tip', "text" => $text));
		}


		public function &note($text)
		{
			return $this->object_add(array("kind" => 'text', "text" => $text, "class" => 'note'));
		}


		public function &label($text)
		{
			$this->check_inputs_start();
			return $this->object_add(array("kind" => 'label', "text" => $text));
		}


		public function &html($html)
		{
			return $this->object_add(array("kind" => 'html', "html" => $html));
		}


		public function &clear()
		{
			return $this->object_add(array("kind" => 'clear'));
		}



		// Vystup do sablony
		public function out($obj = NULL, array $locals = array())
		{
			$this->check_group_end();
			$this->check_tab_group_end();
			$this->check_inputs_end();

			return $obj instanceof Module ?
				$obj->template(self::get_default_template(), (array) $locals + array("f" => $this)):
				System\Template::partial(self::get_default_template(), array("f" => $this));
		}


		public static function get_default_template()
		{
			return 'core/form/plain';
		}


		// Validace dat
		private static function validate_value($what, $str)
		{
			return preg_match(self::$validations[$what], $str);
		}


		private function validate_obj(&$obj)
		{
			if ($this->is_submited() && in_array($obj['kind'], self::$val_kinds)) {
				$val = $this->get_object_value($obj['name']);

				if (isset($obj['type'])) {
					if (($obj['type'] == 'number' || $obj['type'] == 'range') && (any($obj['required']) || $obj['value'])) {

							if (!is_numeric($obj['value']) && $val && $this->is_submited()) {
								$this->error($obj, _('Musí být číslo'));
							}

							if (isset($obj['min']) && intval($val) < $obj['min'] && $this->is_submited()) {
								$this->error($obj, _('Musí být větší než '). $obj['min']);
							}

							if (isset($obj['max']) && intval($val) > $obj['max'] && $this->is_submited()) {
								$this->error($obj, _('Musí být menší než '). $obj['max']);
							}

					} elseif (($obj['type'] == 'email' && (!!$obj['required'] || $val)) && !self::validate_value('email', $val)) {
							$this->error($obj, _('Prosím, zadejte platný email.'));
					} elseif ($obj['type'] == 'password' && $obj['check']) {
						$check = $this->get_object_value($obj['checker']);
						if ($val != $check) {
							$this->error($obj, _('Hesla nesouhlasí.'));
						}

					} elseif ($obj['type'] == 'file') {
						$value = $this->get_object_value($obj['name']);
						if ($obj['required'] && $value && !is_uploaded_file($value['tmp_name'])) {
							$this->error($obj, _('Soubor se nepodařilo nahrát.'));
						}
					}
				}

				if (any($obj['required']) && !strlen($val) && $this->is_submited()) {
					$this->error($obj, _('Položku je nutné vyplnit'));
				}

				if (any($obj['maxlength']) && strlen($val) > $obj['maxlength'] && $this->is_submited()) {
					$this->error($obj, sprintf(_("Položka &bdquo;%s&ldquo; je příliš dlouhá! Maximální délka je %d znaků."), $obj['label'], $obj['maxlength']));
				}
			}
		}


		// Prace s daty
		public function compile_data()
		{
			foreach ($this->raw_data as $key=>$value) {
				$this->data[$this->prefix.$key] = $value;
			}
		}


		public function decompile_data()
		{
			foreach($this->data as $key=>&$value) {
				strpos($key, $this->get_prefix()) === 0 && $k = substr($key, strlen($this->get_prefix()));
				$this->raw_data[$k] = $value;
			}

			unset($this->raw_data['submited']);
		}


		private static function modify_data(&$data)
		{
			foreach ($data as $key=>&$val) {
				if (is_array($val)) {
					self::modify_data($val);
				} else {
					if (!is_object($val) && strpos($val, 'json:') === 0) {
						$val = json_decode(substr($val, 5), true);
					}
				}
			}
		}


		public function exec($lambda, array $locals = array())
		{
			if($this->is_completed()){
				$locals += array("f" => &$this);
				($lambda instanceof \Closure) ? $lambda($locals):include(ROOT."lib/exec/".$lambda.'.php');
				if(!$locals['no-refresh']){
					redirect("/".System\Input::get('path'));
				}
			}
		}


		public function add_extmodel_attrs($obj,  $use_tabber = true, $new_tabber = true)
		{
			$groups = System\ExtModel::get_model_attr_groups($obj);

			foreach ($groups as $group) {

				if ($use_tabber) {
					if ($new_tabber) {
						$this->check_tab_group_end();
					}
					$this->tab($group->name);
				}

				foreach ($group->ext_attrs->fetch() as $attr) {
					switch ($attr->type) {

						case 'text':
							$this->textarea($attr->seoname, array("label" => $attr->name, "required" => $attr->required));
							break;

						case 'string':
							$this->input($attr->seoname, array("label" => $attr->name, "required" => $attr->required));
							break;

						case 'int':
							$this->input($attr->seoname, array("label" => $attr->name, "type" => 'number', "step" => 1, "required" => $attr->required));
							break;

						case 'float':
							$this->input($attr->seoname, array("label" => $attr->name, "type" => 'number', "required" => $attr->required));
							break;

						case 'image':
							$this->input_image($attr->seoname, array("label" => $attr->name, "required" => $attr->required));
							break;

						case 'tag-single':
							$tags = get_tree("\Core\Tag", array("id_parent" => $attr->id_tag_root, $attr->max_tag_depth ? "node.depth < ".$attr->max_tag_depth:null))->fetch();
							$this->input($attr->seoname, array("type" => 'radio', "label" => $attr->name, "required" => $attr->required, "options" => $tags));
							break;

						case 'tag-multi':
							$tags = get_tree("\Core\Tag", array("id_parent" => $attr->id_tag_root))->fetch();
							$this->input($attr->seoname, array("type" => 'checkbox', "label" => $attr->name, "required" => $attr->required, "options" => $tags));
							break;

					}
				}
			}

			if ($new_tabber && $use_tabber) {
				$this->check_tab_group_end();
			}
		}


		static function create_checker(array $info = array())
		{
			if (!$info['submit']) {
				$info['submit'] = _('Provést');
			}

			$f = new Form($info);
			foreach ($info['info'] as $label=>$value) {
				$f->text($value, $label);
			}
			$f->separator();
			$f->input_checkbox('check', _('Vím, co dělám'), true);
			$f->submit($info['submit']);
			return $f;
		}


		public function add_inputs(array $inputs)
		{
			foreach ($inputs as $name=>$obj) {
				switch ($obj['type']) {
					case 'textarea':
						$this->textarea($name, $obj);
						break;
					default:
						$this->input($name, $obj);
						break;
				}
			}
		}


		// parse object name and return value
		private function &get_object_data_ref($name)
		{
			$d = &$this->data;

			if (strpos($name, $this->get_prefix()) === false) {
				$name = $this->get_prefix().$name;
			}

			while ($p = strpos($name, '[')) {

				$var = substr($name, 0, strpos($name, '['));
				$tmp_name = substr($name, $p+1);

				if (strpos($tmp_name, '[')) {
					$t = explode(']', $tmp_name, 2);
					$name = implode('', $t);
				} else {
					$tmp_name = substr($tmp_name, 0, strpos($name, ']')-$p-1);
					$name = $tmp_name;
				}

				$d = &$d[$var];
			}

			if (is_array($d)) {
				return $d[$name];
			}

			if (is_object($d)) {
				return $d->$name;
			}

			return $d;
		}


		private function get_object_value($name)
		{
			return $this->get_object_data_ref($name);
		}


		private function set_object_value($name, $value)
		{
			$ref = &$this->get_object_data_ref($name);
			if (is_object($ref)) {
				$ref->$name = $value;
			} else {
				$ref = $value;
			}
		}
	}
}
