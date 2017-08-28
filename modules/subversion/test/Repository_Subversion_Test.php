<?php
/**
 * @test_phpunit true
 */
namespace zesk;

/**
 * 
 * @author kent
 *
 */
class Repository_Subversion_Test extends Repository_TestCase {
	protected $load_modules = array(
		"Subversion"
	);
	/**
	 * Override in subclasses
	 *
	 * @var string
	 */
	protected $repository_class = __NAMESPACE__ . "\\" . "Repository_Subversion";
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \zesk\Repository_TestCase::testConfiguration()
	 */
	public function testConfiguration() {
		parent::testConfiguration();
		$svn_meta = path($this->path, ".svn");
		$this->assertTrue(is_dir($svn_meta), "is_dir($svn_meta)");
	}
}