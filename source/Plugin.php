<?php

require_once WPAM_BASE_DIRECTORY . "/source/Util/BinConverter.php";
require_once WPAM_BASE_DIRECTORY . "/source/Util/EmailHandler.php";
require_once WPAM_BASE_DIRECTORY . "/source/Util/UserHandler.php";
require_once WPAM_BASE_DIRECTORY . "/source/Util/AffiliateFormHelper.php";
require_once WPAM_BASE_DIRECTORY . "/source/Data/DataAccess.php";
require_once WPAM_BASE_DIRECTORY . "/source/Data/DatabaseInstaller.php";
require_once WPAM_BASE_DIRECTORY . "/source/PostHelper.php";
require_once WPAM_BASE_DIRECTORY . "/source/Pages/Admin/AdminPage.php";
require_once WPAM_BASE_DIRECTORY . "/source/Pages/Admin/MyCreativesPage.php";
require_once WPAM_BASE_DIRECTORY . "/source/Pages/Admin/MyAffiliatesPage.php";
require_once WPAM_BASE_DIRECTORY . "/source/Pages/Admin/NewAffiliatePage.php";
require_once WPAM_BASE_DIRECTORY . "/source/Pages/Admin/SettingsPage.php";
require_once WPAM_BASE_DIRECTORY . "/source/Pages/Admin/PaypalPaymentsPage.php";
require_once WPAM_BASE_DIRECTORY . "/source/Options.php";
require_once WPAM_BASE_DIRECTORY . "/source/Pages/TemplateResponse.php";
require_once WPAM_BASE_DIRECTORY . "/source/Pages/PublicPage.php";
require_once WPAM_BASE_DIRECTORY . "/source/Pages/AffiliatesHome.php";
require_once WPAM_BASE_DIRECTORY . "/source/Pages/AffiliatesRegister.php";
require_once WPAM_BASE_DIRECTORY . "/source/Pages/AffiliatesLogin.php";
require_once WPAM_BASE_DIRECTORY . "/source/OutputCleaner.php";
require_once WPAM_BASE_DIRECTORY . "/source/Validation/Validator.php";
require_once WPAM_BASE_DIRECTORY . "/source/Validation/StringValidator.php";
require_once WPAM_BASE_DIRECTORY . "/source/Validation/CountryCodeValidator.php";
require_once WPAM_BASE_DIRECTORY . "/source/Validation/StateCodeValidator.php";
require_once WPAM_BASE_DIRECTORY . "/source/Validation/SetValidator.php";
require_once WPAM_BASE_DIRECTORY . "/source/Validation/EmailValidator.php";
require_once WPAM_BASE_DIRECTORY . "/source/Validation/MoneyValidator.php";
require_once WPAM_BASE_DIRECTORY . "/source/Validation/PhoneNumberValidator.php";
require_once WPAM_BASE_DIRECTORY . "/source/Validation/MultiPartPhoneNumberValidator.php";
require_once WPAM_BASE_DIRECTORY . "/source/Validation/ZipCodeValidator.php";
require_once WPAM_BASE_DIRECTORY . "/source/Validation/MultiPartSocialSecurityNumberValidator.php";
require_once WPAM_BASE_DIRECTORY . "/source/Tracking/RequestTracker.php";
require_once WPAM_BASE_DIRECTORY . "/source/Tracking/UniqueIdGenerator.php";
require_once WPAM_BASE_DIRECTORY . "/source/Tracking/TrackingLinkBuilder.php";
require_once WPAM_BASE_DIRECTORY . "/source/TermsCompiler.php";
require_once WPAM_BASE_DIRECTORY . "/source/MessageHelper.php";
require_once WPAM_BASE_DIRECTORY . "/source/MoneyHelper.php";
require_once WPAM_BASE_DIRECTORY . "/source/PayPal/Service.php";
require_once WPAM_BASE_DIRECTORY . "/source/Util/JsonHandler.php";
require_once WPAM_BASE_DIRECTORY . "/source/display_functions.php";
require_once WPAM_BASE_DIRECTORY . "/source/Util/DebugLogger.php";


class WPAM_Plugin
{
	//these are only used as an index and for initial slug naming, users can change it
	const PAGE_NAME_HOME = 'affiliate-home';
	const PAGE_NAME_REGISTER = 'affiliate-register';
        const PAGE_NAME_LOGIN = 'affiliate-login';

