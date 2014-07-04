<?php

require_once(OP_MOD . 'email/ProviderInterface.php');
require_once(OP_LIB . 'vendor/aweber_api/aweber_api.php');

/**
 * AWeber email integration provider
 * @author Luka Peharda <luka.peharda@gmail.com>
 */
class OptimizePress_Modules_Email_Provider_Aweber implements OptimizePress_Modules_Email_ProviderInterface
{
	/*
	 * Application needs to be created/registered at labs.aweber.com to receive this info
	 */
	const APP_ID = '175a01ab';
	const CONSUMER_KEY = 'AkGX4qiLDofJcXcmz00fN10D';
	const CONSUMER_SECRET = 'yEaZuqxLlLXbWltDMuEgRW4mKU7L3d5x3AdrhVFl';

	const OPTION_NAME_OAUTH_ACCESS_TOKEN = 'aweber_access_token';
	const OPTION_NAME_OAUTH_ACCESS_SECRET = 'aweber_access_secret';

	/**
	 * @var OP_AWeberAPI
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
	protected $accessSecret;

	/**
	 * Constructor, initializes $accessToken, $accessSecret and creates $client
	 */
	public function __construct()
	{
		/*
		 * Fetching values from wp_options table
		 */
		$this->accessToken = op_get_option(self::OPTION_NAME_OAUTH_ACCESS_TOKEN);
		$this->accessSecret = op_get_option(self::OPTION_NAME_OAUTH_ACCESS_SECRET);		
	}

	/**
	 * @see OptimizePress_Modules_Email_ProviderInterface::subscribe()
	 */
	public function getClient()
	{
		if (null === $this->client) {
			$this->client = new OP_AWeberAPI(self::CONSUMER_KEY, self::CONSUMER_SECRET);
		}

		return $this->client;
	}

	/**
	 * Initializes AWeber account
	 * @return OP_AWeberCollection
	 */
	public function getAccount()
	{
		if (null === $this->account) {
			$this->account = $this->getClient()->getAccount($this->accessToken, $this->accessSecret);
		}	

		return $this->account;
	}

	/**
	 * @see OptimizePress_Modules_Email_ProviderInterface::isEnabled()
	 */
	public function isEnabled()
	{
		return $this->accessToken === false || $this->accessSecret === false ? false : true;
	}

	/**
	 * Authorizes user on AWeber using OAuth
	 *
	 * User will be redirected to AWeber website for authorization
	 *
	 * @return void
	 */
	public function authorize()
	{
		/*
		 * If 'callback' is defined we are returned from AWeber with auth details
		 */
		if (false === op_get('callback') && false === op_get('disconnect')) {
			/*
			 * Defining callback URL where AWeber will return with auth information
			 */
			$callbackUrl = admin_url('admin.php?action=' . OP_AWEBER_AUTH_URL . '&callback=1');

			/*
			 * Fetching request token from AWeber
			 */
			list($requestToken, $requestTokenSecret) = $this->getClient()->getRequestToken($callbackUrl);

			/*
			 * Saving temp request token secret
			 */
			op_update_option(self::OPTION_NAME_OAUTH_ACCESS_SECRET, $requestTokenSecret);

			/*
			 * Redirecting to AWeber login/authorization dialog
			 */
			header("HTTP/1.1 200 OK");
			header('Location: ' . $this->getClient()->getAuthorizeUrl());
			exit();
		} else if ('1' == op_get('disconnect')) {
			/*
    		 * Saving access data
    		 */
    		op_delete_option(self::OPTION_NAME_OAUTH_ACCESS_TOKEN);
    		op_delete_option(self::OPTION_NAME_OAUTH_ACCESS_SECRET);
    		/*
    		 * Redirecting user to dashboard page
    		 */
    		header("HTTP/1.1 200 OK");
    		header('Location: ' . admin_url() . 'admin.php?page=optimizepress#email_marketing_services--aweber');
    		exit();

		} else {
			/*
			 * Filling AWeber user with needed information (from GET and from data received in earlier step)
			 */
			$this->getClient()->user->tokenSecret = op_get_option(self::OPTION_NAME_OAUTH_ACCESS_SECRET);
    		$this->getClient()->user->requestToken = op_get('oauth_token');
    		$this->getClient()->user->verifier = op_get('oauth_verifier');

    		/*
    		 * Fetching access token
    		 */
    		list($accessToken, $accessTokenSecret) = $this->getClient()->getAccessToken();

    		/*
    		 * Saving access data
    		 */
    		op_update_option(self::OPTION_NAME_OAUTH_ACCESS_TOKEN, $accessToken);
    		op_update_option(self::OPTION_NAME_OAUTH_ACCESS_SECRET, $accessTokenSecret);
    		
    		/*
    		 * Redirecting user to dashboard page
    		 */
    		header("HTTP/1.1 200 OK");
    		header('Location: ' . admin_url() . 'admin.php?page=optimizepress#email_marketing_services--aweber');
    		exit();
		}
	}

