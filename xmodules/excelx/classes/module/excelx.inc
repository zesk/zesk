<?php
namespace zesk;

use \DOMDocument;
use \DOMElement;
use \DOMProcessingInstruction;
use \DOMNode;
use \Iterator;

class Module_ExcelX extends Module {
	public static function download($filename, array $headers, Iterator $iterator, Response $response) {
		// pretend content (which is XML) is XLS native
		$response->header("Pragma", "public");
		$response->header("Expires", "0");
		$response->header("Cache-Control", array(
			"must-revalidate, post-check=0, pre-check=0",
			"private"
		));
		$response->header("Content-Type", "application/vnd.ms-excel");
		$response->header("Content-Disposition", "attachment; filename=\"" . $filename . "\";");
		
		// construct skeleton
		$dom = new DOMDocument('1.0', 'utf-8');
		$dom->formatOutput = $dom->preserveSpaces = true; // optional
		$n = new DOMProcessingInstruction('mso-application', 'progid="Excel.Sheet"');
		$dom->appendChild($n);
		
		$workbook = $dom->appendChild(new DOMElement('Workbook'));
		$workbook->setAttribute('xmlns', 'urn:schemas-microsoft-com:office:spreadsheet');
		$workbook->setAttribute('xmlns:o', 'urn:schemas-microsoft-com:office:office');
		$workbook->setAttribute('xmlns:x', 'urn:schemas-microsoft-com:office:excel');
		$workbook->setAttribute('xmlns:ss', 'xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet');
		$workbook->setAttribute('xmlns:html', 'http://www.w3.org/TR/REC-html40');
		
		$styles = $workbook->appendChild(new DOMElement('Styles'));
		$style = $styles->appendChild(new DOMElement('Style'));
		$style->setAttribute('ss:ID', 'Default');
		$worksheet = $workbook->appendChild(new DOMElement('Worksheet'));
		$worksheet->setAttribute('ss:Name', 'sheet1');
		$xmltable = $worksheet->appendChild(new DOMElement('Table'));
		
		$xmlrow = self::new_row($xmltable);
		foreach ($headers as $header) {
			self::export_cell($xmlrow, $header);
		}
		foreach ($iterator as $row) {
			$xmlrow = self::new_row($xmltable);
			foreach ($headers as $header) {
				self::export_cell($xmlrow, avalue($row, $header, null));
			}
		}
		return $dom->saveXML();
	}
	public static function new_row(DOMNode $xmltable) {
		return $xmltable->appendChild(new DOMElement('Row'));
	}
	public static function export_cell(DOMNode $xmlrow, $cell) {
		$xmlcell = $xmlrow->appendChild(new DOMElement('Cell'));
		$xmldata = $xmlcell->appendChild(new DOMElement('Data', $cell));
		$xmldata->setAttribute('ss:Type', is_numeric($cell) ? 'Number' : 'String');
	}
}
