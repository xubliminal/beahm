<?php

/**
 * Factory for creating provider object
 * @author Luka Peharda <luka.peharda@gmail.com>
 */
class OptimizePress_Modules_Email_ProviderFactory
{
	private function __construct()
	{}

	public static function getFactory($type, $cache = false)
	{
		switch ($type) {
			case 'mailchimp':
				require_once(OP_MOD . 'email/provider/Mailchimp.php');
				$provider = new OptimizePress_Modules_Email_Provider_Mailchimp();
				break;
			case 'aweber':
				require_once(OP_MOD . 'email/provider/Aweber.php');
				$provider = new OptimizePress_Modules_Email_Provider_Aweber();
				break;
			case 'infusionsoft':
				require_once(OP_MOD . 'email/provider/Infusionsoft.php');
				$provider = new OptimizePress_Modules_Email_Provider_Infusionsoft();
				break;
			case 'icontact':
				require_once(OP_MOD . 'email/provider/Icontact.php');
				$provider = new OptimizePress_Modules_Email_Provider_Icontact();
				break;
			case 'getresponse':
				require_once(OP_MOD . 'email/provider/GetResponse.php');
				$provider = new OptimizePress_Modules_Email_Provider_GetResponse();
				break;
			case 'gotowebinar':
				require_once(OP_MOD . 'email/provider/GoToWebinar.php');
				$provider = new OptimizePress_Modules_Email_Provider_GoToWebinar();
				break;
			case 'oneshoppingcart':
				require_once(OP_MOD . 'email/provider/OneShoppingCart.php');
				$provider = new OptimizePress_Modules_Email_Provider_OneShoppingCart();
				break;
			case 'campaignmonitor':
				require_once(OP_MOD . 'email/provider/CampaignMonitor.php');
				$provider = new OptimizePress_Modules_Email_Provider_CampaignMonitor();
				break;
			case 'officeautopilot':
				require_once(OP_MOD . 'email/provider/OfficeAutopilot.php');
				$provider = new OptimizePress_Modules_Email_Provider_OfficeAutopilot();
				break;
			default:
				return null;
		}

		require_once(OP_MOD . 'email/provider/TransientCache.php');
		$provider = new OptimizePress_Modules_Email_Provider_TransientCache($provider);
		
		return $provider;
	}
}