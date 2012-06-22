<?php
/**
 * payment_PaypalconnectorScriptDocumentElement
 * @package modules.payment.persistentdocument.import
 */
class payment_PaypalconnectorScriptDocumentElement extends import_ScriptDocumentElement
{
	/**
	 * @return payment_persistentdocument_paypalconnector
	 */
	protected function initPersistentDocument()
	{
		return payment_PaypalconnectorService::getInstance()->getNewDocumentInstance();
	}
	
	/**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_payment/paypalconnector');
	}
}