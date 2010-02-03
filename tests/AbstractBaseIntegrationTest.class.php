<?php
/**
 * @package modules.payment.tests
 */
abstract class payment_tests_AbstractBaseIntegrationTest extends payment_tests_AbstractBaseTest
{
	/**
	 * @return void
	 */
	public function prepareTestCase()
	{
		$this->loadSQLResource('integration-test.sql', true, false);
	}
}