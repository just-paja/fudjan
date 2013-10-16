<?

$users = get_all('\System\User')->fetch();

foreach ($users as $user) {
	if ($user->avatar) {
		$opts = $user->avatar->get_opts();

		if (empty($opts)) {
			if (strpos($user->avatar->path, '/') === 0) {
				$user->avatar->path = ROOT.$user->avatar->path;
				$user->save();
			}
		} else {
			$avatar = \System\Image::from_path(ROOT.$opts['file_path']);
			$user->avatar = $avatar;
			$user->save();
		}
	}
}
