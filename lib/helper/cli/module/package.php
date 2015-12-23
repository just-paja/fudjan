<?php

namespace Helper\Cli\Module
{
	class Package extends \Helper\Cli\Module
	{
		protected static $info = array(
			'name' => 'package',
			'head' => array(
				'Package your application',
			),
		);

		protected static $ignored = array(
			'^\/\.git',
			'^\/composer\.lock',
			'^\/etc\/conf\.d\/dev',
			'^\/var'
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
			$files = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator(BASE_DIR),
				\RecursiveIteratorIterator::LEAVES_ONLY
			);

			$zip = new \ZipArchive();
			$target = BASE_DIR.'/var/artifact.zip';

      if (isset($params[0])) {
        $target = $params[0];
      }

			if ($zip->open($target, \ZipArchive::CREATE) !== true) {
				throw new \System\Error('Could not open artifact target', $target);
			}

			foreach ($files as $file) {
				if ($file->isDir()) {
					continue;
				}

				$filePath = $file->getRealPath();
				$relativePath = str_replace(BASE_DIR, '', $filePath);
				$ignore = false;

				foreach (self::$ignored as $pattern) {
					if (preg_match('/' . $pattern . '/', $relativePath)) {
						$ignore = true;
					}
				}

				if (!$ignore) {
					$zip->addFile($filePath, $relativePath);
				}
			}

			$zip->close();
		}

	}
}
