<?

namespace System\Form
{
	abstract class Renderer
	{
		public static function render(\System\Template\Renderer $ren, \System\Form $form)
		{
			$data = json_encode($form->to_object());
			$ren->content_for('styles', 'bower/pwf-form/styles/form');
			$ren->content_for('styles', 'bower/pwf-form/styles/date');
			$ren->content_for('styles', 'bower/pwf-input-file/styles/file');
			$ren->content_for('styles', 'styles/pwf/form');

			$ren->content_for('scripts', 'bower/moment/moment');
			$ren->content_for('scripts', 'bower/pwf-moment-compat/lib/moment-compat');
			$ren->content_for('scripts', 'bower/pwf-queue/lib/queue');
			$ren->content_for('scripts', 'bower/pwf-comm/lib/comm');
			$ren->content_for('scripts', 'bower/pwf-comm/lib/mods/http');
			$ren->content_for('scripts', 'bower/pwf-comm-form/lib/comm-form');
			$ren->content_for('scripts', 'bower/pwf-locales/lib/locales');
			$ren->content_for('scripts', 'bower/pwf-form/lib/form');
			$ren->content_for('scripts', 'bower/pwf-form/lib/input');
			$ren->content_for('scripts', 'bower/pwf-form/lib/input/default');
			$ren->content_for('scripts', 'bower/pwf-form/lib/input/textarea');
			$ren->content_for('scripts', 'bower/pwf-form/lib/input/checkbox');
			$ren->content_for('scripts', 'bower/pwf-form/lib/input/select');
			$ren->content_for('scripts', 'bower/pwf-form/lib/input/date');
			$ren->content_for('scripts', 'bower/pwf-form/lib/input/month');
			$ren->content_for('scripts', 'bower/pwf-form/lib/input/time');
			$ren->content_for('scripts', 'bower/pwf-form/lib/input/datetime');
			$ren->content_for('scripts', 'bower/pwf-input-file/lib/file');
			$ren->content_for('scripts', 'bower/pwf-input-gps/lib/gps');
			$ren->content_for('scripts', 'bower/pwf-input-location/lib/location');

			return div(array('pwform'), '<span class="def" style="display:none">' . $data . '</span>');
		}
	}
}
