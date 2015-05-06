<?php

if (!defined('_PS_VERSION_'))
	exit;

class ComnpayInstall
{
	
	/**
	 * Set configuration table
	 */
	public function updateConfiguration()
	{
		Configuration::updateValue('COMNPAY_GATEWAY_CONFIG', 'HOMOLOGATION');
		Configuration::updateValue('COMNPAY_GATEWAY_HOMOLOGATION', 'https://secure-homologation.comnpay.com');
		Configuration::updateValue('COMNPAY_GATEWAY_PRODUCTION', 'https://secure.comnpay.com');
	}
	
	/**
	 * Delete Comnpay configuration
	 */
	public function deleteConfiguration()
	{
		Configuration::deleteByName('COMNPAY_GATEWAY_CONFIG');
		Configuration::deleteByName('COMNPAY_GATEWAY_HOMOLOGATION');
		Configuration::deleteByName('COMNPAY_GATEWAY_PRODUCTION');
		Configuration::deleteByName('COMNPAY_GATEWAY_TPE_NUMBER');
		Configuration::deleteByName('COMNPAY_GATEWAY_SECRET_KEY');
	}
	
	/**
	 * Create a new order state
	 */
	public function createOrderState()
	{
		$this->pendingD();
		$this->acceptedD();
		$this->pendingP3f();
		$this->acceptedP3f();
	}

	public function pendingD()
	{
		if (!Configuration::get('COMNPAY_OS_PENDING'))
				{
					$orderState = new OrderState();
					$orderState->name = array();
					foreach (Language::getLanguages() as $language)
					{
						if (strtolower($language['iso_code']) == 'fr')
							$orderState->name[$language['id_lang']] = 'En attente du paiement ComNpay';
						else
							$orderState->name[$language['id_lang']] = 'Pending payment from ComnPay';
					}
					$orderState->send_email = false;
					$orderState->color = '#ffc702';
					$orderState->hidden = false;
					$orderState->delivery = false;
					$orderState->logable = false;
					$orderState->invoice = false;

					if ($orderState->add())
					{
						$source = dirname(__FILE__).'/img/logo.gif';
						$destination = dirname(__FILE__).'/../../img/os/'.(int)$orderState->id.'.gif';
						copy($source, $destination);
					}
					Configuration::updateValue('COMNPAY_OS_PENDING', (int)$orderState->id);
				}
	}

	public function acceptedD()
	{
		if (!Configuration::get('COMNPAY_OS_ACCEPTED'))
				{
					$orderState = new OrderState();
					$orderState->name = array();
					foreach (Language::getLanguages() as $language)
					{
						if (strtolower($language['iso_code']) == 'fr')
							$orderState->name[$language['id_lang']] = 'Paiement ComNpay validé';
						else
							$orderState->name[$language['id_lang']] = 'Accepted payment from ComnPay';
					}
					$orderState->send_email = false;
					$orderState->color = '#96CA2D';
					$orderState->hidden = false;
					$orderState->delivery = false;
					$orderState->logable = false;
					$orderState->invoice = false;

					if ($orderState->add())
					{
						$source = dirname(__FILE__).'/img/logo.gif';
						$destination = dirname(__FILE__).'/../../img/os/'.(int)$orderState->id.'.gif';
						copy($source, $destination);
					}
					Configuration::updateValue('COMNPAY_OS_ACCEPTED', (int)$orderState->id);
				}
	}

	public function pendingP3f()
	{
		if (!Configuration::get('COMNPAY_OS_PENDING_P3F'))
				{
					$orderState = new OrderState();
					$orderState->name = array();
					foreach (Language::getLanguages() as $language)
					{
						if (strtolower($language['iso_code']) == 'fr')
							$orderState->name[$language['id_lang']] = 'En attente du paiement ComNpay en 3 fois';
						else
							$orderState->name[$language['id_lang']] = 'Pending payment in 3 times from ComnPay';
					}
					$orderState->send_email = false;
					$orderState->color = '#ffc702';
					$orderState->hidden = false;
					$orderState->delivery = false;
					$orderState->logable = false;
					$orderState->invoice = false;

					if ($orderState->add())
					{
						$source = dirname(__FILE__).'/img/logo.gif';
						$destination = dirname(__FILE__).'/../../img/os/'.(int)$orderState->id.'.gif';
						copy($source, $destination);
					}
					Configuration::updateValue('COMNPAY_OS_PENDING_P3F', (int)$orderState->id);
				}
	}

	public function acceptedP3f()
	{
		if (!Configuration::get('COMNPAY_OS_ACCEPTED_P3F'))
				{
					$orderState = new OrderState();
					$orderState->name = array();
					foreach (Language::getLanguages() as $language)
					{
						if (strtolower($language['iso_code']) == 'fr')
							$orderState->name[$language['id_lang']] = 'Paiement ComNpay en 3 fois validé';
						else
							$orderState->name[$language['id_lang']] = 'Payment in 3 times from ComnPay accepted';
					}
					$orderState->send_email = false;
					$orderState->color = '#96CA2D';
					$orderState->hidden = false;
					$orderState->delivery = false;
					$orderState->logable = false;
					$orderState->invoice = false;

					if ($orderState->add())
					{
						$source = dirname(__FILE__).'/img/logo.gif';
						$destination = dirname(__FILE__).'/../../img/os/'.(int)$orderState->id.'.gif';
						copy($source, $destination);
					}
					Configuration::updateValue('COMNPAY_OS_ACCEPTED_P3F', (int)$orderState->id);
				}
	}

}
