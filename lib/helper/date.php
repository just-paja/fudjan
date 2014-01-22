<?

namespace Helper
{
	abstract class Date
	{
		public static function imprecise(\System\Template\Renderer $ren, \DateTime $date)
		{
			$now = time();
			$delta = $now - $date->getTimestamp();
			$str = '';

			if ($delta >= 0) {
				if ($delta < 3) {
					$str = $ren->trans('time_moment_ago');
				} else if ($delta < 60) {
					$str = $ren->trans('time_seconds_ago', $delta);
				} else if ($delta < 120) {
					$str = $ren->trans('time_minute_ago');
				} else if ($delta < 3600) {
					$str = $ren->trans('time_minutes_ago', ceil($delta/60));
				} else if ($delta < 7200) {
					$str = $ren->trans('time_hour_ago');
				} else if ($delta < 86400) {
					$str = $ren->trans('time_hours_ago', floor($delta/3600));
				} else if ($delta < 172800) {
					$str = $ren->trans('time_yesterday');
				} else if ($delta < 604800) {
					$str = $ren->trans('time_days_ago', floor($delta/86400));
				} else if ($delta < 1209600) {
					$str = $ren->trans('time_week_ago');
				} else if ($delta < 2592000) {
					$str = $ren->trans('time_weeks_ago', floor($delta/86400/7));
				} else if ($delta < 5184000) {
					$str = $ren->trans('time_month_ago');
				} else if ($delta < 31536000) {
					$str = $ren->trans('time_months_ago', floor($delta/86400/30));
				} else if ($delta < 63072000) {
					$str = $ren->trans('time_year_ago');
				} else if ($delta < 86400*365*50){
					$str = $ren->trans('time_years_ago', floor($delta/86400/365));
				} else {
					$str = $ren->trans('time_long_ago');
				}
			} else {
				$delta = -1 * $delta;

				if ($delta < 3) {
					$str = $ren->trans('time_in_a_moment');
				} else if ($delta < 10) {
					$str = $ren->trans('time_in_a_few_seconds');
				} else if ($delta < 60) {
					$str = $ren->trans('time_in_seconds', $delta);
				} else if ($delta < 120) {
					$str = $ren->trans('time_in_a_minute');
				} else if ($delta < 3600) {
					$str = $ren->trans('time_in_minutes', ceil(-$delta/60));
				} else if ($delta < 7200) {
					$str = $ren->trans('time_in_an_hour');
				} else if ($delta < 86400) {
					$str = $ren->trans('time_in_hours', floor($delta/3600));
				} else if ($delta < 172800) {
					$str = $ren->trans('time_tommorow');
				} else if ($delta < 604800) {
					$str = $ren->trans('time_in_days', floor($delta/86400));
				} else if ($delta < 1209600) {
					$str = $ren->trans('time_in_a_week');
				} else if ($delta < 2592000) {
					$str = $ren->trans('time_in_weeks', floor($delta/86400/7));
				} else if ($delta < 5184000) {
					$str = $ren->trans('time_in_a_month');
				} else if ($delta < 31536000) {
					$str = $ren->trans('time_in_months', floor($delta/86400/30));
				} else if ($delta < 63072000) {
					$str = $ren->trans('time_in_a_year');
				} else {
					$str = $ren->trans('time_in_years', floor($delta/86400/365));
				}
			}

			return $str;
		}
	}
}
