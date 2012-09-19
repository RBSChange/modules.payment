<?php
/**
 * @package modules.payment
 */
class payment_Setup extends object_InitDataSetup
{
	public function install()
	{
		$this->executeModuleScript('init.xml');
	}
}