	const EXT_JQUERY_UI_VER = '1.8.13';

	private $adminPages = array();
	private $publicPages = array();
	private $affiliateHomePage = null;
	private $affiliateRegisterPage = null;
        private $affiliateLoginPage = null;
	private static $PUBLIC_PAGE_IDS = NULL;
	private static $ICON_URL = NULL;
	private $locale;
	private $setloc;

	public function __construct() {
            
                $this->define_constants();
                
		self::$ICON_URL = WPAM_URL . '/images/icon_cash.png';
		$this->adminPages = array(
		 	new WPAM_Pages_Admin_MyAffiliatesPage(
				'wpam-affiliates',
				__( 'Affiliate Management', 'wpam' ),
				__( 'Affiliates', 'wpam' ),
				WPAM_PluginConfig::$AdminCap,
				array(
					new WPAM_Pages_Admin_MyAffiliatesPage(
						"wpam-affiliates",
						__( 'Affiliates', 'wpam' ),
						__( 'My Affiliates', 'wpam' ),
						WPAM_PluginConfig::$AdminCap
					),
					new WPAM_Pages_Admin_NewAffiliatePage(
						"wpam-newaffiliate",
						__( 'New Affiliate', 'wpam' ),
						__( 'New Affiliate', 'wpam' ),
						WPAM_PluginConfig::$AdminCap
					),
					new WPAM_Pages_Admin_MyCreativesPage(
						"wpam-creatives",
						__( 'Creatives', 'wpam' ),
						__( 'My Creatives', 'wpam' ),
						WPAM_PluginConfig::$AdminCap
					),
					new WPAM_Pages_Admin_PaypalPaymentsPage(
						"wpam-payments",
						__( 'PayPal Mass Pay', 'wpam' ),
						__( 'PayPal Mass Pay', 'wpam' ),
						WPAM_PluginConfig::$AdminCap
					),
					new WPAM_Pages_Admin_SettingsPage(
						'wpam-settings',
						__( 'Settings', 'wpam' ),
						__( 'Settings', 'wpam' ),
						WPAM_PluginConfig::$AdminCap
					)
				)
			)
		);

		$this->affiliateHomePage = new WPAM_Pages_AffiliatesHome(self::PAGE_NAME_HOME, __( 'Store Affiliates', 'wpam' ) );
		$this->affiliateRegisterPage = new WPAM_Pages_AffiliatesRegister(self::PAGE_NAME_REGISTER, __( 'Register', 'wpam' ), $this->affiliateHomePage);
                $this->affiliateLoginPage = new WPAM_Pages_AffiliatesLogin(self::PAGE_NAME_LOGIN, __( 'Affiliate Login', 'wpam' ), $this->affiliateHomePage);
		$this->publicPages = array( 
                self::PAGE_NAME_HOME => $this->affiliateHomePage,
		self::PAGE_NAME_REGISTER => $this->affiliateRegisterPage,
                self::PAGE_NAME_LOGIN => $this->affiliateLoginPage
                );

		//set up base actions
		add_action('init', array( $this, 'onInit' ) );
                
		/*** Start integration handler hooks ***/
                //Getshopped/WP-eCommerce
		add_action('wpsc_transaction_result_cart_item', array( $this, 'onWpscCheckout' ) );
                
                //Woocommerce
                add_action('woocommerce_checkout_update_order_meta', array( $this, 'WooCheckoutUpdateOrderMeta'), 10, 2);
                add_action('woocommerce_order_status_completed',  array( $this, 'WooCommerceProcessTransaction')); //Executes when a status changes to completed
                add_action('woocommerce_order_status_processing',  array( $this, 'WooCommerceProcessTransaction')); //Executes when a status changes to processing
                add_action('woocommerce_checkout_order_processed',  array( $this, 'WooCommerceProcessTransaction'));
                
                //Exchange integration
		add_filter('it_exchange_add_transaction', array( $this, 'onExchangeCheckout' ), 10, 7 );

                //simple cart integration
                add_filter('wpspc_cart_custom_field_value', array( $this, 'wpspcAddCustomValue'));
                add_action('wpspc_paypal_ipn_processed', array($this, 'wpspcProcessTransaction'));

                //EDD integration
		add_action('edd_update_payment_status', array( $this, 'onEDDCheckout' ), 10, 3 );
                
                //Jigoshop integration
                add_action('jigoshop_new_order', array($this, 'jigoshopNewOrder'));
                /*** End integration hooks ***/
                
		if ( WPAM_DEBUG ) {
			add_filter( 'all', array( $this, 'hookDebug' ) );
			add_action( 'all', array( $this, 'hookDebug' ) );
		}
	}

