<?php
/**
 * Profiler Class (borrowed from kohana)
 *
 * <pre>
 * $benchmark = Profiler::start('APP', __FUNCTION__);
 * Profiler::stop($benchmark);
 * # Profiler::show();
 * </pre>
 *
 * @author lzyy http://blog.leezhong.com
 * @version 0.1.1
 */
class Profiler {

	protected static $_marks = array();

	public static $enabled = FALSE;

	public static function start($group, $name)
	{
		static $counter = 0;
		$token = 'kp/'.base_convert($counter++, 10, 32);
		Profiler::$_marks[$token] = array (
			'group' => strtolower($group),
			'name'  => (string) $name,
			'start_time'   => microtime(true),
			'start_memory' => memory_get_usage(),
			'stop_time'    => true,
			'stop_memory'  => true,
		);

		return $token;
	}

	public static function stop($token)
	{
		Profiler::$_marks[$token]['stop_time']   = microtime(true);
		Profiler::$_marks[$token]['stop_memory'] = memory_get_usage();
	}

	public static function delete($token)
	{
		unset(Profiler::$_marks[$token]);
	}

	public static function groups()
	{
		$groups = array();
		foreach (Profiler::$_marks as $token => $mark)
		{
			$groups[$mark['group']][$mark['name']][] = $token;
		}
		return $groups;
	}

	public static function stats(array $tokens)
	{
		$min = $max = array(
			'time' => null,
			'memory' => null
			);

		$total = array(
			'time' => 0,
			'memory' => 0
			);

		foreach ($tokens as $token)
		{
			list($time, $memory) = Profiler::total($token);

			if ($max['time'] === null || $time > $max['time'])
			{
				$max['time'] = $time;
			}

			if ($min['time'] === null || $time < $min['time'])
			{
				$min['time'] = $time;
			}

			$total['time'] += $time;

			if ($max['memory'] === null || $memory > $max['memory'])
			{
				$max['memory'] = $memory;
			}

			if ($min['memory'] === null || $memory < $min['memory'])
			{
				$min['memory'] = $memory;
			}

			$total['memory'] += $memory;
		}

		$count = count($tokens);
		$average = array(
			'time' => $total['time'] / $count,
			'memory' => $total['memory'] / $count
			);

		return array(
			'min' => $min,
			'max' => $max,
			'total' => $total,
			'average' => $average
		);
	}

	public static function group_stats($groups = null)
	{
		$groups = ($groups === null)
			? Profiler::groups()
			: array_intersect_key(Profiler::groups(), array_flip((array) $groups));

		// add 0.1.1
		$stats = array();
		// end 0.1.1

		foreach ($groups as $group => $names)
		{
			foreach ($names as $name => $tokens)
			{
				$_stats = Profiler::stats($tokens);
				$stats[$group][$name] = $_stats['total'];
			}
		}

		$groups = array();

		foreach ($stats as $group => $names)
		{
			$groups[$group]['min'] = $groups[$group]['max'] = array(
				'time' => null,
				'memory' => null
				);

			$groups[$group]['total'] = array(
				'time' => 0,
				'memory' => 0
				);

			foreach ($names as $total)
			{
				if (!isset($groups[$group]['min']['time']) || $groups[$group]['min']['time'] > $total['time'])
				{
					$groups[$group]['min']['time'] = $total['time'];
				}
				if (!isset($groups[$group]['min']['memory']) || $groups[$group]['min']['memory'] > $total['memory'])
				{
					$groups[$group]['min']['memory'] = $total['memory'];
				}
				if (!isset($groups[$group]['max']['time']) || $groups[$group]['max']['time'] < $total['time'])
				{
					$groups[$group]['max']['time'] = $total['time'];
				}
				if (!isset($groups[$group]['max']['memory']) || $groups[$group]['max']['memory'] < $total['memory'])
				{
					$groups[$group]['max']['memory'] = $total['memory'];
				}

				$groups[$group]['total']['time'] += $total['time'];
				$groups[$group]['total']['memory'] += $total['memory'];
			}

			$count = count($names);
			$groups[$group]['average']['time'] = $groups[$group]['total']['time'] / $count;
			$groups[$group]['average']['memory'] = $groups[$group]['total']['memory'] / $count;
		}

		return $groups;
	}

