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
		
		$sessionInfo = payment_ConnectorService::getInstance()->getSessionInfo();
		$sessionInfo['payerId'] = $payerId;
		payment_ConnectorService::getInstance()->setSessionInfo($sessionInfo);
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