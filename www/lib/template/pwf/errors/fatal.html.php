<?

Tag::doctype();
Tag::html();

	Tag::head();
		$response->content_for('styles', 'pwf/base');
		$response->content_for('styles', 'pwf/errors');
		$response->content_from('head');
	Tag::close('head');

	Tag::body(array("class" => 'error'));
		Tag::section(array("id" => 'container'));

			$desc = $exc;
			require ROOT."/lib/template/partial/system/error/bug.php";

		Tag::close('section');
	Tag::close('body');
Tag::close('html');
