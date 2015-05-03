<?
#[Avatars]
#[Migrate avatar db structure to use new file API]

$users = \System\User::get_all()->fetch();

foreach ($users as $user) {
	if ($user->avatar) {
		$opts = $user->avatar->get_opts();

		if (empty($opts)) {
			$avatar = $user->avatar;

			if (strpos($user->avatar->path, '/') === 0) {
				$user->avatar->path = ROOT.$user->avatar->path;
			}
		} else {
			if (any($opts['file_path'])) {
				$avatar = \System\Image::from_path($opts['file_path']);

				if ($avatar) {
					$avatar->keep = true;
				}
			} else {
				$avatar = null;
			}
		}

		if (!is_null($avatar)) {
			try {
				$avatar->save();
			} catch (Exception $e) {
				$avatar = null;
			}
		}

		$user->avatar = $avatar;
		$user->save();

	}
}
