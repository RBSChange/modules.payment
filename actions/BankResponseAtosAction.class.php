<?php
class payment_BankResponseAtosAction extends f_action_BaseAction
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
		$ms->log("BANKING ATOS from [".$remoteAddr." : ".$requestUri."] BEGIN");	
       
		try
		{
			$this->getTransactionManager()->beginTransaction();
		
			$connectorService = payment_AtosconnectorService::getInstance();
			$sessionInfo = $connectorService->getSessionInfo();
			if (count($sessionInfo) == 0)
			{
				throw new Exception('Session expired');
			}
			$bankResponse = $connectorService->getBankResponse($request->getParameter('DATA'));
            RequestContext::getInstance()->setLang($bankResponse->getLang());
				
			$order = $bankResponse->getOrder();

			//En production le listener ce charge de compléter la commande
			if (Framework::inDevelopmentMode())
			{
				$connectorService->setPaymentResult($bankResponse, $order);
			}
            elseif ($order->getPaymentStatus() === 'initiated')
            {
             	
                if ($bankResponse->isFailed())
                {
                	$ms->log("BANKING ATOS from [".$remoteAddr." : ".$requestUri."] UPDATE STATUS: FAILED");
                    $connectorService->setPaymentResult($bankResponse, $order);
                }  
				else
				{
					$ms->log("BANKING ATOS from [".$remoteAddr." : ".$requestUri."] UPDATE STATUS: WAITING");
					$order->setPaymentStatus('waiting');
				}
            }
            
			$connectorService->setSessionInfo(array());				
			$url = $sessionInfo['paymentURL'];
			$ms->log("BANKING ATOS from [".$remoteAddr." : ".$requestUri."] END AND REDIRECT : " . $url);
			$this->getTransactionManager()->commit();
		}
		catch(Exception $e)
		{
			$this->getTransactionManager()->rollBack($e);
			
			$ms->log("BANKING ATOS from [".$remoteAddr." : ".$requestUri."] EXCEPTION : " . $e->getMessage());
			
			if (count($sessionInfo) && isset($sessionInfo['orderId']) && isset($sessionInfo['paymentURL']))
			{
				$connectorService->setSessionInfo(array());
				$ms->log("BANKING ATOS from [".$remoteAddr." : ".$requestUri."] REDIRECT FROM SESSION INFO");
				$bill = order_persistentdocument_bill::getInstanceById($sessionInfo['orderId']);
				if ($bill->getPaymentStatus()  === 'initiated')
				{
					$ms->log("BANKING ATOS from [".$remoteAddr." : ".$requestUri."] UPDATE STATUS: FAILED");			
					$bill->setTransactionId('ERROR-BAD-RESPONSE');
					$bill->setPaymentStatus('failed');
				}
				$url = $sessionInfo['paymentURL'];
				$context->getController()->redirectToUrl($url);
				return VIEW::NONE;
			}			
			
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