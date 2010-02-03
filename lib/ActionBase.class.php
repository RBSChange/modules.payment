<?php
/**
 * @package modules.payment.lib
 */
class payment_ActionBase extends f_action_BaseAction
{

	/**
	 * Returns the payment_ConnectorService to handle documents of type "modules_payment/connector".
	 *
	 * @return payment_ConnectorService
	 */
	public function getConnectorService()
	{
		return payment_ConnectorService::getInstance();
	}

	/**
	 * Returns the payment_ChequeconnectorService to handle documents of type "modules_payment/chequeconnector".
	 *
	 * @return payment_ChequeconnectorService
	 */
	public function getChequeconnectorService()
	{
		return payment_ChequeconnectorService::getInstance();
	}

	/**
	 * Returns the payment_AtosconnectorService to handle documents of type "modules_payment/atosconnector".
	 *
	 * @return payment_AtosconnectorService
	 */
	public function getAtosconnectorService()
	{
		return payment_AtosconnectorService::getInstance();
	}

	/**
	 * Returns the payment_CybermutconnectorService to handle documents of type "modules_payment/cybermutconnector".
	 *
	 * @return payment_CybermutconnectorService
	 */
	public function getCybermutconnectorService()
	{
		return payment_CybermutconnectorService::getInstance();
	}
	
	/**
	 * Returns the payment_PaypalconnectorService to handle documents of type "modules_payment/paypalconnector".
	 * @return payment_PaypalconnectorService
	 */
	public function getPaypalconnectorService()
	{
		return payment_PaypalconnectorService::getInstance();
	}
}