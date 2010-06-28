<?php
/**
 * payment_BlockPaymentAction
 * @package modules.payment.lib.blocks
 */
class payment_BlockPaymentAction extends website_BlockAction
{
	/**
	 * @see website_BlockAction::execute()
	 *
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	function execute($request, $response)
	{
		try 
		{
			$payment = $this->getPaymentDocument();
			$connector = $payment->getPaymentConnector();
			$connector->getDocumentService()->setPaymentInfo($connector, $payment);
			$request->setAttribute('connector', $connector);
			$request->setAttribute('order', $payment);
			if ($this->isInBackoffice())
			{
				$viewName = website_BlockView::BACKOFFICE;
			}
			else
			{
				$viewName = $connector->getTemplateViewName();
			}
		}
		catch (Exception $e)
		{
			Framework::exception($e);
			return website_BlockView::ERROR;
		}		
		return $viewName;
	}
	
	/**
	 * @return payment_Order
	 */
	private function getPaymentDocument()
	{
		$payment = null;
		$paymentId = $this->findLocalParameterValue('billid');
		if ($paymentId)
		{
			$payment = DocumentHelper::getDocumentInstance($paymentId);
		}
		if (!$payment instanceof payment_Order) 
		{
			throw new Exception('Invalid payment');
		}
		return $payment;
	}
}