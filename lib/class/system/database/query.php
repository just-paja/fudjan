<?

namespace System\Database
{
	class Query
	{
		const NO_TABLE = "]-nt-[";
		private $tables = array();
		private $cols = array();
		private $joins = array();
		private $join_tables = array();
		private $opts = array();
		private $conds = array();
		private $limits = array();
		private $insert_data = array();
		private $parsed;
		private static $queries = 0;
		private $assoc_with_model;
		private $return_first = false;


		public function __construct(array $opts = array())
		{
			def($opts['opts'], array());

			if (any($opts['table']))  $this->add_tables($opts['table'], (empty($opts['table_alias']) ? null:$opts['table_alias']));
			if (any($opts['cols']))   $this->add_cols($opts['cols']);
			if (any($opts['join']))   $this->add_joins($opts['join']);
			if (any($opts['conds']))  $this->where($opts['conds']);
			if (any($opts['opts']))   $this->add_opts($opts['opts']);
			if (any($opts['model']))  $this->assoc_with_model = $opts['model'];
			if (any($opts['opts']['first']))  $this->return_first = !!$opts['opts']['first'];
		}


		public function assoc_with($model)
		{
			$this->assoc_with_model = $model;
			return $this;
		}


		private function add_objects($objects, &$where, $alias = null)
		{
			if (is_array($objects)) {
				foreach ($objects as $alias=>$object) {
					if (is_numeric($alias)) $where[] = $object; else $where[$alias] = $object;
				}
			} else {
				$placeholder = &$where[(is_numeric($alias) ? null:$alias)];
				is_array($placeholder) ? ($placeholder = array_merge($placeholder, $objects)):($placeholder = $objects);
			}
			return count($objects);
		}


		public function add_tables($objects, $alias = null)
		{
			if (is_array($objects)) {
				foreach ($objects as $alias => $table) {
					$this->add_objects($objects, $this->tables, $alias);
				}
			} else {
				if (!$alias) {
					$alias = 't'.count($this->tables);
				}
				$this->add_objects($objects, $this->tables, $alias);
			}
		}


		public function add_cols($objects, $table = null)
		{
			if (is_null($table)) {
				$table = first_key($this->tables);
			} elseif ($table === false) {
				$table = self::NO_TABLE;
			}

			if (is_array($objects) && is_array(reset($objects))) {
				foreach($objects as $obj){ $this->add_cols($obj, $table); }
			} else $this->add_objects($objects, $this->cols[$table]);

			return $this;
		}


		public function reset_cols()
		{
			$this->cols = array();
			return $this;
		}


		public function add_joins(array $objects)
		{
			$this->add_objects($objects, $this->joins);
		}


		private function resolve_table_alias($table_alias)
		{
			$ta = '';

			if ($table_alias) {
				$ta = "`".$table_alias."`.";
			} elseif ($table_alias !== false) {
				$k = array_keys($this->tables);
				if (any($k)) {
					$table_alias = reset($k);
					$ta = "`".$table_alias."`.";
				}
			}

			return $ta;
		}


		public function where(array $conds, $table_alias = null, $or = false)
		{
			if (any($conds)) {
				if ($or) {
					$temp = array();
				} else {
					$temp = &$this->conds;
				}

				$ta = $this->resolve_table_alias($table_alias);

				if (!empty($conds)) {
					foreach ($conds as $col=>$condition) {
						if (is_array($condition)) {
							$this->where($condition, $table_alias, !$or);
							continue;
						} if (is_object($condition)) {
							throw new \System\Error\Argument("Query condition cannot be an object!");
						} elseif (is_numeric($col) && !is_array($condition)) {
							if (strval($condition)) {
								$temp[] = "$condition";
							}
						} else {
							$temp[] = $ta."`$col` = '$condition'";
						}
					}
				}

				if ($or) $this->conds[] = "(".join(" OR ", $temp).")";
			}

			return $this;
		}


		/** Filter models with certain has_many or has_one relation
		 * @param int[] $relations List of relations and IDs
		 * @return $this
		 */
		public function has(array $relations)
		{
			if (any($this->assoc_with_model)) {
				foreach ($relations as $rel=>$ids) {
					$rel_attrs = \System\Model\Database::get_attr($this->assoc_with_model, $rel);
					$rel_key   = \System\Model\Database::get_id_col($rel_attrs['model']);
					$rel_conds = '';
					$rel_table = any($rel_attrs['is_bilinear']) ?
						\System\Model\Database::get_bilinear_table_name($this->assoc_with_model, $rel_attrs):
						\System\Model\Database::get_table($this->assoc_with_model);

					if (any($rel_attrs['is_bilinear'])) {
						$rel_conds = 'USING('.\System\Model\Database::get_id_col($this->assoc_with_model).')';
					}

					$this
						->join($rel_table, $rel_conds, 't_has_'.$rel)
						->where(array(
							'`t_has_'.$rel.'`.`'.$rel_key.'`'."IN(".implode(',', array_map('intval', $ids)).")"
						));
				}
			} else throw new \System\Error\Database("Query must be associated with model when using query::has()!");

			return $this;
		}


