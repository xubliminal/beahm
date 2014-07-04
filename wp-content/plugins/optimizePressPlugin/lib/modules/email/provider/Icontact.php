<?php

require_once(OP_MOD . 'email/ProviderInterface.php');
require_once(OP_LIB . 'vendor/icontact/iContactApi.php');

/**
 * iContact email integration provider
 * @author Luka Peharda <luka.peharda@gmail.com>
 */
class OptimizePress_Modules_Email_Provider_Icontact implements OptimizePress_Modules_Email_ProviderInterface
{
	const OPTION_NAME_USERNAME = 'icontact_username';
	const OPTION_NAME_PASSWORD = 'icontact_password';

	/**
	 * @var OP_iContactApi
	 */
	protected $client;

	/**
	 * @var string|bool
	 */
	protected $username;

	/**
	 * @var string|bool
	 */
	protected $password;

	/**
	 * Constructor, initializes $username and $password
	 */
	public function __construct()
	{
		/*
		 * Fetching values from wp_options table
		 */
		$this->username = op_get_option(self::OPTION_NAME_USERNAME);
		$this->password = op_get_option(self::OPTION_NAME_PASSWORD);		
	}

	/**
	 * Returns iContact API client
	 * @return iClientApi
	 */
	public function getClient()
	{
		if (null === $this->client) {
			$this->client = OP_iContactApi::getInstance()->setConfig(array(
				'appId' => OP_ICONTACT_APP_ID,
				'apiUsername' => $this->username,
				'apiPassword' => $this->password
			));
		}

		return $this->client;
	}

	/**
	 * @see OptimizePress_Modules_Email_ProviderInterface::isEnabled()
	 */
	public function isEnabled()
	{
		return $this->username === false || $this->password === false ? false : true;
	}

	/**
	 * @see OptimizePress_Modules_Email_ProviderInterface::getLists()
	 */
	public function getLists()
	{
		return $this->getClient()->getLists();
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
			$extras = $this->getExtraFields();
			foreach ($lists as $list) {
				$data['lists'][$list->listId] = array('name' => $list->name, 'fields' => $extras);
			}
		}

		return $data;
	}

	/**
	 * Returns hardcoded extra fields that iContact supports
	 * @return array
	 */
	protected function getExtraFields()
	{
		return array(
			'prefix' => 'Prefix',
			'name' => 'FirstName',
			'last_name' => 'LastName',
			'sufix' => 'Suffix',
			'street' => 'Street',
			'street2' => 'Street2',
			'city' => 'City',
			'state' => 'State',
			'postal_code' => 'PostalCode',
			'phone' => 'Phone',
			'fax' => 'Fax',
			'business' => 'Business'
		);
	}

	/**
	 * Searches for possible form fields from POST and adds them to the collection
	 * @return null|array     Null if no value/field found
	 */
	protected function prepareMergeVars()
	{
		$vars = array();
		$allowed = array_keys($this->getExtraFields());

		foreach ($allowed as $name) {
			$vars[$name] = op_post($name);
		}

		if (count($vars) === 0) {
			$vars = null;
		}

		return $vars;
	}

	/**
	 * @see OptimizePress_Modules_Email_ProviderInterface::subscribe()
	 */
	public function subscribe($data)
	{	
		if (isset($data['list']) && isset($data['email'])) {

			$mergeVars = $this->prepareMergeVars();

			try {
				$contact = $this->getClient()->addContact(
					$data['email'], 'normal', $mergeVars['prefix'], $mergeVars['name'], $mergeVars['last_name'], $mergeVars['sufix'], $mergeVars['street'],
					$mergeVars['street2'], $mergeVars['city'], $mergeVars['state'], $mergeVars['postal_code'], $mergeVars['phone'], $mergeVars['fax'], $mergeVars['business']
				);	
				$this->getClient()->subscribeContactToList($contact->contactId, $data['list']);
				return true;
			} catch (Exception $e) {
				error_log('Exception while doing optin: ' . print_r($e->getMessage(), true));
				return true;
			}
		} else {
			wp_die('Mandatory information not present [list and/or email address].');
		}	
	}

	/**
	 * @see OptimizePress_Modules_Email_ProviderInterface::register()
	 */
	public function register($list, $email, $fname, $lname)
	{
		try {
			$contact = $this->getClient()->addContact(
				$email, 'normal', $mergeVars['prefix'], $fname, $lname
			);	
			$this->getClient()->subscribeContactToList($contact->contactId, $list);
			return true;
		} catch (Exception $e) {
			error_log('Exception while doing optin: ' . print_r($e->getMessage(), true));
			return true;
		}
	}

	/**
	 * @see OptimizePress_Modules_Email_ProviderInterface::getListFields()
	 */
	public function getListFields($listId)
	{
		return array('fields' => $this->getExtraFields());
	}

	/**
	 * @see OptimizePress_Modules_Email_ProviderInterface::getItems()
	 */
	public function getItems()
	{
		return $this->getData();
	}
}