<?php
/**
 * payment_patch_0351
 * @package modules.payment
 */
class payment_patch_0351 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$this->log('Add addressRequired field...');
		$newPath = f_util_FileUtils::buildWebeditPath('modules/payment/persistentdocument/connector.xml');
		$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'payment', 'connector');
		$newProp = $newModel->getPropertyByName('addressRequired');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('payment', 'connector', $newProp);
		
		$sql = "UPDATE `m_payment_doc_connector` SET `addressrequired` = 1";
		$this->executeSQLQuery($sql);
		
		$this->log('Add currencyCode field...');
		$newPath = f_util_FileUtils::buildWebeditPath('modules/payment/persistentdocument/connector.xml');
		$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'payment', 'connector');
		$newProp = $newModel->getPropertyByName('currencyCode');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('payment', 'connector', $newProp);
		
		$this->log('Add minValue field...');
		$newPath = f_util_FileUtils::buildWebeditPath('modules/payment/persistentdocument/connector.xml');
		$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'payment', 'connector');
		$newProp = $newModel->getPropertyByName('minValue');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('payment', 'connector', $newProp);
		
		$this->log('Add maxValue field...');
		$newPath = f_util_FileUtils::buildWebeditPath('modules/payment/persistentdocument/connector.xml');
		$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'payment', 'connector');
		$newProp = $newModel->getPropertyByName('maxValue');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('payment', 'connector', $newProp);
		
		$this->execChangeCommand('compile-locales', array('payment'));
	}
}