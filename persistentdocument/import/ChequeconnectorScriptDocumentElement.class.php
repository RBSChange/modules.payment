<?php
/**
 * payment_ChequeconnectorScriptDocumentElement
 * @package modules.payment.persistentdocument.import
 */
class payment_ChequeconnectorScriptDocumentElement extends import_ScriptDocumentElement
{
	/**
	 * @return payment_persistentdocument_chequeconnector
	 */
	protected function initPersistentDocument()
	{
		return payment_ChequeconnectorService::getInstance()->getNewDocumentInstance();
	}
	
	/**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_payment/chequeconnector');
	}
}