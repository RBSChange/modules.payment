<?php
class payment_PayPalPaymentAction extends f_action_BaseAction
{
	/**
	 * @see f_action_BaseAction::_execute()
	 *
	 * @param Context $context
	 * @param Request $request
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
	 * @return Integer
	 */
	public function getRequestMethods()
	{
		return Request::POST | Request::GET;
	}
	
	/**
	 * @return Boolean
	 */
	public final function isSecure()
	{
		return false;
	}
}