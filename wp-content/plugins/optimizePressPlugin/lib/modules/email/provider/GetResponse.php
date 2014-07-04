<?php

require_once(OP_MOD . 'email/ProviderInterface.php');
require_once(OP_LIB . 'vendor/getresponse/GetResponseAPI.class.php');

/**
 * GetResponse email integration provider
 * @author Luka Peharda <luka.peharda@gmail.com>
 */
class OptimizePress_Modules_Email_Provider_GetResponse implements OptimizePress_Modules_Email_ProviderInterface
{
	const OPTION_NAME_API_KEY = 'getresponse_api_key';
	const OPTION_NAME_API_URL = 'getresponse_api_url';

	/**
	 * @var OP_GetResponse
	 */
	protected $client = null;

	/**
	 * @var boolean
	 */
	protected $apiKey = false;

	/**
	 * Initializes client object and fetches API KEY
	 */
	public function __construct()
	{
		/*
		 * Fetching API key from the wp_options table
		 */
		$this->apiKey = op_get_option(self::OPTION_NAME_API_KEY);	
		$this->apiUrl = op_get_option(self::OPTION_NAME_API_URL);	
	}

	/**
	 * @see OptimizePress_Modules_Email_ProviderInterface::subscribe()
	 */
	public function getClient()
	{
		if (null === $this->client) {
			$this->client = new OP_GetResponse($this->apiKey, $this->apiUrl);
		}

		return $this->client;
	}

	/**
	 * @see OptimizePress_Modules_Email_ProviderInterface::getLists()
	 */
	public function getLists()
	{
		return $this->getClient()->getCampaigns();
	}

	/**
	 * @see OptimizePress_Modules_Email_ProviderInterface::getData()
	 */
	public function getData()
	{
		$data = array(
			'lists' => array()
		);

		$params = $this->getCustomFields();

		/*
		 * List parsing
		 */
		$lists = $this->getLists();
		if ($lists) {
			foreach ($lists as $id => $list) {
				$data['lists'][$id] = array('name' => $list->name, 'fields' => $params);
			}
		}

		return $data;
	}

	/**
	 * @see OptimizePress_Modules_Email_ProviderInterface::getItems()
	 */
	public function getItems()
	{
		return $this->getData();
	}

	/**
	 * @see OptimizePress_Modules_Email_ProviderInterface::subscribe()
	 */
	public function subscribe($data)
	{	
		if (isset($data['list']) && isset($data['email'])) {

			$params = $this->prepareMergeVars();

			try {
				$this->getClient()->addContact($data['list'], op_post('name') !== false ? op_post('name') : 'Friend', $data['email'], 'standard', 0, $params);
			} catch (Exception $e) {
				error_log('Exception while doing optin: ' . print_r($e->getMessage(), true));
			}
			
			return true;
		} else {
			wp_die('Mandatory information not present [list and/or email address ].');
		}	
	}

	/**
	 * @see OptimizePress_Modules_Email_ProviderInterface::register()
	 */
	public function register($list, $email, $fname, $lname)
	{
		try {
			$this->getClient()->addContact($list, $fname . ' ' . $lname, $email, 'standard', 0, null);
		} catch (Exception $e) {
			error_log('Exception while doing optin: ' . print_r($e->getMessage(), true));
		}

		return true;
	}

	/**
	 * Searches for possible form fields from POST and adds them to the collection
	 * @return null|array     Null if no value/field found
	 */
	protected function prepareMergeVars()
	{
		$vars = array();
		$allowed = array_keys($this->getCustomFields());

		foreach ($allowed as $name) {
			if ($name !== 'name' && op_post($name) !== false) {
				$vars[$name] = op_post($name);
			}
		}

		if (count($vars) === 0) {
			$vars = null;
		}

		return $vars;
	}

	/**
	 * @see OptimizePress_Modules_Email_ProviderInterface::isEnabled()
	 */
	public function isEnabled()
	{
		return $this->apiKey === false ? false : true;
	}

	/**
	 * Returns form fields for given list
	 * @return array
	 */
	public function getCustomFields()
	{
		$fields = array('name' => __('Name', OP_SN));

		$vars = $this->getClient()->getAccountCustoms();

		foreach ($vars as $var) {
			$fields[$var->name] = $var->name;
		}

		return $fields;
	}

	/**
	 * @see OptimizePress_Modules_Email_ProviderInterface::getListFields()
	 */
	public function getListFields($listId)
	{
		return array('fields' => $this->getCustomFields());
	}
}