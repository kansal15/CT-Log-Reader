<?php
require_once dirname(__FILE__) . '/../tldextract.php';

class IntegrationTest  extends PHPUnit_Framework_TestCase {

	/**
	 * Ensure that downloading the TLD list from PSL works properly, that the list
	 * gets cached, and that the local snapshot is up to date.
	 */
	public function testSnapshotAndCache() {
		$temporaryCacheFile = tempnam(sys_get_temp_dir(), 'tld_cache');
		$snapshotFile = dirname(__FILE__) . '/../.tld_set_snapshot';

		$this->assertFileExists($snapshotFile, 'The local TLD set snapshot must exist.');

		$extractor = new TLDExtract(true, $temporaryCacheFile);
		$extractor->extract('http://www.exmaple.com/');
		$this->assertFileExists($temporaryCacheFile, 'Must be able to download and cache the TLD list.');

		$snapshotTlds = unserialize(file_get_contents($snapshotFile));
		$cachedTlds = unserialize(file_get_contents($temporaryCacheFile));

		foreach(array('public', 'private') as $section) {
			$this->assertEmpty(
				array_diff($cachedTlds[$section], $snapshotTlds[$section]),
				sprintf('Local TLD snapshot must be up to date [section: %s].', $section)
			);
		}
		unlink($temporaryCacheFile);
	}
}
