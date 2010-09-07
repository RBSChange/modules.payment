<?php
/**
 * payment_patch_0302
 * @package modules.payment
 */
class payment_patch_0302 extends patch_BasePatch
{
//  by default, isCodePatch() returns false.
//  decomment the following if your patch modify code instead of the database structure or content.
    /**
     * Returns true if the patch modify code that is versionned.
     * If your patch modify code that is versionned AND database structure or content,
     * you must split it into two different patches.
     * @return Boolean true if the patch modify code that is versionned.
     */
//	public function isCodePatch()
//	{
//		return true;
//	}
 
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$this->executeLocalXmlScript('list.xml');
		
		$newPath = f_util_FileUtils::buildWebeditPath('modules/payment/persistentdocument/atosconnector.xml');
		$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'payment', 'atosconnector');
		$newProp = $newModel->getPropertyByName('paymentmeans');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('payment', 'atosconnector', $newProp);
		
		$this->execChangeCommand('compile-locales', array('payment'));
		
		$atoss = payment_AtosconnectorService::getInstance()->createQuery()->find();
		foreach ($atoss as $atos) 
		{
			payment_AtosconnectorService::getInstance()->updateCertificateIfNeeded($atos);
		}
	}

	/**
	 * @return String
	 */
	protected final function getModuleName()
	{
		return 'payment';
	}

	/**
	 * @return String
	 */
	protected final function getNumber()
	{
		return '0302';
	}
}