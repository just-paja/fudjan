<?

namespace System\Model
{
	class Nested extends Basic
	{
		const FIRST_CHILD  = 'firstChild';
		const LAST_CHILD   = 'lastChild';
		const NEXT_SIBLING = 'nextSibling';
		const PREV_SIBLING = 'prevSibling';
		private $reposition = false;
		private $parent_node = NULL;


		public static function get_tree($model, array $conds = array(), array $opts = array(), array $joins = array())
		{
			if (!in_array("Core\System\NestedModel", class_parents($model))) {
				throw new \NestedModelException(sprintf(_('Model `%s` nedědí vlastnosti třídy NestedModel'), $model));
			}

			$helper = new Query(array(
				"opts"  => array("order-by" => 'node.left'),
				"model" => $model
			));

			$helper->add_tables(array("parent" => $model::$table, "node"   => $model::$table));
			$helper->where($conds);
			$helper->add_cols(array_merge($model::$attrs, (array) $model::$id_col), "node");
			$helper->add_cols(array("id_parent" => 'id_parent', "left" => 'left', "right" => 'right', "depth" => 'depth'), "node");
			$helper->where(array("`node`.`left` BETWEEN `parent`.`left` AND `parent`.`right`"));
			$helper->group_by("node.".$model::$id_col);
			$helper->add_cols(array(
				"depth-local" => "node.depth - parent.depth",
			), false);

			return $helper;
		}


		public function get_children(array $conds = array(), array $opts = array())
		{
			$conds["id_parent"] = $this->id;
			return get_all(get_class($this), $conds, $opts);
		}


		public function get_descendants(array $conds = array(), array $opts = array())
		{
			$model = get_class($this);
			return self::get_tree($model, array('node.left > '.$this->left, 'node.right < '.$this->right));
		}


		public function get_parent()
		{
			if (is_null($this->parent_node)) {
				$this->parent_node = get_first($model = get_class($this), array($model::$id_col => $this->id_parent))->fetch();
			}
			return $this->parent_node;
		}


