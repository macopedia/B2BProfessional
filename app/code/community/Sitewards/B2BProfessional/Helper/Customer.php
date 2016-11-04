<?php

/**
 * Sitewards_B2BProfessional_Helper_Customer
 *  - Helper containing the checks for
 *      - customer is active
 *      - if login is required
 *
 * @category    Sitewards
 * @package     Sitewards_B2BProfessional
 * @copyright   Copyright (c) 2014 Sitewards GmbH (http://www.sitewards.com/)
 */
class Sitewards_B2BProfessional_Helper_Customer extends Sitewards_B2BProfessional_Helper_Core
{
    /**
     * Path for the config for extension requires login
     */
    const CONFIG_EXTENSION_REQUIRES_LOGIN = 'b2bprofessional/requirelogin/requirelogin';

    /**
     * Path for the config for extension is active by customer group
     */
    const CONFIG_EXTENSION_ACTIVE_BY_CUSTOMER_GROUP = 'b2bprofessional/activatebycustomersettings/activebycustomer';

    /**
     * Path for the config for customer groups that are activated
     */
    const CONFIG_EXTENSION_ACTIVE_CUSTOMER_GROUPS = 'b2bprofessional/activatebycustomersettings/activecustomers';

    /**
     * Path for the config for customer groups that are activated
     */
    const CONFIG_EXTENSION_ACTIVE_CUSTOMER_GLOBALLY = 'b2bprofessional/generalsettings/activecustomers';

    /**
     * Flag if the extension is set to require login
     *
     * @var bool
     */
    protected $bLoginRequired;

    /**
     * Flag if the extension is active by customer group
     *
     * @var bool
     */
    protected $bExtensionActiveByCustomerGroup;

    /**
     * Array of activated customer group ids
     *
     * @var int[]
     */
    protected $aActivatedCustomerGroupIds = array();

    /**
     * Check to see if the customer is logged in
     *  - Is the session logged in,
     *  - Are customers activated globally,
     *  - Is this one customer active for the current store,
     *
     * @return bool
     */
    public function isCustomerLoggedIn()
    {
        if (Mage::helper('customer')->isLoggedIn() === true) {
            /*
             * If customer activation is global
             *  - then any customer can access any store
             */
            if ($this->isCustomerActivationGlobal()) {
                return true;
            }

            /* @var $oCustomerSession Mage_Customer_Model_Session */
            $oCustomerSession = Mage::getSingleton('customer/session');
            /* @var $oCustomer Mage_Customer_Model_Customer */
            $oCustomer = $oCustomerSession->getCustomer();
            if($this->isCustomerActiveForStore($oCustomer)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if the extension has customers activated globally
     *
     * @return bool
     */
    private function isCustomerActivationGlobal()
    {
        return Mage::getStoreConfigFlag(self::CONFIG_EXTENSION_ACTIVE_CUSTOMER_GLOBALLY);
    }

    /**
     * Check if a given customer is allowed for the current store
     *  - NOTE: users created via the admin section cannot be attached to a front end store and so are globally active
     *
     * @param Mage_Customer_Model_Customer $oCustomer
     * @return bool
     */
    private function isCustomerActiveForStore(Mage_Customer_Model_Customer $oCustomer)
    {
        /*
         * Check to see if the user was created via the admin section
         *  - Note: users created via the admin section cannot be attached to a front end store
         */
        if ($this->isCustomerAdminCreation($oCustomer)) {
            return true;
        }

        /*
         * Get user's store and current store for comparison
         */
        $iUserStoreId    = $oCustomer->getStoreId();
        $iCurrentStoreId = Mage::app()->getStore()->getId();

        /*
         * Return true if:
         *  - the user's store id matches the current store id
         */
        return $iUserStoreId === $iCurrentStoreId;
    }

    /**
     * Check to see if the user has been created via the admin section
     *
     * @param Mage_Customer_Model_Customer $oCustomer
     * @return bool
     */
    private function isCustomerAdminCreation(Mage_Customer_Model_Customer $oCustomer)
    {
        return (int) $oCustomer->getStoreId() === Mage_Core_Model_App::ADMIN_STORE_ID;
    }

    /**
     * Check to see if the customer's group is active
     *  - If they are not logged in then we do not need to check any further
     *
     * @return bool
     */
    public function isCustomerGroupActive()
    {
        /* @var $oCustomerSession Mage_Customer_Model_Session */
        $oCustomerSession        = Mage::getModel('customer/session');
        $iCurrentCustomerGroupId = $oCustomerSession->getCustomerGroupId();
        $aActiveCustomerGroupIds = $this->getActivatedCustomerGroupIds();

        return in_array($iCurrentCustomerGroupId, $aActiveCustomerGroupIds);
    }

    /**
     * Check to see if the website is set-up to require a user login to view pages
     *
     * @return bool
     */
    public function isLoginRequired()
    {
        return $this->getStoreFlag(self::CONFIG_EXTENSION_REQUIRES_LOGIN, 'bLoginRequired');
    }

    /**
     * Check to see if the extension is activated by customer group
     *
     * @return bool
     */
    public function isExtensionActivatedByCustomerGroup()
    {
        return $this->getStoreFlag(self::CONFIG_EXTENSION_ACTIVE_BY_CUSTOMER_GROUP, 'bExtensionActiveByCustomerGroup');
    }

    /**
     * Get an array of all the activated customer group ids
     *  - always include the 'NOT LOGGED IN' group
     *
     * @return int[]
     */
    private function getActivatedCustomerGroupIds()
    {
        if (empty($this->aActivatedCustomerGroupIds)) {
            /*
             * Customer group ids are saved in the config in format
             *  - "group1,group2"
             */
            $sActivatedCustomerGroups         = Mage::getStoreConfig(self::CONFIG_EXTENSION_ACTIVE_CUSTOMER_GROUPS);
            $this->aActivatedCustomerGroupIds = explode(',', $sActivatedCustomerGroups);

            /*
             * Always add the guest user group id when activated by customer group
             */
            $this->aActivatedCustomerGroupIds[] = Mage_Customer_Model_Group::NOT_LOGGED_IN_ID;
        }
        return $this->aActivatedCustomerGroupIds;
    }
}