        public function define_constants(){
            global $wpdb;
            //DB Table names
            define( 'WPAM_AFFILIATES_TBL', $wpdb->prefix . 'wpam_affiliates');
            define( 'WPAM_CREATIVES_TBL', $wpdb->prefix . 'wpam_creatives');
            define( 'WPAM_TRACKING_TOKENS_TBL', $wpdb->prefix . 'wpam_tracking_tokens');
            define( 'WPAM_EVENTS_TBL', $wpdb->prefix . 'wpam_events');
            define( 'WPAM_ACTIONS_TBL', $wpdb->prefix . 'wpam_actions');
            define( 'WPAM_TRANSACTIONS_TBL', $wpdb->prefix . 'wpam_transactions');
            define( 'WPAM_MESSAGES_TBL', $wpdb->prefix . 'wpam_messages');
            define( 'WPAM_TRACKING_TOKENS_PURCHASE_LOGS_TBL', $wpdb->prefix . 'wpam_tracking_tokens_purchase_logs');
            define( 'WPAM_AFFILIATES_FIELDS_TBL', $wpdb->prefix . 'wpam_affiliates_fields');
            define( 'WPAM_PAYPAL_LOGS_TBL', $wpdb->prefix . 'wpam_paypal_logs');
            define( 'WPAM_IMPRESSIONS_TBL', $wpdb->prefix . 'wpam_impressions');
            
        }
        
	//remove 'old' style capabilities and replace with 'new'
	private function initCaps() {
		//leave commented until http://core.trac.wordpress.org/ticket/16617 is fixed and released
		//$roleMgr = new WP_Roles();
		//$roleMgr->add_cap('administrator', WPAM_PluginConfig::$AdminCap, true);
		$role = get_role( 'administrator' );
		$role->add_cap( WPAM_PluginConfig::$AdminCap );				
	}
	
	public function onActivation() {
		global $wpdb;

		$this->initCaps();

		$options = new WPAM_Options();
		$options->initOptions();

		if (!file_exists(WPAM_CREATIVE_IMAGES_DIR))
		{
			wp_mkdir_p(WPAM_CREATIVE_IMAGES_DIR);
		}

		$dbInstaller = new WPAM_Data_DatabaseInstaller($wpdb);
		$dbInstaller->doDbInstall();
                $dbInstaller->doInstallPages( $this->publicPages );
                $dbInstaller->doFreshInstallDbDefaultData();		
	}

	private function setMonetaryLocale( $locale ) {
		$is_set = setlocale( LC_MONETARY, 
			$locale, 
			$locale . ' ISO-8859-1',
			$locale . '.iso88591',
			$locale . '.UTF-8',
			$locale . '.UTF8',
			$locale . '.utf8'
		);

		return $is_set;
	}

