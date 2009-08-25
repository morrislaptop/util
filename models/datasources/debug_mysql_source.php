<?php

require (LIBS . 'model' . DS . 'datasources' . DS . 'dbo_source.php');
require (LIBS . 'model' . DS . 'datasources' . DS . 'dbo' . DS . 'dbo_mysql.php');

class DebugMysqlSource extends DboMysql
{
/**
 * Outputs the contents of the queries log.
 * Modified to show where the query came from
 *
 * @param boolean $sorted
 */
	function showLog($sorted = false) {
		if ($sorted) {
			$log = sortByKey($this->_queriesLog, 'took', 'desc', SORT_NUMERIC);
		} else {
			$log = $this->_queriesLog;
		}

		if ($this->_queriesCnt > 1) {
			$text = 'queries';
		} else {
			$text = 'query';
		}

		if (PHP_SAPI != 'cli') {
			print ("<table class=\"cake-sql-log table\" id=\"cakeSqlLog_" . preg_replace('/[^A-Za-z0-9_]/', '_', uniqid(time(), true)) . "\" summary=\"Cake SQL Log\" cellspacing=\"0\" border = \"0\">\n<caption>({$this->configKeyName}) {$this->_queriesCnt} {$text} took {$this->_queriesTime} ms</caption>\n");
			print ("<thead>\n<tr><th>Nr</th><th>Query</th><th>Error</th><th>Affected</th><th>Num. rows</th><th>Took (ms)</th><th width='40%'>Code</th></tr>\n</thead>\n<tbody>\n");

			foreach ($log as $k => $i) {
				print ("<tr><td>" . ($k + 1) . "</td><td>" . h($i['query']) . "</td><td>{$i['error']}</td><td style = \"text-align: right\">{$i['affected']}</td><td style = \"text-align: right\">{$i['numRows']}</td><td style = \"text-align: right\">{$i['took']}</td><td>" . nl2br($i['code']) . "</td></tr>\n");
			}
			print ("</tbody></table>\n");
		} else {
			foreach ($log as $k => $i) {
				print (($k + 1) . ". {$i['query']} {$i['error']}\n");
			}
		}
	}

/**
 * Log given SQL query.
 * Modified to record where the query came from
 *
 * @param string $sql SQL statement
 * @todo: Add hook to log errors instead of returning false
 */
	function logQuery($sql) {
		$this->_queriesCnt++;
		$this->_queriesTime += $this->took;
		$this->_queriesLog[] = array(
			'query' => $sql,
			'code' => $this->code(),
			'error'		=> $this->error,
			'affected'	=> $this->affected,
			'numRows'	=> $this->numRows,
			'took'		=> $this->took
		);
		if (count($this->_queriesLog) > $this->_queriesLogMax) {
			array_pop($this->_queriesLog);
		}
		if ($this->error) {
			return false;
		}
	}

/**
 * Puts in HTML which will show the code that started the query. Only puts in code inside the app
 */
 	function code() {
		$trace = Debugger::trace();
		$lines = explode("\n", $trace);

		// remove first 2, its just this file
		array_shift($lines);
		array_shift($lines);

		foreach ($lines as $key => $line) {
			if ( strpos($line, 'CORE') !== false  ) {
				// remove it.
				unset($lines[$key]);
			}
		}
		$out = implode("\n", $lines);
		return $out;
 	}
}
?>