		public function save($pos = self::LAST_CHILD, $id_obj = NULL)
		{
			$model = get_class($this);
			if (!$this->check_position($pos)) {
				throw new \NestedModelException('Invalid node position is supplied.');
			}

			if ($this->update_check()) {
				if ($this->id) {
					if ($this->reposition) {
						if (($id = intval($id_obj)) || $id = intval($this->id_parent)) {
							switch ($pos) {
								case self::FIRST_CHILD:
									$helper = "SELECT `left`, `right` FROM `".$model::$table."` WHERE `".$model::$id_col."` = ".$id;
									$conds  = "current.left BETWEEN node.left+1 AND node.right AND current.left - node.left = 1";
									$opts   = "ORDER BY node.left DESC";
									break;

								case self::LAST_CHILD :
									$helper = "SELECT `left`, `right` FROM `".$model::$table."` WHERE `".$model::$id_col."` = ".$id;
									$conds  = "current.left BETWEEN node.left+1 AND node.right AND node.right - current.right = 1";
									$opts   = "ORDER BY node.left DESC";
									break;

								case self::NEXT_SIBLING :
									$helper = "SELECT `left` FROM `".$model::$table."` WHERE `".$model::$id_col."` = ".$id;
									$conds  = "current.left - node.right = 1";
									break;

								case self::PREV_SIBLING :
									$sql = "SELECT node.".$model::$id_col."
									FROM $name node, () AS current
									WHERE ";
									$helper = "SELECT right FROM `".$model::$table."` WHERE `".$model::$id_col."` = ".$id;
									$conds  = "node.left - current.right = 1";
									break;
							}

							$res = \Dibi::fetch("
								SELECT `".$model::$id_col."` id
									FROM `".$model::$table."` node, (".$helper.") AS current
									WHERE ".$conds." ".$opts."
							");

							if ($res['id'] != $id) {
								self::reduce_node_width($model, $id);
								$this->update_attrs($this->set_lr($pos, $id_obj));
							}
						} else {
							// there is no objective node - save outside of the tree
						}
						$this->reposition = false;
					}
				} else {
					$this->update_attrs($this->set_lr($pos, $id_obj));
				}

				if (!get_first($model)) {
					$this->update_attrs(array("left" => 1, "right" => 2));
				}

				$r = parent::save();
				self::rebuild_tree($model);
				return $r;
			} else {
				$this->errors[] = 'missing-required-attrs';
			}

			return $this;
		}


		private function set_lr($pos, $id_obj = NULL)
		{
			$model = get_class($this);
			if ((in_array($pos, array(self::FIRST_CHILD, self::LAST_CHILD)) && ($obj = $this->get_parent())) || ($id_obj && ($obj = find($model, $id_obj)))) {
				// there is an object to put $this under
				switch ($pos) {
					case self::FIRST_CHILD:
						$sql1 = "right = right + 2 WHERE right > ".intval($obj->left);
						$sql2 = "left  = left  + 2 WHERE left  > ".intval($obj->left);
						$left  = $obj->left  + 1;
						$right = $obj->left + 2;
						break;

					case self::LAST_CHILD:
						$sql1 = "`right` = `right` + 2 WHERE `right` >= ".intval($obj->right);
						$sql2 = "`left`  = `left`  + 2 WHERE `left`  >  ".intval($obj->right);
						$left  = $obj->right;
						$right = $obj->right + 1;
						break;

					case self::NEXT_SIBLING:
						$sql1 = "right = right + 2 WHERE right > ".intval($obj->right);
						$sql2 = "left  = left  + 2 WHERE left  > ".intval($obj->right);
						$left  = $obj->right  + 1;
						$right = $obj->right + 2;
						break;

					case self::PREV_SIBLING:
						$sql1 = "right = right + 2 WHERE right >  ".intval($obj->left);
						$sql2 = "left  = left  + 2 WHERE left  >= ".intval($obj->left);
						$left  = $obj->left;
						$right = $obj->left + 1;
						break;
				}

				\Dibi::query("UPDATE `".$model::$table."` SET ".$sql1);
				\Dibi::query("UPDATE `".$model::$table."` SET ".$sql2);

			} else {
				// there is no objective node - save outside of the tree
				$this->id_parent = NULL;
				$res = \Dibi::fetch("SELECT MAX(`right`) r FROM `".$model::$table."`")->toArray();
				$left = any($res) ? ($res['r'] + 1):1;
				$right = $left + 1;
			}

			return array("left" => $left, "right" => $right);
		}


		private static function check_position($pos)
		{
			$r = new \ReflectionClass('\Core\System\NestedModel');
			return in_array($pos, $r->getConstants());
		}


		public function update_attrs(array $update)
		{
			if (isset($update['left']) || isset($update['right']) || isset($update['id_parent'])) {

				def($update['left']);
				def($update['right']);

				$this->reposition = $update['left'] != $this->left || $update['right'] != $this->right || $update['id_parent'] != $this->id_parent;
			}
			return parent::update_attrs($update);
		}


		public function __set($attr, $value)
		{
			if (in_array($attr, array("left", "right", "id_parent"))) {
				$this->reposition = $this->$attr != $value;
				$this->after_save[] = array(array(&$this, "rebuild_subtree"), array(get_class($this), $this->id_parent, $this->get_parent()->left));
			}
			return parent::__set($attr, $value);
		}


		static private function reduce_node_width($model, $id)
		{
			$res = \Dibi::fetch("SELECT `left`, `right`, (`right` - `left` + 1) width FROM ".$model::$table." WHERE ".$model::$id_col." = ".intval($id));

			if (any($res)) {

				// Update children
				if ((int) $width > 2) {
					\Dibi::query("UPDATE ".$model::$table." SET `right` = `right` - 1, `left` = `left` - 1 WHERE `left` BETWEEN ".$res['left']." AND ".$res['right']);
				}

				// Update parent nodes and nodes on next levels.
				\Dibi::query("UPDATE ".$model::$table." SET `left` = `left` - 2 WHERE `left` > ".$res['left']." AND `right` > ".$res['right']);
				\Dibi::query("UPDATE ".$model::$table." SET `right` = `right` - 2 WHERE `right` > ".$res['right']);
			}
		}


		// Really slow. TODO: Come up with SQL only version
		static public function rebuild_tree($model)
		{
			$helper = new Query(array(
				"table" => $model::$table,
				"cols"  => array("id" => $model::$id_col, "lft" => 'left'),
				"conds" => array("id_parent" => 0),
			));
			$res = $helper->select();
			while ($row = $res->fetch()) {
				$row = $row->toArray();
				self::rebuild_subtree($model, $row['id'], $row['lft'], 0);
			}
		}


		static protected function rebuild_subtree($model, $id_parent, $left, $depth = 0)
		{
			$right = $left+1;
			$helper = new Query(array(
				"table" => $model::$table,
				"cols"  => array("id" => $model::$id_col),
				"conds" => array("id_parent" => $id_parent),
			));
			$res = $helper->select();
			while ($row = $res->fetch()) {
				$row = $row->toArray();
				$right = self::rebuild_subtree($model, $row['id'], $right, $depth+1);
			}

			\Dibi::query("
				UPDATE `".$model::$table."`
					SET
						`left`  = ".intval($left).",
						`right` = ".intval($right).",
						`depth` = ".intval($depth)."
					WHERE `".$model::$id_col."` = ".$id_parent."
			");

			return $right+1;
		}

	}
}