		public function where_in($col, array $conds, $table_alias = null, $or = false)
		{
			if (any($conds)) {
				$ta = $this->resolve_table_alias($table_alias);
				return $this->where(array($ta."`$col` IN (".implode(',', array_map('intval', $conds)).")"), $ta, $or);
			}

			return $this;
		}


		public function sort_by($cond)
		{
			$this->opts['order-by'] = $cond;
			return $this;
		}


		public function add_insert_data(array $data)
		{
			$this->insert_data[] = $data;
		}


		public function add_opts(array $opts)
		{
			$this->opts = array_merge($this->opts, $opts);
			return $this;
		}


		public function group_by($str)
		{
			return $this->add_opts(array("group-by" => $str));
		}


		public function distinct()
		{
			return $this->add_opts(array("distinct" => true));
		}


		public function join($table, $conds_str, $alias = null, $type = null)
		{
			$this->join_tables[$alias] = $table;
			$this->joins[] = ($type ? $type.' ':null)."JOIN `".$table."`".($alias ? ' '.$alias:null)." ".$conds_str;
			return $this;
		}


		public function left_join($table, $conds, $alias = null)
		{
			return $this->join($table, $conds, $alias, 'left');
		}


		private function prepare($for = 'select')
		{
			$a = false;
			if (!$this->parsed) {
				if ($for != 'delete') {
					$a = true;
				}

				foreach ($this->tables as $t_alias => $table) {
					$this->parsed['tables'][] = "\n`".$table."`".($a && $t_alias && !is_numeric($t_alias) ? " `".$t_alias."`":null);

					!isset($this->cols[$t_alias]) && $this->cols[$t_alias] = array();
					foreach ($this->cols[$t_alias] as $alias => $col) {
						$fn = strpos($col, '(') !== false;
						$t = $fn ? "":"`".($a && $t_alias ? $t_alias:$table)."`.";
						$this->parsed['cols'][] = $t.$col.($alias && !is_numeric($alias) ? " `".$alias."`":null);
					}
				}

				foreach ($this->join_tables as $t_alias => $table) {
					foreach (array_merge(isset($this->cols[$table]) ? (array) $this->cols[$table]:array(), isset($this->cols[$t_alias]) ? (array) $this->cols[$t_alias]:array()) as $alias => $col) {
						$this->parsed['cols'][] = "`".($a && $t_alias ? $t_alias:$table)."`.".$col.($alias && !is_numeric($alias) ? " `".$alias."`":null);
					}
				}

				if (!empty($this->cols[self::NO_TABLE])) {
					foreach ($this->cols[self::NO_TABLE] as $name => $value) {
						$this->parsed['cols'][] = "(".$value.") `".$name."`";
					}
				}
			}
		}


		public function count()
		{
			$this->prepare();

			$sql = "SELECT COUNT(*) ".
				"FROM ".implode(',', (array) $this->parsed['tables']).(!empty($this->joins) ? " ".implode(" ", $this->joins):NULL).
				(!empty($this->conds) ? " WHERE ".implode(' AND ', $this->conds):null);

			self::$queries ++;
			return \System\Database::count($sql);
		}


		public static function first_val(array $result)
		{
			return reset($result);
		}


		public function select($get_query = false)
		{
			if (any($this->opts['falsify-return-value'])) return $this->false_return_value;

			$this->prepare();
			$sql = "SELECT ".(any($this->opts['distinct']) ? " DISTINCT ":'').implode(',', $this->parsed['cols']).
				"\n FROM ".implode(',', (array) $this->parsed['tables']).(!empty($this->joins) ? " ".implode(" ", $this->joins):NULL).
				(!empty($this->conds) ? "\n WHERE ".implode(' AND ', $this->conds):null);

			if (!empty($this->opts['group-by'])) {
				$sql .= "\n GROUP BY ".$this->opts['group-by'];
			}

			if (!empty($this->opts['order-by'])) {
				$sql .= "\n ORDER BY ".$this->opts['order-by'];
			}

			if (isset($this->opts['per-page'])) {
				def($this->opts['offset'], 0);
				$sql .= "\n LIMIT ".intval($this->opts['offset']).",".intval($this->opts['per-page']);
			}

			//dump($sql);
			self::$queries ++;
			return $get_query ? $sql:\System\Database::query($sql);
		}


		public function insert($get_query = false)
		{
			$this->prepare();
			$sql = "INSERT INTO `".implode('`, `', $this->tables)."` ";
				if (any($this->parsed['cols'])) {
					foreach ($this->cols as $table) {
						$sql .= "(".implode(',', $table).")";
					}
				}

			$this->parsed['insert-data'] = array();

			foreach ($this->insert_data as $row) {
				$d = array();
				foreach ($this->cols as $table) {
					foreach ($table as $col) {
						\System\Database::escape($row[$col]);
						$d[] = $row[$col];
					}
				}
				$this->parsed['insert-data'][] = "(".implode(',', $d).")";
			}

			if (any($this->parsed['insert-data'])) {
				$sql .= " VALUES ".implode(',', $this->parsed['insert-data']);

				//dump($sql);
				self::$queries ++;
				return $get_query ? $sql:\System\Database::query($sql);
			} else {
				return false;
			}
		}


