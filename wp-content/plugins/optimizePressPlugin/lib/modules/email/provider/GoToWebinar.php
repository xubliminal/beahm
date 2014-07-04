<?php

require_once(OP_MOD . 'email/ProviderInterface.php');
require_once(OP_LIB . 'vendor/gotowebinar/CitrixAPI.php');

/**
 * GoToWebinar email integration
 * @author Luka Peharda <luka.peharda@gmail.com>
 */
class OptimizePress_Modules_Email_Provider_GoToWebinar implements OptimizePress_Modules_Email_ProviderInterface
{
	const OPTION_NAME_OAUTH_ACCESS_TOKEN 	= 'gotowebinar_access_token';
	const OPTION_NAME_OAUTH_ORGANIZER_KEY	= 'gotowebinar_organizer_key';
	const OPTION_NAME_OAUTH_API_KEY 		= 'gotowebinar_api_key';
	const OPTION_NAME_OAUTH_EXPIRES_IN 		= 'gotowebinar_expires_in';

	/**
	 * @var OP_CitrixAPI
	 */
	protected $client;

	/**
	 * @var AWeberCollection
	 */
	protected $account;

	/**
	 * @var string|bool
	 */
	protected $accessToken;

	/**
	 * @var string|bool
	 */
	protected $organizerKey;

	/**
	 * @var string|bool
	 */
	protected $apiKey;

	/**
	 * Constructor, initializes $accessToken, $organizerKey and creates $client
	 */
	public function __construct()
	{
		/*
		 * Fetching values from wp_options table
		 */
		$this->accessToken 	= op_get_option(self::OPTION_NAME_OAUTH_ACCESS_TOKEN);
		$this->organizerKey = op_get_option(self::OPTION_NAME_OAUTH_ORGANIZER_KEY);
		$this->apiKey 		= op_get_option(self::OPTION_NAME_OAUTH_API_KEY);
	}

	/**
	 * @see OptimizePress_Modules_Email_ProviderInterface::subscribe()
	 */
	public function getClient()
	{
		if (null === $this->client) {
			$this->client = new OP_CitrixAPI($this->accessToken, $this->organizerKey);
		}

		return $this->client;
	}

	/**
	 * @see OptimizePress_Modules_Email_ProviderInterface::isEnabled()
	 */
	public function isEnabled()
	{
		return $this->accessToken === false || $this->organizerKey === false ? false : true;
	}

	/**
	 * Authorizes user on GoToWebinar using OAuth
	 *
	 * User will be redirected to GoToWebinar website for authorization
	 *
	 * @return void
	 */
	public function authorize()
	{
		/*
		 * If 'callback' is defined we are returned from GoToWebinar with auth details
		 */
		if (false === op_get('authorize') && false === op_get('disconnect') && false === op_get('clean')) {
			/*
			 * Defining callback URL where GoToWebinar will return with auth information
			 */
			$callbackUrl = admin_url('admin.php?action=' . OP_GOTOWEBINAR_AUTH_URL);

			$response = $this->getClient()->getOAuthToken($this->apiKey, $callbackUrl);
			
			if (is_string($response)) {
				$data = json_decode($response);
				/*
				 * Saving access token
				 */
				op_update_option(self::OPTION_NAME_OAUTH_ACCESS_TOKEN, $data->access_token);
				op_update_option(self::OPTION_NAME_OAUTH_ORGANIZER_KEY, $data->organizer_key);
				op_update_option(self::OPTION_NAME_OAUTH_EXPIRES_IN, time() + (int) $data->expires_in);
			}			

			/*
			 * Redirecting to GoToWebinar login/authorization dialog
			 */
			header("HTTP/1.1 200 OK");
			header('Location: ' . admin_url() . 'admin.php?page=optimizepress#email_marketing_services--gotowebinar');
			exit();
		} else if ('1' == op_get('disconnect')) {
			/*
    		 * Saving access data
    		 */
    		op_delete_option(self::OPTION_NAME_OAUTH_ACCESS_TOKEN);
    		op_delete_option(self::OPTION_NAME_OAUTH_ORGANIZER_KEY);
    		op_delete_option(self::OPTION_NAME_OAUTH_EXPIRES_IN);
    		op_delete_option(self::OPTION_NAME_OAUTH_API_KEY);

    		/*
    		 * Redirecting user to dashboard page
    		 */
    		header("HTTP/1.1 200 OK");
    		header('Location: ' . admin_url() . 'admin.php?page=optimizepress#email_marketing_services--gotowebinar');
    		exit();
    	} else if ('1' == op_get('clean')) {
			op_delete_option(self::OPTION_NAME_OAUTH_API_KEY);  

			/*
    		 * Redirecting user to dashboard page
    		 */
    		header("HTTP/1.1 200 OK");
    		header('Location: ' . admin_url() . 'admin.php?page=optimizepress#email_marketing_services--gotowebinar');
    		exit();
		} else {
			$callbackUrl = admin_url('admin.php?action=' . OP_GOTOWEBINAR_AUTH_URL);
			$this->getClient()->getOAuthToken($this->apiKey, $callbackUrl);
		}
	}

