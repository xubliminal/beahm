<?php

require_once(OP_MOD . 'email/ProviderInterface.php');

/**
 * 1 Shopping Cart email integration
 * @author Luka Peharda <luka.peharda@gmail.com>
 */
class OptimizePress_Modules_Email_Provider_OneShoppingCart implements OptimizePress_Modules_Email_ProviderInterface
{
	const OPTION_NAME_ENABLED = 'oneshoppingcart_enabled';

	/**
	 * @var bool
	 */
	protected $enabled;

	/**
	 * Constructor, initializes $accessToken, $organizerKey and creates $client
	 */
	public function __construct()
	{
		/*
		 * Fetching values from wp_options table
		 */
		$this->enabled 	= (bool) op_get_option(self::OPTION_NAME_ENABLED);
	}

	/**
	 * @see OptimizePress_Modules_Email_ProviderInterface::isEnabled()
	 */
	public function isEnabled()
	{
		return $this->enabled;
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
		if (false === op_get('disconnect')) {	
			op_update_option(self::OPTION_NAME_ENABLED, true);

			header("HTTP/1.1 200 OK");
    		header('Location: ' . admin_url() . 'admin.php?page=optimizepress#email_marketing_services--oneshoppingcart');
			exit();
		} else if ('1' == op_get('disconnect')) {
    		op_delete_option(self::OPTION_NAME_ENABLED);

    		header("HTTP/1.1 200 OK");
    		header('Location: ' . admin_url() . 'admin.php?page=optimizepress#email_marketing_services--oneshoppingcart');
    		exit();
		}
	}

	/**
	 * @see OptimizePress_Modules_Email_ProviderInterface::subscribe()
	 */
	public function subscribe($data)
	{
		return;
	}

	/**
	 * @see OptimizePress_Modules_Email_ProviderInterface::register()
	 */
	public function register($list, $email, $fname, $lname)
	{
		return;
	}

	/**
	 * @see OptimizePress_Modules_Email_ProviderInterface::subscribe()
	 */
	public function getClient()
	{
		return;
	}

	/**
	 * @see OptimizePress_Modules_Email_ProviderInterface::getLists()
	 */
	public function getLists()
	{
		return;
	}

	/**
	 * @see OptimizePress_Modules_Email_ProviderInterface::getData()
	 */
	public function getData()
	{
		return;
	}

	/**
	 * @see OptimizePress_Modules_Email_ProviderInterface::getListFields()
	 */
	public function getListFields($listId)
	{
		return array('fields' => array());
	}

	/**
	 * @see OptimizePress_Modules_Email_ProviderInterface::getItems()
	 */
	public function getItems()
	{
		return $this->getData();
	}
}