		public function delete()
		{
			$this->prepare('delete');
			self::$queries ++;

			return \System\Database::query($sql =
				"DELETE FROM ".implode(',', (array) $this->parsed['tables']).
				"\nWHERE ".implode(' AND ', $this->conds)
			);
		}


		public static function simple_delete($from, array $conds)
		{
			$helper = new self(array("table" => $from));
			$helper = $helper->where($conds, $from);

			return $helper->delete();
		}


		public static function simple_count($table, array $cols = array(), array $conds = array())
		{
			if (any($this->opts['falsify-return-value'])) return $this->false_return_value;

			$helper = new self(
				array(
					"table" => $table,
					"conds" => $conds
				)
			);

			return $helper->count();
		}



		public static function count_all()
		{
			return self::$queries;
		}


		public function fetch($key = null, $value = null)
		{
			if (any($this->opts['falsify-return-value'])) return $this->false_return_value;

			$result = $this->select();
			$data = $this->assoc_with_model ?
				$result->fetch_model($this->assoc_with_model, $key):
				$result->fetch_assoc($key, $value);

			return $this->return_first ? (any($data) ? reset($data):null):$data;
		}


		public function fetch_one()
		{
			$this->return_first = true;
			return $this->fetch();
		}


		public function paginate($per_page = 20, $page_offset = 0)
		{
			$this->add_opts(array("per-page" => intval($per_page), "offset" => intval($per_page)*intval($page_offset)));
			return $this;
		}


		public function ignore_query($retval = NULL)
		{
			$this->opts['falsify-return-value'] = true;
			$this->false_return_value = $retval;
			return $this;
		}


		public function cancel_ignore()
		{
			unset($this->opts['falsify-return-value']);
			return $this;
		}


		/* Set up quick conditions if associated with a model
		 * @return $this
		 */
		public function quick()
		{
			return isset($this->assoc_with_model) ?
				$this->where(\System\Model\Database::get_quick_conds($this->assoc_with_model)):
				$this;
		}


		public function get_filter_cond($filter, $table_alias)
		{
			$pass  = null;
			$type  = $filter['type'];
			$value = $filter[$type];

			if ($filter['attr'] == 'id' && $this->assoc_with_model) {
				$filter['attr'] = \System\Model\Database::get_id_col($this->assoc_with_model);
			}

			switch ($type) {
				case 'in':
				case 'exact':
					if (is_array($value)) {
						$value = implode(',', array_map('intval', $value));
						$pass = "`" . $filter['attr'] . "` IN (".$value.")";
					} else {
						$pass = "`" . $filter['attr'] . "` = '".$value."'";
					}
					break;

				case 'iexact':
					$pass = "LOWER(`" . $filter['attr'] . "`) = LOWER('".$value."')";
					break;

				case 'contains':
					$pass = "`" . $filter['attr'] . "` LIKE '%".$value."%'";
					break;

				case 'icontains':
					$pass = "LOWER(`" . $filter['attr'] . "`) LIKE LOWER('%".$value."%')";
					break;

				case 'starts_with':
					$pass = "`" . $filter['attr'] . "` LIKE '".$value."%'";
					break;

				case 'istarts_with':
					$pass = "LOWER(`" . $filter['attr'] . "`) LIKE LOWER('".$value."%')";
					break;

				case 'ends_with':
					$pass = "`" . $filter['attr'] . "` LIKE '%".$value."'";
					break;

				case 'iends_with':
					$pass = "LOWER(`" . $filter['attr'] . "`) LIKE LOWER('%".$value."')";
					break;

				default:
					throw new \System\Error\Argument('Unknown filter', $type);

			}

			return $pass;
		}


		public function get_filter_batch_cond($filters, $table_alias = null, $or = false)
		{
			$pass = array();

			if (!is_array($filters)) {
				$filters = array($filters);
			}

			foreach ($filters as $row) {
				$pass[] = $this->get_filter_cond($filter, $table_alias);
			}

			return $pass;
		}


		public function add_filter($filter, $table_alias = null, $or = false)
		{
			$pass  = array();
			$valid = true;
			$or = false;

			if (isset($filter['type']) && isset($filter[$filter['type']])) {
				$type  = $filter['type'];
				$value = $filter[$type];

				if ($type == 'or') {
					$or = true;
					$pass = $this->add_filter_batch($value, $table_alias, true);
				} else if (isset($filter['attr'])) {
					$pass = array($this->get_filter_cond($filter, $table_alias));
				} else {
					$valid = false;
				}
			} else {
				$valid = false;
			}

			if (!$valid) {
				throw new \System\Error\Argument('Failed to parse filters', var_export($filter_val, true));
			}

			return $this->where($pass, $table_alias, $or);
		}
	}
}