	public function onInit() {
            
                add_action( 'wp_enqueue_scripts', array($this,'load_shortcode_specific_scripts'));
                
                add_action( 'wp_head' , array($this,'handle_wp_head_hook'));
                
		//actions & filters
		add_action( 'template_redirect',array($this, 'onTemplateRedirect' ) );
		add_action( 'admin_menu', array($this, 'onAdminMenu' ) );
		add_action( 'current_screen', array( $this, 'onCurrentScreen' ) );
                
		add_action( 'wp_ajax_wpam-ajax_request', array( $this, 'onAjaxRequest' ) );

		add_filter('pre_user_email',  array($this, 'filterUserEmail'));
				
		//set the locale for money format & paypal
		$this->locale = WPAM_LOCALE_OVERRIDE ? WPAM_LOCALE_OVERRIDE : get_locale();
		$this->setloc = $this->setMonetaryLocale( $this->locale );
		//loading provided locale didn't work, choose default
		if ( ! $this->setloc && setlocale( LC_MONETARY, 0 ) == 'C')
		    setlocale( LC_MONETARY, '' ); 

		add_action('admin_notices', array( $this, 'showAdminMessages' ) );
		
                if (!is_admin()){
                    add_filter('widget_text', 'do_shortcode');                
                }

		add_shortcode('AffiliatesRegister', array( $this->publicPages[self::PAGE_NAME_REGISTER], 'doShortcode' ) );
		add_shortcode('AffiliatesHome', array( $this->publicPages[self::PAGE_NAME_HOME], 'doShortcode' ) );
                add_shortcode('AffiliatesLogin', array($this, 'doLoginShortcode'));
		add_action( 'save_post' , array( $this, 'onSavePage' ), 10, 2 );

		try	{
			if ( isset( $_GET[WPAM_PluginConfig::$RefKey] ) ) {
				$requestTracker = new WPAM_Tracking_RequestTracker();
                                $query_args = $_GET;
				$requestTracker->handleIncomingReferral($query_args);
			}
		} catch (Exception $e) {
			wp_die("WPAM FAILED: " . $e->getMessage());			
		}
	}
        
