<?

try {
	$generator = System\Output::introduce();
} catch(Exception $e) { $generator = 'pwf'; }

$heading = 'There was an error while processing your request';
$info    = 'We are doing everything in our power to fix that. If you do not share this opinion, please let us know.';

try {
	$heading = l($heading);
	$info = l($info);
} catch(Exception $e) {}

?>
<h1><?=$heading?></h1>
<p><?=$info?></p>
<ul>
	<?
		$errors = System\Status::format_errors($desc);
		foreach ($errors as $e) {
			?>
			<li class="error-desc"><?=$e?></li>
			<?
		}
	?>
</ul>
