<?php
/**
 * Class where to put your custom methods for document payment_persistentdocument_freeconnector
 * @package modules.payment.persistentdocument
 */
class payment_persistentdocument_freeconnector extends payment_persistentdocument_freeconnectorbase 
{
	/**
	 * @return string
	 */	
	public function getTemplateViewName()
	{
		return 'Free';
	}
}