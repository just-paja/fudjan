<!DOCTYPE html>
<html lang="<?=$loc->get_lang()?>">
	<head>
		<link rel="stylesheet" type="text/css" href="<?=$ren->link_resource('style', 'error-report')?>">

			<?
				$ren->content_for('title', 'Fudjan error');
				$ren->content_from('head');
			?>
	</head>

	<body class="error">
		<section id="container">
			<?
				$ren->render_yield();
				$ren->slot();
			?>
		</section>

		<footer><?=\System\Status::introduce();?></footer>
	</body>
</html>
