<?php
class payment_PayPalPaymentAction extends payment_Action
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
        $ms = payment_ModuleService::getInstance();	
		$ms->log("BANKING PAYMENT from [".$remoteAddr." : ".$requestUri."] BEGIN");		
		try
		{
			$connectorService = payment_PaypalconnectorService::getInstance();
			$sessionInfo = $connectorService->getSessionInfo();
			$connector = $this->getPaypalConnector($sessionInfo);
			
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
			$ms->log("BANKING PAYMENT from [".$remoteAddr." : ".$requestUri."] END AND REDIRECT : " . $url);
			$context->getController()->redirectToUrl($url);
			return VIEW::NONE;
		}
		catch ( Exception $e )
		{
			$ms->log("BANKING PAYMENT from [".$remoteAddr." : ".$requestUri."] FAILED : " . $e->getMessage());
			Framework::exception($e);
		}
		$currentWebsite = website_WebsiteModuleService::getInstance()->getCurrentWebsite();
		$context->getController()->redirectToUrl($currentWebsite->getUrlForLang(RequestContext::getInstance()->getLang()));
		return VIEW::NONE;
	}
	
	/**
	 * @param array $sessionInfo
	 * @return payment_persistentdocument_paypalconnector 
	 */
	private function getPaypalConnector($sessionInfo)
	{
		return DocumentHelper::getDocumentInstance ( $sessionInfo ['connectorId'], 'modules_payment/paypalconnector' );
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