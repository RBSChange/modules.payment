<?php
/**
 * payment_FreeconnectorScriptDocumentElement
 * @package modules.payment.persistentdocument.import
 */
class payment_FreeconnectorScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return payment_persistentdocument_freeconnector
     */
    protected function initPersistentDocument()
    {
    	return payment_FreeconnectorService::getInstance()->getNewDocumentInstance();
    }
    
    /**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_payment/freeconnector');
	}
}