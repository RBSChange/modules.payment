<?php
/**
 * payment_patch_0301
 * @package modules.payment
 */
class payment_patch_0301 extends patch_BasePatch
{
 
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		parent::execute();
		$this->log('Compilation des locales du module');
		f_util_System::exec('change.php compile-locales payment');
	}

	/**
	 * Returns the name of the module the patch belongs to.
	 *
	 * @return String
	 */
	protected final function getModuleName()
	{
		return 'payment';
	}

	/**
	 * Returns the number of the current patch.
	 * @return String
	 */
	protected final function getNumber()
	{
		return '0301';
	}
}