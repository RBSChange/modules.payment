<?php
class payment_PaypalConfirmAction extends change_Action
{
	/**
	 * @see f_action_BaseAction::_execute()
	 *
	 * @param change_Context $context
	 * @param change_Request $request
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