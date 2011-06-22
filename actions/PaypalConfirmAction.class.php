<?php
class payment_PaypalConfirmAction extends f_action_BaseAction
{
	/**
	 * @see f_action_BaseAction::_execute()
	 *
	 * @param Context $context
	 * @param Request $request
	 */
	protected function _execute($context, $request)
	{
 		$remoteAddr = $_SERVER['REMOTE_ADDR'];
        $requestUri = $_SERVER['REQUEST_URI'];
 		$payerId = $request->getParameter('PayerID');
 		$ms = payment_ModuleService::getInstance();	
		$ms->log("BANKING CONFIRM PAYPAL from [".$remoteAddr." : ".$requestUri."] PayerId: $payerId");
		
		$connectorService = payment_PaypalconnectorService::getInstance(); 
		$sessionInfo = $connectorService->getSessionInfo();
		$sessionInfo['payerId'] = $payerId;
		$connectorService->setSessionInfo($sessionInfo);
		$connector = payment_persistentdocument_paypalconnector::getInstanceById($sessionInfo['connectorId']);
		$connectorService->validatePayment($sessionInfo, $connector);
		$url = $sessionInfo['paymentURL'];
		$context->getController()->redirectToUrl($url);
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