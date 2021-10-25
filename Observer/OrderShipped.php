<?php

namespace SYNAPTECH\Smsalert\Observer;

use Magento\Framework\Event\ObserverInterface;
use \Magento\Framework\Event\Observer       as Observer;
use \Magento\Framework\View\Element\Context as Context;
use \SYNAPTECH\Smsalert\Helper\Data        as Helper;

/**
 * Customer login observer
 */
class OrderShipped implements ObserverInterface
{
    /**
     * Message manager
     *
     * @var \Magento\Framework\Message\ManagerInterface
     */
    const AJAX_PARAM_NAME = 'infscroll';
    /**
     *
     */
    const AJAX_HANDLE_NAME = 'infscroll_ajax_request';

    /**
     * Https request
     *
     * @var \Zend\Http\Request
     */
    private $request;

    /**
     * Layout Interface
     * @var \Magento\Framework\View\LayoutInterface
     */
    private $layout;

    /**
     * Cache
     * @var $_cache
     */
    private $cache;

    /**
     * Helper for SmsalertSMS Module
     * @var \SYNAPTECH\Smsalert\Helper\Data
     */
    private $helper;

    /**
     * Message Manager
     * @var $messageManager
     */
    private $messageManager;

    /**
     * Username
     * @var $username
     */
    private $username;

    /**
     * Password
     * @var $password
     */
    private $password;

    /**
     * Sender ID
     * @var $senderId
     */
    private $senderId;

    /**
     * Destination
     * @var $destination
     */
    private $destination;

    /**
     * Message
     * @var $message
     */
    private $message;

    /**
     * Whether Enabled or not
     * @var $enabled
     */
    private $enabled;

    /**
     * Channel
     * @var $channel
     */
    private $channel;

    /**
     * Whether Failover is activated or not
     * @var $failoverActivated
     */
    private $failoverActivated;

    /**
     * Constructor
     * @param Context $context
     * @param Helper $helper _helper
     */
    public function __construct(
        Context $context,
        Helper $helper
    ) {
        $this->_helper  = $helper;
        $this->_request = $context->getRequest();
        $this->_layout  = $context->getLayout();
    }

    /**
     * The execute class
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /**
         * Checking either this call is for order hold or not
         */

        //if (strpos($_SERVER['REQUEST_URI'], 'order/hold') !== false) 
		
            /**
             * Getting Module Configuration from admin panel
             */
            //Getting Username
            $this->username         = $this->_helper->getSmsalertUsername();

            //Getting Password
            $this->password         = $this->_helper->getSmsalertPassword();

            //Getting Sender ID
            $this->senderId        = $this->_helper->getCustomerId();

            //Getting Message
            $this->message          = $this->_helper->getCustomerSmsOnShipped();

            //Getting Customer Notification value
            $this->enabled          = $this->_helper->isCustomerSmsIsEnabledOnShipped();

            //Getting Channel
            $this->channel          = $this->_helper->getChannel();

            //Getting FailoverActivated
            $this->failoverActivated          = $this->_helper->isFailoverActivated();
            
            if ($this->enabled == 1) {
                /**
                 * Verification of API Account
                 */
                
                //Verification of API
            $verificationResult     = true;
                if ($verificationResult == true) {
                    //Getting Order Details
                    
					$shipment = $observer->getEvent()->getShipment();
					$order = $shipment->getOrder();
					$tracksCollection = $shipment->getTracksCollection();
                    $trackingId = '';
					$carrierName = '';
					foreach ($tracksCollection->getItems() as $track) {
						$trackingId = $track->getTrackNumber();
						$carrierName = $track->getTitle();              
					}
					$shipment_id  = $observer->getEvent()->getShipment()->getIncrementId();
					
                    $orderData = [
                        'orderId'       => $order->getIncrementId(),
						'storeName'     => $order->getStore()->getGroup()->getName(),
                        'firstname'     => $order->getBillingAddress()->getFirstName(),
                        'middlename'   =>  $order->getBillingAddress()->getMiddleName(),
                        'lastname'      => $order->getBillingAddress()->getLastName(),
                        'totalPrice'    => number_format($order->getGrandTotal(), 2),
                        'countryCode'   => $order->getOrderCurrencyCode(),
                        'protectCode'   => $order->getProtectCode(),
                        'customerDob'   => $order->getCustomerDob(),
                        'customerEmail' => $order->getCustomerEmail(),
                        'gender'        => ($order->getCustomerGender()?'Female':'Male'),
                        'trackingId'    => $trackingId,
                        'carrierName'  => $carrierName,
                    ];
					
					$this->destination  = $order->getBillingAddress()->getTelephone();
                    //Manipulating SMS
                    $this->message      = $this->_helper->manipulateSMS($this->message, $orderData);
                    $this->message = str_replace('{shipmentNumber}', $shipment_id, $this->message);
                    
                     //Sending SMS
                     $this->_helper->sendMessage(
                        $this->_helper->sanitizePhone($this->destination),
                        $this->message,
                        $this->channel,
                        $this->failoverActivated,
                    );
                    
                    //Sending SMS to Admin
                    if ($this->_helper->isAdminSmsIsEnabled()==1) {
                        $this->destination  = $this->_helper->getAdminSenderId();
                        $this->message      = $this->_helper->getAdminSmsForOrderShipped();
                        $this->message      = $this->_helper->manipulateSMS($this->message, $orderData);
						$this->message = str_replace('{shipmentNumber}', $shipment_id, $this->message);
                        
                        //Sending SMS
                        $this->_helper->sendMessage(
                            $this->_helper->sanitizePhone($this->destination),
                            $this->message,
                            $this->channel,
                            $this->failoverActivated,
                        );
                    }
                }
            }
    }
}
