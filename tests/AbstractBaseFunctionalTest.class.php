<?php
/**
 * @package modules.payment.tests
 */
abstract class payment_tests_AbstractBaseFunctionalTest extends payment_tests_AbstractBaseTest
{
	/**
	 * @return void
	 */
	public function prepareTestCase()
	{
		$this->loadSQLResource('functional-test.sql', true, false);
	}
}