<?php
class payment_PaypalConfirmAction extends payment_Action
{
	/**
	 * @see f_action_BaseAction::_execute()
	 *
	 * @param Context $context
	 * @param Request $request
	 */
	protected function _execute($context, $request)
	{
		$sessionInfo = payment_ConnectorService::getInstance()->getSessionInfo();
		$sessionInfo['payerId'] = $request->getParameter('PayerID');
		payment_ConnectorService::getInstance()->setSessionInfo($sessionInfo);
		$context->getController()->redirectToUrl($sessionInfo['paymentURL']);
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