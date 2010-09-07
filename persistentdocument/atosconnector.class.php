<?php
/**
 * payment_persistentdocument_atosconnector
 * @package payment.persistentdocument
 */
class payment_persistentdocument_atosconnector extends payment_persistentdocument_atosconnectorbase
{

	public function getTemplateViewName()
	{
		return 'Atos';
	}
	
	/**
	 * @return string
	 */
	public function getPaymentmeansbo()
	{
		if (f_util_StringUtils::isNotEmpty($this->getPaymentmeans()))
		{
			return str_replace(array(',1', ',2', ',3', ',4'), array('.1', '.2', '.3', '.4'), $this->getPaymentmeans());
		}
		return null;
	}
	
	/**
	 * 
	 * @param string $paymentmeans
	 */
	public function setPaymentmeansbo($paymentmeans)
	{
		if (f_util_StringUtils::isNotEmpty($paymentmeans))
		{
			$this->setPaymentmeans(str_replace(array('.1', '.2', '.3', '.4'), array(',1', ',2', ',3', ',4'), $paymentmeans));
		}
		else
		{
			$this->setPaymentmeans(null);
		}
	}
	
	public function getPaymentIcons()
	{
		if (f_util_StringUtils::isNotEmpty($this->getPaymentmeans()))
		{
			$data = explode(',', str_replace(array(',1', ',2', ',3', ',4'), '.gif', $this->getPaymentmeans()));
		}
		else
		{
			$data = array('CB.gif', 'VISA.gif', 'MASTERCARD.gif');
		}
		$result = array();
		foreach ($data as $name) 
		{
			$result[] = MediaHelper::getFrontofficeStaticUrl($name);
		}
		return $result;
	}
}