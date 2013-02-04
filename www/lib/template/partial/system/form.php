<?

$sub_li = $li = 0;

Tag::div(array(
	"class"  => 'yaform-container',
	"id"     => $f->id.'-container',
	"output" => true,
));

	Tag::a(array("name" => $f->anchor, "id" => $f->anchor, "close" => true));

	$f->heading && print(section_heading($f->heading));
	$f->desc && Tag::p(array("content" => $f->desc));

	Tag::form($f->get_attr_data());

		Tag::fieldset(array(
			"class" => 'hidden',
			"content" => Tag::input(array(
				"value"  => htmlspecialchars(json_encode($f->get_hidden_data())),
				"type"   => 'hidden',
				"name"   => $f->get_prefix().'hidden_data',
				"close"  => true,
				"output" => false,
			)),
		));

		$objects = $f->get_objects();

		foreach ($objects as $obj) {
			System\Form\Helper::render_element($obj);
		}

	Tag::close('form');
Tag::close('div');

if(!empty($f->form_js)){
	?>
	<script type="text/javascript">
		//<![CDATA[
			$(function() {
				<?
				if(is_array($f->form_js)){
					foreach($f->form_js as $line){
						echo $line."\n";
					}
				}
				?>
			});
		//]]>
	</script>
<?
}
