<?php
if (!defined('_PS_VERSION_'))
  exit;
 
class Comnpay extends PaymentModule
{


	public function __construct()
	{
		$this->name = 'comnpay';
		$this->tab = 'payments_gateways';
		$this->version = '1.2.0';
		$this->author = 'Afone';
		$this->need_instance = 1;
		$this->ps_versions_compliancy = array('min' => '1.5', 'max' => '1.6');
		$this->dependencies = array('blockcart');
		$this->currencies = true;
		$this->currencies_mode = 'checkbox';

		parent::__construct();

		$this->displayName = $this->l('Comnpay');
		$this->description = $this->l('Accepts payments by credit cards with comNpay.');

		$this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
	}


	public function install()
	{
		if (Shop::isFeatureActive())
			Shop::setContext(Shop::CONTEXT_ALL);

		include_once(_PS_MODULE_DIR_.'/'.$this->name.'/comnpay_install.php');
		$comnpay_install = new ComnpayInstall();
		$comnpay_install->updateConfiguration($this->version);
		$comnpay_install->createOrderState();

		return parent::install() &&
							$this->registerHook('payment') && 
							$this->registerHook('paymentReturn') && 
							$this->registerHook('displayHeader') &&
							$this->registerHook('displayBackOfficeHeader');
	}

	public function uninstall()
	{
		include_once(_PS_MODULE_DIR_.'/'.$this->name.'/comnpay_install.php');
		$comnpay_install = new ComnpayInstall();
		$comnpay_install->deleteConfiguration();

		if (!$this->unregisterHook('payment') || !$this->unregisterHook('paymentReturn') || !$this->registerHook('displayHeader') || !$this->unregisterHook('displayBackOfficeHeader') )
		{
			Logger::addLog('Comnpay module: unregisterHook failed', 4);
			return false;
		}

		if (!parent::uninstall())
		{
			Logger::addLog('Comnpay module: uninstall failed', 4);
			return false;
		}

		return true;
	}


	/**
	 * Affichage du mode de paiement Comnpay
	 */
	public function hookDisplayPayment($params)
	{
		if (!$this->isAvailable())
			return;

		$this->context->smarty->assign(
		    array(
		        'path_img' => $this->_path,
		        'p3f' => Configuration::get('COMNPAY_GATEWAY_P3F'),
		        'linkPayment' => $this->context->link->getModuleLink('comnpay', 'payment', array('typeTr'=>0)),
		        'linkPayment3f' => $this->context->link->getModuleLink('comnpay', 'payment', array('typeTr'=>1))
		    )
		);

		return $this->display(__FILE__, '/views/templates/front/payment.tpl');
	}


	/**
	 * Traitement du retour de l'utilisateur après le paiement
	 */
	public function hookDisplayPaymentReturn($params)
	{

		if (!$this->isAvailable())
			return;

		// Get informations
		$orderId = Tools::getValue('id_order');
        $order = new Order($orderId);

        if(($order->current_state == Configuration::get('COMNPAY_OS_PENDING'))||($order->current_state == Configuration::get('COMNPAY_OS_PENDING_P3F')))
            $template = 'pending.tpl';
		elseif (($order->current_state == Configuration::get('COMNPAY_OS_ACCEPTED'))||($order->current_state == Configuration::get('COMNPAY_OS_ACCEPTED_P3F')))
			$template = 'authorised.tpl';
		else
			return;

		return $this->display(__FILE__, 'views/templates/front/result/'.$template);
	}

	/**
	 * Ajout d'une CSS personnalisée
	 */
	public function hookDisplayHeader()
	{
		$this->context->controller->addCSS($this->_path.'css/comnpay.css', 'all');
	}

	/**
	 * Ajout d'une CSS personnalisée dans l'admin pour la page de configuration Comnpay
	 */
	public function hookDisplayBackOfficeHeader()
	{
		$this->context->controller->addCSS($this->_path.'css/comnpay_back.css', 'all');
		$this->context->controller->addJS($this->_path.'scripts/validateConfiguration.js');
	}

	public function isAvailable()
	{
		if (!$this->active)
			return false;

		if ( (Configuration::get('COMNPAY_GATEWAY_TPE_NUMBER') != "") &&
					(Configuration::get('COMNPAY_GATEWAY_SECRET_KEY') != "") )
			return true;

		return false;
	}


	/*
	 * Secret Validation
	 */
	public function validSec($values, $secret_key)
	{
	    if (isset($values['sec']) && $values['sec'] != "")
	    {
	        $sec = $values['sec'];
	        unset($values['sec']);
	        unset($values['cluster']);	 // Rajouté par les serveurs OVH et fausse la validation
	        unset($values['240plan']);	// Rajouté par les serveurs OVH et fausse la validation
	        return strtoupper(hash("sha512", base64_encode(implode("|",$values)."|".$secret_key))) == strtoupper($sec);
	    }
	    else
	    {
	        return false;
	    }
	}

	/**
	 * Changer Id Order State
	 */
	public function changeIdOrderState($transactionId, $stateId)
	{
		if ($transactionId=="")
			return false;

	    $orderHistory = new OrderHistory();
	    $orderHistory->id_order = $transactionId;
	    $orderHistory->changeIdOrderState($stateId, $transactionId);
	    $orderHistory->addWithemail();
	    $orderHistory->save();

	    return true;
	}

