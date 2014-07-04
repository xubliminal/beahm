<?php

require_once(OP_MOD . 'email/ProviderInterface.php');
require_once(OP_LIB . 'vendor/officeautopilot/OAPAPI.php');

/**
 * Office Autopilot email integration provider
 * @author Luka Peharda <luka.peharda@gmail.com>
 */
class OptimizePress_Modules_Email_Provider_OfficeAutopilot implements OptimizePress_Modules_Email_ProviderInterface
{
	const OPTION_NAME_APP_ID 	= 'officeautopilot_app_id';
	const OPTION_NAME_API_KEY 	= 'officeautopilot_api_key';

	/**
	 * @var OP_OAPAPI
	 */
	protected $client = null;

	/**
	 * @var string
	 */
	protected $appId;

	/**
	 * @var string
	 */
	protected $apiKey;

	public function __construct()
	{
		$this->appId 	= op_get_option(self::OPTION_NAME_APP_ID);
		$this->apiKey 	= op_get_option(self::OPTION_NAME_API_KEY);
	}

	public function getClient()
	{
		if (null === $this->client) {
			$this->client = new OP_OAPAPI(array('AppID' =>  $this->appId, 'Key' => $this->apiKey));
		}

		return $this->client;
	}

	public function subscribe($data)
	{
		if (isset($data['email'])) {

			$params['fields'] = $this->prepareMergeVars();
			$params['fields']['E-Mail'] = $data['email'];

			if (isset($data['list']) && !empty($data['list'])) {
				$params['sequences'] = array($data['list']);	
			}			

			try {
				$this->getClient()->add_contact($params);
			} catch (Exception $e) {
				error_log('Exception while doing optin: ' . print_r($e->getMessage(), true));
			}

			return true;
		} else {
			wp_die('Mandatory information not present [email address].');
		}		
	}

	/**
	 * @see OptimizePress_Modules_Email_ProviderInterface::register()
	 */
	public function register($list, $email, $fname, $lname)
	{
		$params['fields']['First Name'] = $fname;
		$params['fields']['Last Name'] = $lname;
		$params['fields']['E-Mail'] = $email;

		if (!empty($list)) {
			$params['sequences'] = array($list);	
		}		
		
		try {
			$this->getClient()->add_contact($params);
		} catch (Exception $e) {
			error_log('Exception while doing optin: ' . print_r($e->getMessage(), true));
		}

		return true;
	}

	public function getLists()
	{
		$lists = $this->getClient()->fetch_sequences_type();

		return $lists;
	}

	public function getData()
	{
		$data = array(
			'lists' => array()
		);

		/*
		 * List parsing
		 */
		$lists = $this->getLists();
		if (is_array($lists) && count($lists) > 0) {
			$extraFields = $this->getFields();
			foreach ($lists as $key => $name) {
				$data['lists'][$key] = array('name' => $name, 'fields' => $extraFields);
			}
		}

		return $data;
	}

	public function isEnabled()
	{
		if (false !== $this->appId && false !== $this->apiKey) {
			return true;
		} else {
			return false;
		}
	}

	protected function getFields()
	{
		return array (
			'First-Name' 	=> 'First Name',
			'Promo-Code' 	=> 'Promo Code',
			'Last-Name' 	=> 'Last Name',
			// 'E-Mail' 	=> 'E-Mail',
			'Cell-Phone' 	=> 'Cell Phone',
			'DC-Phone' 		=> 'DC Phone',
			'Office-Phone' 	=> 'Office Phone',
			'Fax' 			=> 'Fax',
			'Home-Phone' 	=> 'Home Phone',
			'Title' 		=> 'Title',
			'Company' 		=> 'Company',
			'Address' 		=> 'Address',
			'Address-2' 	=> 'Address 2',
			'Zip-Code' 		=> 'Zip Code',
			'City' 			=> 'City',
			'State' 		=> 'State'
		);
	}

	/**
	 * Searches for possible form fields from POST and adds them to the collection
	 * @return null|array     Null if no value/field found
	 */
	protected function prepareMergeVars()
	{
		$vars = array();
		$fields = $this->getFields();

		foreach ($fields as $key => $name) {
			if (false !== $value = op_post($key)) {
				$vars[$name] = $value;
			}
		}

		if (count($vars) === 0) {
			$vars = null;
		}
		return $vars;
	}

	/**
	 * @see OptimizePress_Modules_Email_ProviderInterface::getListFields()
	 */
	public function getListFields($listId)
	{
		return array('fields' => $this->getFields());
	}

	/**
	 * @see OptimizePress_Modules_Email_ProviderInterface::getItems()
	 */
	public function getItems()
	{
		return $this->getData();
	}
}