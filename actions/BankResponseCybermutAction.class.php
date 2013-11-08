<?php
class payment_BankResponseCybermutAction extends f_action_BaseAction
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
		$ms->log("BANKING CYBERMUT from [".$remoteAddr." : ".$requestUri."] BEGIN");	
        
		try
		{
			$this->getTransactionManager()->beginTransaction();		
		
			$connectorService = payment_CybermutconnectorService::getInstance();
			$sessionInfo = $connectorService->getSessionInfo();
			if (count($sessionInfo) == 0)
			{
				throw new Exception('Session expired');
			}

			if(Framework::inDevelopmentMode())
			{
				$sessionInfo['status'] = 'CANCEL';
				$bankResponse = $connectorService->getCallbackResponse($sessionInfo);
				if ($bankResponse)
				{
					$order = $bankResponse->getOrder();
					$connectorService->setPaymentResult($bankResponse, $order);
				}
			}
			else
			{
				$order = DocumentHelper::getDocumentInstance($request->getParameter('orderId'));
				if(f_util_StringUtils::isEmpty($order->getPaymentStatus()))
				{
					$order->setPaymentResult('waiting');
				}
			}

			$url = $sessionInfo['paymentURL'];		
			$connectorService->setSessionInfo(array());
			$ms->log("BANKING CYBERMUT from [".$remoteAddr." : ".$requestUri."] END AND REDIRECT : " . $url);
			$this->getTransactionManager()->commit();
	
		}
		catch(Exception $e)
		{
			$ms->log("BANKING CYBERMUT from [".$remoteAddr." : ".$requestUri."] FAILED : " . $e->getMessage());
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