	/**
	 * @see OptimizePress_Modules_Email_ProviderInterface::subscribe()
	 */
	public function subscribe($data)
	{
		if (isset($data['list']) && isset($data['email'])) {

			$webinarKey = substr($data['list'], 1);
			unset($data['list']);

			try {
				$this->getClient()->createRegistrant($webinarKey, $data);
			} catch (Exception $e) {
				error_log('Exception while doing optin: ' . print_r($e->getMessage(), true));
			}

			return true;
		} else {
			wp_die('Mandatory information not present [webinar and/or email address].');
		}
	}

	/**
	 * @see OptimizePress_Modules_Email_ProviderInterface::register()
	 */
	public function register($list, $email, $fname, $lname)
	{
		return;
	}

	/**
	 * Traverses through the list collection and returns needed list
	 * @param string $id
	 * @return AWeberEntry|null
	 */
	protected function getListById($id)
	{
		$lists = $this->getLists();
		foreach ($lists as $list) {
			if ($list->id == $id) {
				return $list;
			}
		}

		return null;
	}

	/**
	 * Searches for possible form fields from POST and adds them to the collection
	 * @param  AWeberEntry $list
	 * @return null|array     Null if no value/field found
	 */
	protected function prepareMergeVars($list)
	{
		$vars = array();
		$allowed = array_keys($this->getFormFields($list));

		foreach ($allowed as $name) {
			if (false !== $value = op_post(str_replace(' ', '_', $name))) {
				$vars[$name] = $value;
			}
		}

		if (count($vars) === 0) {
			$vars = null;
		}

		return $vars;
	}

		/**
	 * @see OptimizePress_Modules_Email_ProviderInterface::getLists()
	 */
	public function getLists()
	{
		return $this->getClient()->getUpcomingWebinars();
	}

	/**
	 * @see OptimizePress_Modules_Email_ProviderInterface::getData()
	 */
	public function getData()
	{
		$data = array(
			'lists' => array()
		);

		/*
		 * List parsing
		 */
		$lists = $this->getLists();
		if (is_string($lists)) {
			/*
			 * preg_replace is here to add slashes around integers and floats
			 * 
			 * json_decode decoded big integers as floats and this caused issues on some hosts
			 */
			$lists = json_decode(preg_replace('/("\w+"):(\d+(\.\d+)?)/', '\\1:"\\2"', $lists));
			if (count($lists) > 0) {
				foreach ($lists as $list) {
					$data['lists']['a' . $list->webinarKey] = array('name' => $list->subject, 'fields' => $this->getFormFields());
				}
			}	
		}

		return $data;
	}

	/**
	 * Returns form fields for given list
	 * @return array
	 */
	public function getFormFields()
	{
		$fields = array('firstName' => 'firstName', 'lastName' => 'lastName');
		return $fields;
	}

	/**
	 * @see OptimizePress_Modules_Email_ProviderInterface::getListFields()
	 */
	public function getListFields($listId)
	{
		return array('fields' => $this->getFormFields());
	}

	/**
	 * @see OptimizePress_Modules_Email_ProviderInterface::getItems()
	 */
	public function getItems()
	{
		return $this->getData();
	}
}