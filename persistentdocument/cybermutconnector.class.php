<?php
/**
 * payment_persistentdocument_cybermutconnector
 * @package payment.persistentdocument
 */
class payment_persistentdocument_cybermutconnector extends payment_persistentdocument_cybermutconnectorbase
{
	public function getTemplateViewName()
	{
		return 'Cybermut';
	}
	
	/**
	 * @return string
	 */
	public function getListenerURL()
	{
		return "http://" . Framework::getUIDefaultHost() . "/payment/cybermutListenerResponse.php";
	}
}