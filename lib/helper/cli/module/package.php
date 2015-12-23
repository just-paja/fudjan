<?php

namespace Helper\Cli\Module
{
	class ProjectDirectoryRecursiveIterator extends \RecursiveFilterIterator {

		public static $filters = array(
			'^\/\.git',
			'^\/composer.lock',
			'^\/etc\/conf\.d\/dev',
			'^\/var',
		);

		public function accept() {
			$accept = true;

			foreach (self::$filters as $pattern) {
				$path = $this->current()->getPathname();
				$relative = str_replace(BASE_DIR, '', $path);

				if (preg_match('/'.$pattern.'/', $relative)) {
					$accept = false;
					break;
				}
			}

			return $accept;
		}

	}

	class Package extends \Helper\Cli\Module
	{
		const DIR_PACKAGES = '/var/packages';

		protected static $info = array(
			'name' => 'package',
			'head' => array(
				'Package your application',
			),
		);

		protected static $attrs = array(
			"help"    => array("type" => 'bool', "value" => false, "short" => 'h', "desc"  => 'Show this help'),
			"verbose" => array("type" => 'bool', "value" => false, "short" => 'v', "desc" => 'Be verbose'),
		);


		protected static $commands = array(
			"artifact" => array(
				'single' => 'Create deployable artifact',
				'path' => 'Create deployable artifact on [path]'
			),
		);


		public function cmd_artifact(array $params = array())
		{
			\System\Directory::check(BASE_DIR.static::DIR_PACKAGES);

			$target = BASE_DIR.static::DIR_PACKAGES.'/artifact.tar';
			$result = $target.'.gz';

			if (isset($params[0])) {
				$target = $params[0];
			}

			if (file_exists($target)) {
				unlink($target);
			}

			if (file_exists($result)) {
				unlink($result);
			}

			$iter = new \RecursiveDirectoryIterator(BASE_DIR);
			$iter->setFlags(\FileSystemIterator::SKIP_DOTS);
			$iter = new ProjectDirectoryRecursiveIterator($iter);
			$iter = new \RecursiveIteratorIterator($iter);

			$archive = new \PharData($target);
			$archive->buildFromIterator($iter, BASE_DIR);

			$archive->compress(\Phar::GZ);
			unlink($target);
		}

	}
}
