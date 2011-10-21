<?php
class payment_BankResponseChequeAction extends change_Action
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
		$ms = payment_ModuleService::getInstance();
		$ms->log("BANKING CHEQUE from [".$remoteAddr." : ".$requestUri."] BEGIN");	
			 
		try
		{
			$this->getTransactionManager()->beginTransaction();
		
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

			$this->getTransactionManager()->commit();
		}
		catch(Exception $e)
		{
			$ms->log("BANKING CHEQUE from [".$remoteAddr." : ".$requestUri."] FAILED : " . $e->getMessage());
			$this->getTransactionManager()->rollBack($e);
			$currentWebsite = website_WebsiteService::getInstance()->getCurrentWebsite();
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
		return change_Request::POST | change_Request::GET;
	}

	/**
	 * @return Boolean
	 */
	public final function isSecure()
	{
		return false;
	}
}