<?

foreach ($slots as $slot) {
	$name = 'layout-'.$slot;
	$cname = 'layout-slot-'.$slot;

	echo div($cname);
		$ren->slot($name);
	close('div');
}