	public static function total($token)
	{
		$mark = Profiler::$_marks[$token];

		if ($mark['stop_time'] === false)
		{
			$mark['stop_time'] = microtime(true);
			$mark['stop_memory'] = memory_get_usage();
		}

		return array(
			$mark['stop_time'] - $mark['start_time'],
			$mark['stop_memory'] - $mark['start_memory'],
		);
	}

	public static function application()
	{
		if (!defined('APP_START_TIME'))
		{
			$time = 'please define APP_START_TIME at start. APP_START_TIME = microtime(TRUE)';
		} 
		else
		{
			$time = microtime(true) - APP_START_TIME;
		}

		if (!defined('APP_START_MEMORY'))
		{
			$memory = 'please define APP_START_MEMORY at start. APP_START_MEMORY = memory_get_usage()';
		}
		else
		{
			$memory = memory_get_usage() - APP_START_MEMORY;
		}

		return array('time' => $time, 'memory' => $memory);
	}

	public static function show($type = 'view')
	{
		// add 0.1.1
		if (!Profiler::$enabled)
			return;
		// end 0.1.1

		static $registed = false;
		if (!$registed)
		{
			register_shutdown_function(array('Profiler', 'doShow'));
			$registed = true;
		}
	}

	//{{{ show html
	public static function doShow()
	{
?>
<style type="text/css">
.noah table.profiler { width: 99%; margin: 0 auto 1em; border-collapse: collapse; }
.noah table.profiler th,
.noah table.profiler td { padding: 0.2em 0.4em; background: #fff; border: solid 1px #999; border-width: 1px 0; text-align: left; font-weight: normal; font-size: 1em; color: #111; text-align: right; }
.noah table.profiler th.name { text-align: left; }
.noah table.profiler tr.group th { font-size: 1.4em; background: #222; color: #eee; border-color: #222; }
.noah table.profiler tr.group td { background: #222; color: #777; border-color: #222; }
.noah table.profiler tr.group td.time { padding-bottom: 0; }
.noah table.profiler tr.headers th { text-transform: lowercase; font-variant: small-caps; background: #ddd; color: #777; }
.noah table.profiler tr.mark th.name { width: 40%; font-size: 1.2em; background: #fff; vertical-align: middle; }
.noah table.profiler tr.mark td { padding: 0; }
.noah table.profiler tr.mark.final td { padding: 0.2em 0.4em; }
.noah table.profiler tr.mark td > div { position: relative; padding: 0.2em 0.4em; }
.noah table.profiler tr.mark td div.value { position: relative; z-index: 2; }
.noah table.profiler tr.mark td div.graph { position: absolute; top: 0; bottom: 0; right: 0; left: 100%; background: #71bdf0; z-index: 1; }
.noah table.profiler tr.mark.memory td div.graph { background: #acd4f0; }
.noah table.profiler tr.mark td.current { background: #eddecc; }
.noah table.profiler tr.mark td.min { background: #d2f1cb; }
.noah table.profiler tr.mark td.max { background: #ead3cb; }
.noah table.profiler tr.mark td.average { background: #ddd; }
.noah table.profiler tr.mark td.total { background: #d0e3f0; }
.noah table.profiler tr.time td { border-bottom: 0; font-weight: bold; }
.noah table.profiler tr.memory td { border-top: 0; }
.noah table.profiler tr.final th.name { background: #222; color: #fff; }
.noah table.profiler abbr { border: 0; color: #777; font-weight: normal; }
.noah table.profiler:hover tr.group td { color: #ccc; }
.noah table.profiler:hover tr.mark td div.graph { background: #1197f0; }
.noah table.profiler:hover tr.mark.memory td div.graph { background: #7cc1f0; }
.noah table.profiler tr.app td {border-right:1px solid #999}
</style>

<?php
$group_stats      = Profiler::group_stats();
$group_cols       = array('min', 'max', 'average', 'total');
?>

<div class="noah">

	<table class="profiler">
		<tr class="group"><th class="name">$_GET</th><th></th></tr>
		<?php foreach($_GET as $key => $value) {
			echo '<tr><td class="mark" style="text-align:left;vertical-align:middle;width:25%;height:50px;font-size:20px">"'.$key.'"</td><td style="background:#ddd;text-align:left;vertical-align:middle">'.$value.'</td></tr>';
		} ?>
	</table>

	<table class="profiler">
		<tr class="group"><th class="name">$_POST</th><th></th></tr>
		<?php foreach($_POST as $key => $value) {
			echo '<tr><td class="mark" style="text-align:left;vertical-align:middle;width:25%;height:50px;font-size:20px">"'.$key.'"</td><td style="background:#ddd;text-align:left;vertical-align:middle">'.$value.'</td></tr>';
		} ?>
	</table>

	<table class="profiler">
		<tr class="group"><th class="name">$_COOKIE</th><th></th></tr>
		<?php foreach($_COOKIE as $key => $value) {
			echo '<tr><td class="mark" style="text-align:left;vertical-align:middle;width:25%;height:50px;font-size:20px">"'.$key.'"</td><td style="background:#ddd;text-align:left;vertical-align:middle">'.$value.'</td></tr>';
		} ?>
	</table>

	<?php foreach (Profiler::groups() as $group => $benchmarks): ?>
	<table class="profiler">
		<tr class="group">
			<th class="name" rowspan="2"><?php echo ucfirst($group) ?></th>
			<td class="time" colspan="4"><?php echo $group_stats[$group]['total']['time'] ?> <abbr title="seconds">s</abbr></td>
		</tr>
		<tr class="group">
			<td class="memory" colspan="4"><?php echo number_format($group_stats[$group]['total']['memory'] / 1024, 4) ?> <abbr title="kilobyte">kB</abbr></td>
		</tr>
		<tr class="headers">
			<th class="name"><?php echo 'Benchmark' ?></th>
			<?php foreach ($group_cols as $key): ?>
			<th class="<?php echo $key ?>"><?php echo ucfirst($key) ?></th>
			<?php endforeach ?>
		</tr>
		<?php foreach ($benchmarks as $name => $tokens): ?>
		<tr class="mark time">
			<?php $stats = Profiler::stats($tokens) ?>
			<th class="name" rowspan="2" scope="rowgroup"><?php echo $name, ' (', count($tokens), ')' ?></th>
			<?php foreach ($group_cols as $key): ?>
			<td class="<?php echo $key ?>">
				<div>
					<div class="value"><?php echo number_format($stats[$key]['time'], 6) ?> <abbr title="seconds">s</abbr></div>
					<?php if ($key === 'total'): ?>
						<div class="graph" style="left: <?php echo max(0, 100 - $stats[$key]['time'] / $group_stats[$group]['max']['time'] * 100) ?>%"></div>
					<?php endif ?>
				</div>
			</td>
			<?php endforeach ?>
		</tr>
		<tr class="mark memory">
			<?php foreach ($group_cols as $key): ?>
			<td class="<?php echo $key ?>">
				<div>
					<div class="value"><?php echo number_format($stats[$key]['memory'] / 1024, 4) ?> <abbr title="kilobyte">kB</abbr></div>
					<?php if ($key === 'total'): ?>
						<div class="graph" style="left: <?php echo max(0, 100 - $stats[$key]['memory'] / $group_stats[$group]['max']['memory'] * 100) ?>%"></div>
					<?php endif ?>
				</div>
			</td>
			<?php endforeach ?>
		</tr>
		<?php endforeach ?>
	</table>
	<?php endforeach ?>

	<table class="profiler">
		<?php $stats = Profiler::application() ?>
		<tr class="final mark time">
			<th class="name" rowspan="2" scope="rowgroup"><?php echo 'Application Execution' ?></th>
		</tr>
		<tr class="app">
			<?php 
			$time = $stats['time'];
			if(!is_string($time)) {
				$time = number_format($stats['time'], 6).'<abbr title="seconds">s</abbr>';
			}
			$memory = $stats['memory'];
			if(!is_string($memory)) {
				$memory = number_format($stats['memory'] / 1024, 4).'<abbr title="kilobyte">kB</abbr>';
			}
			?>
			<td class="time"><?php echo $time; ?> </td>
			<td class="memory"><?php echo $memory; ?> </td>
		</tr>
	</table>

</div>

<?php
	}
	//}}}

}
