<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<?=content_from('head');?>
	</head>

	<body>
		<div id="container">
			<? yield(); ?>
			<? slot(); ?>
		</div>
		<footer><?=System\Output::introduce()?></footer>
	</body>
</html>
<?
