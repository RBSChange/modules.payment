<?php
/**
 * payment_persistentdocument_chequeconnector
 * @package payment.persistentdocument
 */
class payment_persistentdocument_chequeconnector extends payment_persistentdocument_chequeconnectorbase
{
	public function getTemplateViewName()
	{
		return 'Cheque';
	}
}