	/**
	 * @see OptimizePress_Modules_Email_ProviderInterface::subscribe()
	 */
	public function subscribe($data)
	{
		if (isset($data['list']) && isset($data['email'])) {

			$list = $this->getListById($data['list']);

			$params = array(
				'email' => $data['email'],
				'name' => false === op_post('name') ? '' : op_post('name')
			);

			if (null === $list) {
				wp_die('Error when subscribing user: ' . print_r($params, true) . "\n" . 'List not found [' . $data['list'] . ']');
			}

			$mergeVars = $this->prepareMergeVars($list);
			if (null !== $mergeVars) {
				$params['custom_fields'] = $mergeVars;
			}

			try {
				$status = $list->subscribers->create($params);
			} catch (OP_AWeberAPIException $e) {
				error_log('Error when subscribing user: ' . print_r($params, true) . "\n" . $e->type . ' - ' . $e->message);
				if (trim($e->message) == 'email: Subscriber already subscribed.') {
					if (isset($_POST['redirect_url'])) {
						$action = sprintf(__('<a href="javascript:history.go(-1);">Return to previous page</a> or <a href="%s">continue</a>.', OP_SN), op_post('redirect_url'));	
					} else {
						$action = __('<a href="javascript:history.go(-1);">Return to previous page.</a>', OP_SN);
					}
					op_warning_screen(
						__('This email is already subscribed...', OP_SN),
						__('Optin form - Warning', OP_SN),
						$action
					);
				}
			}

			return true;
		} else {
			wp_die('Mandatory information not present [list and/or email address].');
		}
	}

	/**
	 * @see OptimizePress_Modules_Email_ProviderInterface::register()
	 */
	public function register($list, $email, $fname, $lname)
	{
		$list = $this->getListById($list);

		try {

			$status = $list->subscribers->create(array(
				'email' => $email,
				'name' => $fname . ' ' . $lname
			));

		} catch (OP_AWeberAPIException $e) {
			error_log('Error when subscribing user: ' . $e->type . ' - ' . $e->message);
		}

		return true;
	}

	/**
	 * Traverses through the list collection and returns needed list
	 * @param string $id
	 * @return OP_AWeberEntry|null
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
	 * @param  OP_AWeberEntry $list
	 * @return null|array     Null if no value/field found
	 */
	protected function prepareMergeVars($list)
	{
		$vars = array();
		$allowed = array_keys($this->getFormFields($list));

		foreach ($allowed as $name) {
			if ($name !== 'name' && false !== $value = op_post(str_replace(' ', '_', $name))) {
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
		return $this->getAccount()->lists;
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
		if (count($lists) > 0) {
			foreach ($lists as $list) {
				$data['lists'][$list->id] = array('name' => $list->name, 'fields' => $this->getFormFields($list));
			}
		}

		return $data;
	}

	/**
	 * @see OptimizePress_Modules_Email_ProviderInterface::getItems()
	 */
	public function getItems()
	{
		$data = array(
			'lists' => array()
		);

		/*
		 * List parsing
		 */
		$lists = $this->getLists();
		if (count($lists) > 0) {
			foreach ($lists as $list) {
				$data['lists'][$list->id] = array('name' => $list->name);
			}
		}

		return $data;
	}

	/**
	 * @see OptimizePress_Modules_Email_ProviderInterface::getListFields()
	 */
	public function getListFields($listId)
	{
		$list = $this->getLists()->getById($listId);
		return array('fields' => $this->getFormFields($list));
	}

	/**
	 * Returns form fields for given list
	 * @param  OP_AWeberEntry $list
	 * @return array
	 */
	public function getFormFields($list)
	{
		$fields = array('name' => 'name');

		$vars = $list->custom_fields;
		if (count($vars) > 0) {
			foreach ($vars as $var) {
				$fields[esc_attr($var->name)] = $var->name;
			}
		}

		return $fields;
	}
}