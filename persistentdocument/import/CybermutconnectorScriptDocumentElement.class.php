<?php
/**
 * payment_CybermutconnectorScriptDocumentElement
 * @package modules.payment.persistentdocument.import
 */
class payment_CybermutconnectorScriptDocumentElement extends import_ScriptDocumentElement
{
	/**
	 * @return payment_persistentdocument_cybermutconnector
	 */
	protected function initPersistentDocument()
	{
		return payment_CybermutconnectorService::getInstance()->getNewDocumentInstance();
	}
	
	/**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_payment/cybermutconnector');
	}
}