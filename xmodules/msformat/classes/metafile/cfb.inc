<?php
/**
 * 
 * 
 */
namespace zesk;

/**
 * 
 * @author kent
 *
 */
class Metafile_CFB extends Metafile {
	protected $minor_version = null;
	protected $major_version = null;
	
	/**
	 * @var integer
	 */
	protected $sector_size = null;
	
	/**
	 * Number of Directory Sectors (4 bytes): This integer field contains the count of the number
	 * of directory sectors in the compound file.
	 *
	 * If Major Version is 3, then the Number of Directory Sectors MUST be zero. This field is not supported for version 3 compound files.
	 * 
	 * @var integer
	 */
	protected $directory_sectors_count = null;
	
	/**
	 * First Directory Sector Location (4 bytes): This integer field contains the starting sector number for the directory stream.
	 * 
	 * @var integer
	 */
	protected $directory_sector_offset = null;
	
	/**
	 * Transaction Signature Number (4 bytes): This integer field MAY contain a sequence number that is incremented every time the compound file is saved by an implementation that supports file transactions. This is field that MUST be set to all zeroes if file transactions are not implemented.<1>
	 * 
	 * @var integer
	 */
	protected $transaction_signature_number = null;
	
	/**
	 * First Mini FAT Sector Location (4 bytes): This integer field contains the starting sector number for the mini FAT.
	 * 
	 * @var integer
	 */
	protected $mini_fat_sector_location = null;
	
	/**
	 * Number of Mini FAT Sectors (4 bytes): This integer field contains the count of the number of mini FAT sectors in the compound file.
	 * 
	 * @var integer
	 */
	protected $mini_fat_sector_count = null;
	
	/**
	 * First DIFAT Sector Location (4 bytes): This integer field contains the starting sector number for the DIFAT.
	 * 
	 * @var integer
	 */
	protected $difat_sector_location = null;
	
	/**
	 * Number of DIFAT Sectors (4 bytes): This integer field contains the count of the number of DIFAT sectors in the compound file.
	 * 
	 * @var integer
	 */
	protected $difat_sector_count = null;
	
	/**
	 * DIFAT (436 bytes): This array of 32-bit integer fields contains the first 109 FAT sector locations of the compound file.
	 * 
	 * @var array
	 */
	protected $difat = array();
	function validate() {
		$this->read_header();
	}
	
	// CFB_Functionality
	private function read_header() {
		$starting_offset = $this->stream->offset();
		echo "START: $starting_offset\n";
		$signature = $this->read_byte(8);
		if ($signature !== array(
			0xD0,
			0xCF,
			0x11,
			0xE0,
			0xA1,
			0xB1,
			0x1A,
			0xE1
		)) {
			throw new Exception_File_Format("CFB Header signature incorrect " . PHP::dump($signature));
		}
		$header_clsid = $this->read_byte(16);
		if ($header_clsid !== array_fill(0, 16, 0)) {
			$this->warning("Header_CLSID should be all zeros, it's not: " . PHP::dump($header_clsid));
		}
		$this->minor_version = $this->read_integer(2);
		if ($this->minor_version !== 0x003E) {
			$this->warning(__("Header Minor version should {0}, it's {1}", 0x003E, $this->minor_version));
		}
		$this->major_version = $this->read_integer(2);
		if (!in_array($this->major_version, array(
			3,
			4
		))) {
			$this->warning(__("Header Minor version should 3 or 4, but it's: {0}", $this->major_version));
		}
		$byte_order = $this->read_integer(2);
		if ($byte_order !== 0xFFFE) {
			throw new Exception_File_Format("CFB Header byte order must be 0xFFFE, it's " . dechex($byte_order));
		}
		$sector_shift = $this->read_integer(2);
		if ($this->major_version === 3) {
			if ($sector_shift !== 0x0009) {
				throw new Exception_File_Format("Sector shift must be 0x0009 ($sector_shift)");
			}
			$this->sector_size = 512;
		} else { /* if ($this->major_version === 4) */
			if ($sector_shift !== 0x000C) {
				throw new Exception_File_Format("Sector shift must be 0x0009 ($sector_shift)");
			}
			$this->sector_size = 4096;
		}
		if (($mini_sector_shift = $this->read_integer(2)) !== 0x0006) {
			throw new Exception_File_Format("Min sector shift must be 0x0006 ($mini_sector_shift)");
		}
		if (($zeros = $this->read_byte(6)) !== array_fill(0, 6, 0)) {
			$this->warning("Header Reserved (6) should be all zeros, it's not: " . PHP::dump($zeros));
		}
		$this->directory_sectors_count = $this->read_integer(4);
		if ($this->major_version === 3 && $this->directory_sectors_count !== 0) {
			throw new Exception_File_Format("Directory Sectors must be 0 in version 3");
		}
		
		$this->fat_sector_count = $this->read_integer(4);
		$this->directory_sector_offset = $this->read_integer(4);
		$this->transaction_signature_number = $this->read_integer(4);
		if (($mini_stream_cutoff_size = $this->read_integer(4)) !== ($must = 0x00001000)) {
			throw new Exception_File_Format("Mini Stream Cutoff size must be $must (is $mini_stream_cutoff_size)");
		}
		$this->mini_fat_sector_location = $this->read_integer(4);
		$this->mini_fat_sector_count = $this->read_integer(4);
		$this->difat_sector_location = $this->read_integer(4);
		$this->difat_sector_count = $this->read_integer(4);
		$this->difat = $this->read_integer(4, 109);
		if ($this->major_version === 4) {
			$zeros = $this->stream->read(3584);
			if ($zeros !== str_repeat(chr(0), strlen($zeros))) {
				throw new Exception_File_Format("Version 4 file not padded with zeros");
			}
		}
		$ending_offset = $this->stream->offset();
		echo "END: $ending_offset\n";
		$bytes_read = $ending_offset - $starting_offset;
		if ($bytes_read !== $this->sector_size) {
			throw new Exception_Invalid("Offsets read ($bytes_read) does not match sector size ($this->sector_size)");
		}
		return $this;
	}
	
	/**
	 * A sector number can be converted into a byte offset into the file by using the following formula: 
	 * 
	 * (sector number + 1) x Sector Size. 
	 * 
	 * This implies that sector #0 of the file begins at byte offset Sector Size, not at 0.
	 */
	protected function load_sector($number) {
	}
	function load_fat() {
		/*
		 * Special values are reserved for chain terminators 
		 * 
		 * (ENDOFCHAIN = 0xFFFFFFFE), 
		 * 
		 * free sectors (FREESECT = 0xFFFFFFFF), and 
		 * 
		 * sectors that contain storage for FAT sectors (FATSECT = 0xFFFFFFFD) or 
		 * 
		 * DIFAT Sectors (DIFSECT = 0xFFFFFFC), 
		 * which are not chained in the same way as the others.
		 */
	}
}
