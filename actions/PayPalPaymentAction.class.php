<?php
class payment_PayPalPaymentAction extends change_Action
{
	/**
	 * @see f_action_BaseAction::_execute()
	 *
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	protected function _execute($context, $request)
	{
		$connectorService = payment_PaypalconnectorService::getInstance();
		$sessionInfo = $connectorService->getSessionInfo();
		$connector = payment_persistentdocument_paypalconnector::getInstanceById($sessionInfo['connectorId']);
		$url = $connectorService->validatePayment($sessionInfo, $connector);
		$context->getController()->redirectToUrl($url);
		return VIEW::NONE;
	}
		
	/**
	 * @return integer
	 */
	public function getRequestMethods()
	{
		return change_Request::POST | change_Request::GET;
	}
	
	/**
	 * @return boolean
	 */
	public final function isSecure()
	{
		return false;
	}
}