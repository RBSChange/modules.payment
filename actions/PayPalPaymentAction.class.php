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
		$remoteAddr = $_SERVER['REMOTE_ADDR'];
        $requestUri = $_SERVER['REQUEST_URI'];
	
		try
		{
			$this->getTransactionManager()->beginTransaction();
	        $ms = payment_ModuleService::getInstance();	
			$ms->log("BANKING PAYMENT PAYPAL from [".$remoteAddr." : ".$requestUri."] BEGIN");				
			
			$connectorService = payment_PaypalconnectorService::getInstance();
			$sessionInfo = $connectorService->getSessionInfo();
			$connector = payment_persistentdocument_paypalconnector::getInstanceById($sessionInfo['connectorId']);
			
			$finalPaymentAmt = $sessionInfo ['paymentAmount'];
			$token = $sessionInfo ['token'];
			$paymentType = $sessionInfo ['paymentType'];
			$currencyCodeType = $sessionInfo ['currencyCodeType'];
			$payerId = $sessionInfo ['payerId'];
			
			$resArray = array_merge($sessionInfo, $connector->confirmPayment ( $finalPaymentAmt, $token, $paymentType, $currencyCodeType, $payerId ));	
			$bankResponse = $connectorService->getBankResponse($resArray);
			
			$order = $bankResponse->getOrder();
			
			$connectorService->setPaymentResult($bankResponse, $order);
			$url = $sessionInfo['paymentURL'];
			
			$connectorService->setSessionInfo(array());
			$ms->log("BANKING PAYMENT PAYPAL from [".$remoteAddr." : ".$requestUri."] END AND REDIRECT : " . $url);
			$this->getTransactionManager()->commit();
		}
		catch ( Exception $e )
		{
			$ms->log("BANKING PAYMENT PAYPAL from [".$remoteAddr." : ".$requestUri."] FAILED : " . $e->getMessage());
			$this->getTransactionManager()->rollBack($e);
			$currentWebsite = website_WebsiteModuleService::getInstance()->getCurrentWebsite();
			$url = $currentWebsite->getUrlForLang(RequestContext::getInstance()->getLang());
		}
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