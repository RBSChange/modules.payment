<?php
/**
 * Class where to put your custom methods for document payment_persistentdocument_ogoneconnector
 * @package modules.payment.persistentdocument
 */
class payment_persistentdocument_ogoneconnector extends payment_persistentdocument_ogoneconnectorbase 
{
	/**
	 * @return string
	 */
	public function getTemplateViewName()
	{
		return 'Ogone';
	}
	
	/**
	 * @return String
	 */
	public function getListenerURL()
	{
		return "http://" . Framework::getUIDefaultHost() . "/payment/ogoneListener.php";
	}
}