        public function load_shortcode_specific_scripts(){
            //Use this function to load JS and CSS file that should only be loaded if the shortcode is present in the page
            global $post;
            if( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'AffiliatesLogin') ) {
                wp_enqueue_style('wpamloginstyle', WPAM_URL . '/style/wpam-login-styles.css');
            }
            if( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'AffiliatesHome') ) {
                wp_enqueue_style('wpampurestyle', WPAM_URL . '/style/pure-styles.css');
            }
            if( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'AffiliatesRegister') ) {
                wp_enqueue_style('wpampurestyle', WPAM_URL . '/style/pure-styles.css');
            }
        }
        
        public function handle_wp_head_hook()
        {
            $debug_marker = "<!-- Affiliates Manager plugin v" . WPAM_VERSION . " - https://wpaffiliatemanager.com/ -->";
            echo "\n${debug_marker}\n";
        }
        
        public function doLoginShortcode()
        {
            $home_page_id = get_option( WPAM_PluginConfig::$HomePageId );
            $home_page_obj = get_post($home_page_id);
            $home_page_url = $home_page_obj -> guid;
                        
            if(is_user_logged_in()) {
                global $current_user;
                get_currentuserinfo();
                $logout_url = wp_logout_url($home_page_url);
                $output = '<div class="wpam-logged-in">';
                $output .= '<p>'.__('You are currently logged in','wpam').'</p>';
                $output .= '<div class="wpam-logged-in-gravatar"><img src="http://www.gravatar.com/avatar/' . md5( trim( strtolower( $current_user->user_email ) ) ) . '?s=64" /></div>';
                $output .= '<div class="wpam-logged-in-username">'.__('Username','wpam').': ' . $current_user->user_login . "</div>";
                $output .= '<div class="wpam-logged-in-email">'.__('Email','wpam').': ' . $current_user->user_email . "</div>";
                $output .= '<div class="wpam-logged-in-logout-link"><a href="'.$logout_url.'">'.__('Log out','wpam').'</a></div>';
                $output .= '</div>'; 
                return $output;
            }
            else{
                $args = array(
                    'echo' => false,
                    'redirect' => $home_page_url,
                );
                $form_output = '<div class="wpam-login-form">'.wp_login_form($args).'</div>';
                return $form_output;
            }
        }

	public function hookDebug( $name ) {
		//file_put_contents( '/tmp/hooks.txt', "{$name}\n", FILE_APPEND );
	}
	
	public function onCurrentScreen( $screen ) {
		//#64 only show this libary on the pages that need it (ones that use jquery-ui-tabs)
		if ( $screen->id == 'toplevel_page_wpam-affiliates' ||
			 strpos( $screen->id, 'affiliates_page' ) === 0 ) {
			
			wp_register_style('wpam_style', WPAM_URL . "/style/style.css");
			wp_enqueue_style('wpam_style');
			
			wp_enqueue_script( 'jquery-ui-datepicker' );
			
			//used for persistent tabs

			wp_enqueue_script( 'jquery-ui-tabs' );

			$this->enqueueDialog();
			wp_register_script( 'wpam_contact_info', WPAM_URL . '/js/contact_info.js', array( 'jquery-ui-dialog' ) );
			wp_register_script( 'wpam_money_format', WPAM_URL . '/js/money_format.js' );

                        wp_register_style( 'wpam_jquery_ui_theme', WPAM_URL . '/style/jquery-ui/smoothness/jquery-ui.css' );
                        wp_enqueue_style( 'wpam_jquery_ui_theme' );
                        
		}
		
		add_thickbox();
	}
	
    public function becomeAffiliate() {
		echo '<div id="aff_div" class="wrap">';
		echo '<div id="icon-users" class="icon32"></div><h2>'. __( 'Become an affiliate', 'wpam' ) . '</h2>';
		echo '<p>' . __( 'Are you interested in earning money by directing visitors to our site?', 'wpam' ) . '</p>';
		//@TODO check the rules on spaces for l10n
		echo '<p><a href="'. $this->affiliateRegisterPage->getLink() .'">' . __( 'Sign up', 'wpam' ) . '</a>' . __( ' to become an affiliate today!', 'wpam' );
		echo '</p></div></div>';
	}

        public function wpspcAddCustomValue($custom_field_val){
            if(isset($_COOKIE[WPAM_PluginConfig::$RefKey])){
                $name = 'wpam_tracking';
                $value = $_COOKIE[WPAM_PluginConfig::$RefKey];
                $new_val = $name.'='.$value;
                $custom_field_val = $custom_field_val.'&'.$new_val;
                WPAM_Logger::log_debug('Simple WP Cart Integration - Adding custom field value. New value: '.$custom_field_val);
            }
            return $custom_field_val;
        }
        
        public function wpspcProcessTransaction($ipn_data){
            $custom_data = $ipn_data['custom'];
            WPAM_Logger::log_debug('Simple WP Cart Integration - IPN processed hook fired. Custom field value: '.$custom_data);
            $custom_values = array();
            parse_str($custom_data, $custom_values);
            if(isset($custom_values['wpam_tracking']) && !empty($custom_values['wpam_tracking'])){
                $tracking_value = $custom_values['wpam_tracking'];
                WPAM_Logger::log_debug('Simple WP Cart Integration - Tracking data present. Need to track affiliate commission. Tracking value: '.$tracking_value);
                
                $purchaseLogId = $ipn_data['txn_id'];
                $purchaseAmount = $ipn_data['mc_gross'];//TODO - later calculate sub-total only
                $strRefKey = $tracking_value;
                $requestTracker = new WPAM_Tracking_RequestTracker();
                $requestTracker->handleCheckoutWithRefKey( $purchaseLogId, $purchaseAmount, $strRefKey);
                WPAM_Logger::log_debug('Simple WP Cart Integration - Commission tracked for transaction ID: '.$purchaseLogId.'. Purchase amt: '.$purchaseAmount);
            }
        }
        
	public function onWpscCheckout( array $purchaseInfo ) {
		if ( $purchaseInfo['purchase_log']['processed'] >= 2 ) {
			$purchaseAmount = $purchaseInfo['purchase_log']['totalprice'] - $purchaseInfo['purchase_log']['base_shipping'];
			$purchaseLogId = $purchaseInfo['purchase_log']['id'];
			
			$requestTracker = new WPAM_Tracking_RequestTracker();
			$requestTracker->handleCheckout( $purchaseLogId, $purchaseAmount );
		}
	}

	public function onWooCheckout( $order_id ) {
		$order = new WC_Order( $order_id );
                $total = $order->order_total;
                $shipping = $order->get_total_shipping();
                $tax = $order->get_total_tax();
                WPAM_Logger::log_debug('WooCommerce Integration - Total amount: ' . $total . '. Total shipping: ' . $shipping . 'Total tax: ' . $tax);
		$purchaseAmount = $total - $shipping - $tax;;
		$requestTracker = new WPAM_Tracking_RequestTracker();
		$requestTracker->handleCheckout( $order_id, $purchaseAmount );
	}
        
        public function WooCheckoutUpdateOrderMeta($order_id, $posted)
        {
            $wpam_refkey = "";
            if(isset($_COOKIE[WPAM_PluginConfig::$RefKey])){
                $wpam_refkey = $_COOKIE[WPAM_PluginConfig::$RefKey];
            }
            if(!empty($wpam_refkey)){//Save the wpam_refkey in the order meta
                update_post_meta( $order_id, '_wpam_refkey', $wpam_refkey);
                $wpam_refkey = get_post_meta($order_id, '_wpam_refkey', true);
                WPAM_Logger::log_debug("WooCommerce Integration - Saving wpam_refkey (".$wpam_refkey.") with order. Order ID: ".$order_id);
            }
        }
        
        public function WooCommerceProcessTransaction($order_id)
        {          
            //affiliates manager code
            WPAM_Logger::log_debug('WooCommerce Integration - Order processed. Checking if affiliate commission needs to be awarded.');
            $order = new WC_Order( $order_id );
            $recurring_payment_method = get_post_meta($order_id, '_recurring_payment_method', true);
            if (!empty($recurring_payment_method)) {
                WPAM_Logger::log_debug("WooCommerce Integration - This is a recurring payment order. Subscription payment method: ".$recurring_payment_method);
                WPAM_Logger::log_debug("The commission will be calculated via the recurring payemnt api call.");
                return;
            }
            $total = $order->order_total;
            $shipping = $order->get_total_shipping();
            $tax = $order->get_total_tax();
            WPAM_Logger::log_debug('WooCommerce Integration - Total amount: ' . $total . ', Total shipping: ' . $shipping . ', Total tax: ' . $tax);
            $purchaseAmount = $total - $shipping - $tax;
            $wpam_refkey = get_post_meta($order_id, '_wpam_refkey', true);
            if(empty($wpam_refkey)){
                WPAM_Logger::log_debug("WooCommerce Integration - could not get wpam_refkey from cookie. This is not an affiliate sale");
                return;
            }

            $order_status = $order->status;
            WPAM_Logger::log_debug("WooCommerce Integration - Order status: " . $order_status);
            if (strtolower($order_status) != "completed" && strtolower($order_status) != "processing") {
                WPAM_Logger::log_debug("WooCommerce Integration - Order status for this transaction is not in a 'completed' or 'processing' state. Commission will not be awarded at this stage.");
                WPAM_Logger::log_debug("WooCommerce Integration - Commission for this transaciton will be awarded when you set the order status to completed or processing.");
                return;
            }
            $requestTracker = new WPAM_Tracking_RequestTracker();
            WPAM_Logger::log_debug('WooCommerce Integration - awarding commission for order ID: '.$order_id.'. Purchase amount: '.$purchaseAmount);
            $requestTracker->handleCheckoutWithRefKey( $order_id, $purchaseAmount, $wpam_refkey);
        }

        public function jigoshopNewOrder($order_id)
        {
            $order = new jigoshop_order( $order_id );

            $total = floatval( $order->order_subtotal );
            if ( $order->order_discount ) {
                $total = $total - floatval( $order->order_discount );
            }
            if ( $total < 0 ) {
                $total = 0;
            }
            
            WPAM_Logger::log_debug('JigoShop Integration - new order received. Order ID: '.order_id.'. Purchase amt: '.$total);

            $requestTracker = new WPAM_Tracking_RequestTracker();
            $requestTracker->handleCheckout( $order_id, $total );
            
        }
        
	public function onEDDCheckout( $payment_id, $new_status, $old_status ) {
            WPAM_Logger::log_debug('Easy Digital Downlaods Integration - order status updated. Old status: '.$old_status.', New Status: '.$new_status.'. Checking if affiliate commission needs to be awarded.');
            if ( $old_status == 'publish' || $old_status == 'complete' ){
                WPAM_Logger::log_debug('Easy Digital Downlaods Integration - This payment was processed once, no need to award commission.');
                return; // Make sure that payments are only completed once
            }
            $purchaseAmount = edd_get_payment_amount( $payment_id );
            WPAM_Logger::log_debug('Easy Digital Downlaods Integration - awarding commission for Order ID: '.$payment_id.'. Purchase amt: '.$purchaseAmount);
            $requestTracker = new WPAM_Tracking_RequestTracker();
            $requestTracker->handleCheckout( $payment_id, $purchaseAmount );
	}

	public function onExchangeCheckout( $transaction_id, $method, $method_id, $status, $customer_id, $cart_object, $args ) {
		$purchaseAmount = it_exchange_get_transaction_subtotal( $transaction_id, false );
		$requestTracker = new WPAM_Tracking_RequestTracker();
		$requestTracker->handleCheckout( $transaction_id, $purchaseAmount );

		return $transaction_id;
	}

	public function onAdminMenu()
	{
		//let the hackery begin! #63
		global $menu;
                $menu_parent_slug = 'wpam-affiliates';

		//show this to affiliates, but not admins / affiliate managers
		if ( ! current_user_can( WPAM_PluginConfig::$AdminCap ) && current_user_can( WPAM_PluginConfig::$AffiliateCap ) ) {
			
                        //$icon_url = esc_url( self::$ICON_URL );
                        
			//I won't necessarily guarantee this will work in the future
			$new_menu = array(
				__( 'Affiliates', 'wpam' ),
				'read',
				$this->affiliateHomePage->getLink(),
				null,
				'menu-top',
				null,
				'dashicons-groups',
			);
			$menu[] = $new_menu;
		}

		//show to non-affiliates
		if ( ! current_user_can( WPAM_PluginConfig::$AffiliateCap ) && ! current_user_can( WPAM_PluginConfig::$AdminCap ) ) {
			add_menu_page(
				__( 'Affiliates', 'wpam' ),
				__( 'Be An Affiliate', 'wpam' ),
				'read',
				'newaffiliate',
				array($this, 'becomeAffiliate'),
				'dashicons-groups'
			);
		}

                //WP Admin Side Menu
		foreach ($this->adminPages as $page)
		{
			add_object_page(
				$page->getName(),
				$page->getMenuName(),
				$page->getRequiredCap(),
				$page->getId(),
				array(),
				'dashicons-groups'
			);
			foreach ($page->getChildren() as $childPage)
			{
				add_submenu_page(
					$page->getId(),
					$childPage->getName(),
					$childPage->getMenuName(),
					$childPage->getRequiredCap(),
					$childPage->getId(),
					array($childPage, "process")
				);
			}

		}
                
                //add submenu page
                add_submenu_page($menu_parent_slug, __("Affiliates Manager Click Tracking", 'wpam'), __("Click Tracking", 'wpam'), WPAM_PluginConfig::$AdminCap, 'wpam-clicktracking', 'wpam_display_clicks_menu');
                //
                do_action('wpam_after_main_admin_menu', $menu_parent_slug);
                
	}

	//for public pages
	public function onTemplateRedirect() {
		if( ! is_array( self::$PUBLIC_PAGE_IDS ) ) {
			self::$PUBLIC_PAGE_IDS = array(
				$this->publicPages[WPAM_Plugin::PAGE_NAME_HOME]->getPageId(),
				$this->publicPages[WPAM_Plugin::PAGE_NAME_REGISTER]->getPageId() );				
		}

		//get the current page
		$page_id = NULL;
		$page = get_page( $page_id );

		//register front-end scripts
		if( isset( $page->ID ) && in_array( $page->ID, self::$PUBLIC_PAGE_IDS ) ) {
			//add jquery dialog + some style
			$this->enqueueDialog();
                        wp_register_style( 'wpam_jquery_ui_theme', WPAM_URL . '/style/jquery-ui/smoothness/jquery-ui.css' );
                        wp_enqueue_style( 'wpam_jquery_ui_theme' );
			wp_register_style('wpam_style', WPAM_URL . "/style/style.css");
			wp_enqueue_style('wpam_style');

			//#45 add a datepicker
			wp_enqueue_script( 'jquery-ui-datepicker' );

			wp_register_script( 'wpam_contact_info', WPAM_URL . '/js/contact_info.js', array( 'jquery-ui-dialog' ) );
			wp_register_script( 'wpam_tnc', WPAM_URL . '/js/tnc.js', array( 'jquery-ui-dialog' ) );
			wp_register_script( 'wpam_payment_method', WPAM_URL . '/js/payment_method.js' );
		}
	}

	/**
	 * There's an upstream bug with JQuery UI Button that will probably be
	 * fixed in JQuery UI 1.9, so we need to override the default WP one until
	 * it's fixed and the fixed version is included in WP.
	 * 
	 * @see http://bugs.jqueryui.com/ticket/7680
	 */
	private function enqueueDialog() {
		//things seem to be working OK with dialog/button as of WP 3.4, so we'll just use the included version
		
		wp_enqueue_script( 'jquery-ui-button' );
		wp_enqueue_script( 'jquery-ui-dialog' );
	}

	//#79 sync email when it's actually changed
	public function filterUserEmail( $email ) {
		$user = wp_get_current_user();
		$newEmail = get_option( $user->ID . '_new_email' );
		if ( ! empty( $newEmail ) && isset( $_GET['newuseremail'] ) ) {
			$db = new WPAM_Data_DataAccess();
			$affiliate = $db->getAffiliateRepository()->loadByUserId($user->ID);
			$affiliate->email = $email;
			$db->getAffiliateRepository()->update($affiliate);
		}
		return $email;
	}

	public function onSavePage( $page_id, $page ) {
		if ( $page->post_type == 'page' ) {
			if ( strpos ( $page->post_content, WPAM_PluginConfig::$ShortCodeHome ) !== false ) {
				update_option( WPAM_PluginConfig::$HomePageId, $page->ID );
			} elseif ( strpos ( $page->post_content, WPAM_PluginConfig::$ShortCodeRegister ) !== false ) {
				update_option( WPAM_PluginConfig::$RegPageId, $page->ID );
			}
		}
	}

	public function showAdminMessages() {
		if ( empty( $this->setloc ) ):
			//don't bother showing this warning if they were trying to use 'en_US'
			if ( $this->locale == 'en_US' ) return;
			$code = WPAM_MoneyHelper::getCurrencyCode();
			$currency = WPAM_MoneyHelper::getDollarSign();

			echo '<div id="message" class="error">
			<p><strong>' . sprintf( __( 'WP Affiliate Manager was unable to load your currency from your WPLANG setting: %s', 'wpam' ), $this->locale ) . '<br/>' .
				sprintf( __( 'Your currency will be displayed as %s and PayPal payments will be paid in %s', 'wpam' ), $currency, $code ) . '</strong></p></div>';
			if ( WPAM_DEBUG )
				echo "<!-- LC_MONETARY {$this->locale}, isset: ", var_export($this->setloc, true), PHP_EOL, var_export( localeconv(), true ), ' -->';
		endif;
	}

	public function onAjaxRequest()
	{
		//die(print_r($_REQUEST, true));
		$jsonHandler = new WPAM_Util_JsonHandler();
		try
		{
			switch ($_REQUEST['handler'])
			{
				case 'approveApplication':
					$response = $jsonHandler->approveApplication($_REQUEST['affiliateId'], $_REQUEST['bountyType'], $_REQUEST['bountyAmount']);
					break;
				case 'declineApplication':
					$response = $jsonHandler->declineApplication($_REQUEST['affiliateId']);
					break;
				case 'blockApplication':
					$response = $jsonHandler->blockApplication($_REQUEST['affiliateId']);
					break;
				case 'activateAffiliate':
					$response = $jsonHandler->activateApplication($_REQUEST['affiliateId']);
					break;
				case 'deactivateAffiliate':
					$response = $jsonHandler->deactivateApplication($_REQUEST['affiliateId']);
					break;
				case 'setCreativeStatus':
					$response = $jsonHandler->setCreativeStatus($_REQUEST['creativeId'], $_REQUEST['status']);
					break;
				case 'addTransaction':
					$response = $jsonHandler->addTransaction($_REQUEST['affiliateId'], $_REQUEST['type'], $_REQUEST['amount'], $_REQUEST['description']);
					break;
				case 'getPostImageElement':
					$response = $jsonHandler->getPostImageElement($_REQUEST['postId']);
					break;
				case 'deleteCreative':
					$response = $jsonHandler->deleteCreative($_REQUEST['creativeId']);
					break;
				default: throw new Exception( __( 'Invalid JSON handler.', 'wpam' ) ); 
			}
		}
		catch (Exception $e)
		{
			$response = new JsonResponse(JsonResponse::STATUS_ERROR, $e->getMessage());
		}

		die( json_encode($response) ); //required to return a proper result		
	}
}
