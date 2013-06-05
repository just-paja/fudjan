<?

/** Database structure handling
 * @package database
 */
namespace Helper\Database
{
	/** Container class that is responsible for handling database structure
	 */
	abstract class Structure
	{
		public static function get_default_config()
		{
			$dbs = cfg('database', 'list');
			foreach ($dbs as $ident=>$dbcfg) {
				if (any($dbcfg['is_yawf_home'])) {
					$dbcfg['ident'] = $ident;
					return $dbcfg;
				}
			}
		}


		public static function get_default_ident()
		{
			$dbcfg = self::get_default_config();
			return $dbcfg['ident'];
		}


		public static function get_driver_name($db_ident = null)
		{
			if (is_null($db_ident)) {
				$db_ident = self::get_default_ident();
			}

			if (\System\Database::exists($db_ident)) {
				return cfg('database', 'list', $db_ident, 'driver');
			} else throw new \System\Error\Database(sprintf('Database %s does not exist', $db_ident));
		}


		public static function table_exists($name, $db_ident = null)
		{
			$drv = '\\Helper\\Database\\'.ucfirst(self::get_driver_name($db_ident)).'\\Table';
			return $drv::exists($name, $db_ident);
		}


		public static function get_database($db_ident = null)
		{
			if (is_null($db_ident)) {
				$db_ident = self::get_default_ident();
			}

			$driver = '\\Helper\\Database\\'.ucfirst(self::get_driver_name($db_ident)).'\\Database';
			return new $driver($db_ident);
		}


		public static function sync_model($model)
		{
			self::sync_model_table($model);
			self::sync_model_table_indexes($model);
			self::sync_model_relations($model);
		}


		public static function sync_model_table($model)
		{
			$db = self::get_database();
			$table = $db->get_table($model::get_table($model));
			$attrs = \Helper\Database\Attr::get_from_model($model);
			$relations = \Helper\Database\Relation::get_from_model($model);

			foreach ($attrs as $attr) {
				if (!$table->has_column($attr->name)) {
					$table->add_attr($attr);
				}

				$table->get_column($attr->name)->set_cfg($attr->get_data());
			}

			$table->save();
		}


		public static function sync_model_table_indexes($model)
		{
			$db = self::get_database();
			$table = $db->get_table($model::get_table($model));
			$attrs = \Helper\Database\Attr::get_from_model($model);
			$indexes = $table->get_indexes();

			foreach ($attrs as $attr) {
				if ($attr->is_index && !in_array($attr->name, $indexes)) {
					$table->add_index($attr->name);
				} elseif (!$attr->is_key && in_array($attr->name, $indexes)) {
					$table->drop_index($attr->name);
				}
			}

		}


		public static function sync_model_relations($model)
		{
			$attrs = \Helper\Database\Attr::get_from_model($model);
			$relations = \Helper\Database\Relation::get_from_model($model);

			if (any($relations)) {
				foreach ($relations as $rel) {
					if ($rel->type == \System\Model\Database::REL_HAS_MANY && $rel->is_bilinear) {

						self::sync_bilinear_relation_table($rel);
					}
				}
			}
		}


		private static function sync_bilinear_relation_table(\Helper\Database\Relation $rel)
		{
			$name = $rel->get_bilinear_table_name();
			$db = self::get_database();
			$table = $db->get_table($name);
			$name_a = \System\Model\Database::get_id_col($rel->is_master ? $rel->parent:$rel->model);
			$name_b = \System\Model\Database::get_id_col($rel->is_master ? $rel->model:$rel->parent);

			$attrs = array(
				\Helper\Database\Attr::from_def('id_'.$name, array("type" => 'int', "is_unsigned" => true, "is_autoincrement" => true, "is_primary" => true)),
				\Helper\Database\Attr::from_def($name_a, array("type" => 'int', "is_unsigned" => true)),
				\Helper\Database\Attr::from_def($name_b, array("type" => 'int', "is_unsigned" => true)),
				\Helper\Database\Attr::from_def("created_at", array("type" => 'datetime', "default" => 0)),
				\Helper\Database\Attr::from_def("updated_at", array("type" => 'datetime', "default" => 0)),
			);

			foreach ($attrs as $attr) {
				if (!$table->has_column($attr->name)) {
					$table->add_attr($attr);
				}

				$table->get_column($attr->name)->set_cfg($attr->get_data());
			}

			return $table->save();
		}


	}
}
