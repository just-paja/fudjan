<?

foreach ($slots as $slot) {
	$name = 'layout-'.$slot;
	$cname = 'layout-slot-'.$slot;

	?>
	<div class="$cname">
		<? $ren->slot($name); ?>
	</div>
	<?
}
