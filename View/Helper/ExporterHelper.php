<?php
App::uses('AppHelper', 'View/Helper');

class ExporterHelper extends AppHelper {


	/*
	  $defaults = array('csvEncoding' => 'SJIS-win',
	  'hasHeader' => false,
	  'skipHeaderCount' => 1,
	  'delimiter' => ',',
	  'enclosure' => '"',
	  'forceImport' => false,
	  'saveMethod' => false,
	  'allowExtension' => false,
	  );
	*/

	public $options;

	private $__temporaryDir = '';

	private $__temporaryFile = '';
	private $__temporaryFileSize = 0;
	private $__temporaryFileZipped = '';

	public function __construct(View $View, $settings = array()) {
		parent::__construct($View, $settings);

		$this->__temporaryDir = TMP . $this->request->params['controller'];
		if (! file_exists($this->__temporaryDir)) {
			$umask = umask(0);
			mkdir($this->__temporaryDir, 0777);
			umask($umask);
		}

	}

	private function __convertCsvLine($line) {
		$ret = array();
        foreach ($line as $data) {
            if (preg_match('/[,"\n]/', $data)) {
                $data = preg_replace('/\"/', '""', $data);
                $data = '"' . $data . '"';
            }
            $ret[] = mb_convert_encoding($data, $this->options['csvEncoding'], 'UTF-8');
		}
		return $ret;
	}

	private function __saveTemporaryFile($lines) {
		$this->__getTemporaryFilenames();

		$handle = fopen($this->__temporaryDir . '/' . $this->__temporaryFile, 'w');
		if (!$handle) {
			throw new YacsvException(__d('Yacsv: Can not open temporary file'));
		}

		if (is_array($this->options['header'])) {
			$header = mb_convert_encoding(implode($this->options['delimiter'], $this->options['header']), $this->options['csvEncoding'], 'UTF-8');
			fwrite($handle,  $header . $this->options['linefeed']);
		}

		foreach ($lines as $line) {
			$dataArray = array();

			foreach ($this->options['fields'] as $field) {
				if (preg_match('/^([^\.]+)\.([^\.]+)$/',$field, $match)) {
					$model = $match[1];
					$field = $match[2];
					$dataArray[] = $line[$model][$field];
				} else {
					$dataArray[] = $line[0][$field];
				}
			}

			$tmp = $this->__convertCsvLine($dataArray);
			$data = implode($this->options['delimiter'], $tmp) . $this->options['linefeed'];
			fwrite($handle, $data);
		}
		fclose($handle);
	}

	private function __getTemporaryFilenames() {
		$file = uniqid('exportCsv_');
		$this->__temporaryFile = $file . '.csv';
		$this->__temporaryFileZipped = $file . '.zip';
		return $this->__temporaryFile;
	}

	public function csv(&$lines, $options = array()) {
        $defaults = array(
			//'csvEncoding' => 'SJIS-win',
			'csvEncoding' => 'SJIS-win',
			'delimiter' => ',',
			'enclosure' => '"',
			'filename' => 'data.csv',
			'zipped' => false,
			'linefeed' => "\r\n",
			'fields' => null,
			'header' => null,
		);
        $this->options = array_merge($defaults, $options);

		if (is_null($this->options['fields'])) {
			throw new YacsvException(__d('Yacsv: Require to set fields option'));
		}

		$this->__saveTemporaryFile($lines);

		// ヘッダ表示
		$size = filesize($this->__temporaryDir . '/' . $this->__temporaryFile);

		header('Content-Disposition: attachment; filename=' . $this->options['filename']);
		header('Content-Length: ' . $size);

		ob_end_clean();
		readfile($this->__temporaryDir .'/'. $this->__temporaryFile);

		unlink($this->__temporaryDir . '/' . $this->__temporaryFile);
	}
}