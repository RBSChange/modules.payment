<?php
class payment_BankResponseChequeAction extends payment_Action
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
		$ms->log("BANKING CHEQUE from [".$remoteAddr." : ".$requestUri."] BEGIN");	
        
		try
		{
			$connectorService = payment_ChequeconnectorService::getInstance();
			$sessionInfo = $connectorService->getSessionInfo();
			if (count($sessionInfo) == 0)
			{
				throw new Exception('Session expired');
			}			
			if ($request->hasParameter('accept'))
			{
				$sessionInfo['status'] = 'waiting';
			}
			else
			{
				$sessionInfo['status'] = 'failed';
			}
			
			$bankResponse = $connectorService->getBankResponse($sessionInfo);
			$order = $bankResponse->getOrder();
			
			$connectorService->setPaymentResult($bankResponse, $order);
			$url = $sessionInfo['paymentURL'];
			
			$connectorService->setSessionInfo(array());
			$ms->log("BANKING CHEQUE from [".$remoteAddr." : ".$requestUri."] END AND REDIRECT : " . $url);
			$context->getController()->redirectToUrl($url);
			return VIEW::NONE;	
		}
		catch(Exception $e)
		{
			$ms->log("BANKING CHEQUE from [".$remoteAddr." : ".$requestUri."] FAILED : " . $e->getMessage());
			Framework::exception($e);
		}
		$currentWebsite = website_WebsiteModuleService::getInstance()->getCurrentWebsite();
		$context->getController()->redirectToUrl($currentWebsite->getUrlForLang(RequestContext::getInstance()->getLang()));
		return VIEW::NONE;
	}

	/**
	 * @param array $sessionInfo
	 * @return payment_persistentdocument_chequeconnector 
	 */
	private function getChequeConnector($sessionInfo)
	{
		return DocumentHelper::getDocumentInstance ($sessionInfo ['connectorId'], 'modules_payment/chequeconnector');
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