<?php

foreach ($slots as $slot) {
	$name = 'layout-'.$slot;
	$cname = 'layout-slot-'.$slot;

	?>
	<div class="$cname">
		<?php $ren->slot($name); ?>
	</div>
	<?
}
