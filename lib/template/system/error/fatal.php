<!DOCTYPE html>
<html lang="<?php echo $loc->get_lang()?>">
	<head>
		<link rel="stylesheet" type="text/css" href="<?php echo $ren->link_resource('style', 'error-report')?>">

		<?
			$ren->content_for('title', 'Fudjan error');
			$ren->content_from('head');
		?>
	</head>

	<body class="error">
		<section id="container">
			<?

			$desc = $exc;
			require ROOT."/lib/template/partial/system/error/bug.php";

			?>
		</section>

		<footer><?php echo \System\Status::introduce();?></footer>
	</body>
</html>
