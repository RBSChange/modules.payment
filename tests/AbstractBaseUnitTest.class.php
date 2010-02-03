<?php
/**
 * @package modules.payment.tests
 */
abstract class payment_tests_AbstractBaseUnitTest extends payment_tests_AbstractBaseTest
{
	/**
	 * @return void
	 */
	public function prepareTestCase()
	{
		$this->resetDatabase();
	}
}