	/**
	 * Administration
	 */
	public function getContent()
	{
		if (!isset($this->_html) || empty($this->_html))
			$this->_html = '';
		

		$msg_confirmation="";
		if (Tools::isSubmit('submitComnpay'))
		{
			Configuration::updateValue('COMNPAY_GATEWAY_CONFIG', Tools::getValue('COMNPAY_GATEWAY_CONFIG'));
			Configuration::updateValue('COMNPAY_GATEWAY_HOMOLOGATION', Tools::getValue('COMNPAY_GATEWAY_HOMOLOGATION'));
			Configuration::updateValue('COMNPAY_GATEWAY_PRODUCTION', Tools::getValue('COMNPAY_GATEWAY_PRODUCTION'));
			Configuration::updateValue('COMNPAY_GATEWAY_TPE_NUMBER', Tools::getValue('COMNPAY_GATEWAY_TPE_NUMBER'));
			Configuration::updateValue('COMNPAY_GATEWAY_SECRET_KEY', Tools::getValue('COMNPAY_GATEWAY_SECRET_KEY'));
			Configuration::updateValue('COMNPAY_GATEWAY_P3F',Tools::getValue('COMNPAY_GATEWAY_P3F'));

			$msg_confirmation="
				<div class=\"alert_comnpay_admin\">
					Configuration sauvegardée
				</div>";
		}
		
		return $this->_html.'
		<div id="comnpay_configuration">
			<fieldset><legend><img src="../modules/'.$this->name.'/img/logo.gif" /> '.$this->l('Présentation').'</legend>
				<p>
					<img src="../modules/'.$this->name.'/img/comnpay.png" alt="Logo ComNpay" class="float_left" />
					<h3>'.$this->l('Accepts payments by credit cards with comNpay.', 'comnpay').'</h3>'.'
				</p>
			
			</fieldset>

			<div class="clear">&nbsp;</div>
			'.$msg_confirmation.'
			<form action="'.Tools::htmlentitiesUTF8($_SERVER['REQUEST_URI']).'" method="post">
				<fieldset><legend><img src="../img/admin/contact.gif" /> '.$this->l('Settings').'</legend>
					<div>
						<label for="tpe_number">'.$this->l('TPE Number').'</label>
						<div class="margin-form">
							<input type="text" id="tpe_number" size="30" name="COMNPAY_GATEWAY_TPE_NUMBER" value="'.Tools::safeOutput(Tools::getValue('COMNPAY_GATEWAY_TPE_NUMBER', Configuration::get('COMNPAY_GATEWAY_TPE_NUMBER'))).'" />
						</div>
						<div class="clear">&nbsp;</div>
						
						<label for="secret_key">'.$this->l('Secret Key').'</label>
						<div class="margin-form">
							<input type="text" id="secret_key" size="30" name="COMNPAY_GATEWAY_SECRET_KEY" value="'.Tools::safeOutput(Tools::getValue('COMNPAY_GATEWAY_SECRET_KEY', Configuration::get('COMNPAY_GATEWAY_SECRET_KEY'))).'" />
						</div>
						<div class="clear">&nbsp;</div>
						
						<label for="url_homologation">'.$this->l('URL homologation').'</label>
						<div class="margin-form">
							<input type="text" id="url_homologation" size="50" name="COMNPAY_GATEWAY_HOMOLOGATION" value="'.Tools::safeOutput(Tools::getValue('COMNPAY_GATEWAY_HOMOLOGATION', Configuration::get('COMNPAY_GATEWAY_HOMOLOGATION'))).'" />
						</div>
						<div class="clear">&nbsp;</div>

						<label for="url_production">'.$this->l('URL production').'</label>
						<div class="margin-form">
							<input type="text" id="url_production" size="50" name="COMNPAY_GATEWAY_PRODUCTION" value="'.Tools::safeOutput(Tools::getValue('COMNPAY_GATEWAY_PRODUCTION', Configuration::get('COMNPAY_GATEWAY_PRODUCTION'))).'" />
						</div>
						<div class="clear">&nbsp;</div>
						
						<label>'.$this->l('Plateforme').'</label>
						<div class="margin-form">
							<div class="plateforme">
								<input type="radio" id="homologation" name="COMNPAY_GATEWAY_CONFIG" value="HOMOLOGATION" class="choix_plateforme"
								'.((Tools::getValue('COMNPAY_GATEWAY_CONFIG', Configuration::get('COMNPAY_GATEWAY_CONFIG')) == "HOMOLOGATION") ? 'checked="checked"' : '').' />
								<label for="homologation" class="label_plateforme homologation">'.$this->l('Homologation').'</label>&nbsp;
							</div>
							<div class="clear">&nbsp;</div>
							<div class="plateforme">
								<input type="radio" id="production" name="COMNPAY_GATEWAY_CONFIG" value="PRODUCTION" class="choix_plateforme" 
								'.((Tools::getValue('COMNPAY_GATEWAY_CONFIG', Configuration::get('COMNPAY_GATEWAY_CONFIG')) == "PRODUCTION") ? 'checked="checked"' : '').' />
								<label for="production" class="label_plateforme production">'.$this->l('Production').'</label>
							</div>
						</div>
						<div class="clear">&nbsp;</div>

						<label for="p3f">'.$this->l('Authorize the payment in three time').'</label>
						<div class="margin-form">
						<input type="checkbox" id="p3f" name="COMNPAY_GATEWAY_P3F" '.((Tools::getValue('COMNPAY_GATEWAY_P3F', Configuration::get('COMNPAY_GATEWAY_P3F')) == "on") ? 'checked' : '').'/>
						'.$this->l("Cette option doit être activée sur votre compte Afone Paiement").'
						</div>

						<input type="submit" name="submitComnpay" id="submitComnpay" value="'.$this->l('Update settings').'" class="button" />
					</div>
				</fieldset>
			</form>
			<div class="clear">&nbsp;</div>
		</div>';
	}

}
?>