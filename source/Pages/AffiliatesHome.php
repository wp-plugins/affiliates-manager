<?php
/**
 * @author John Hargrove
 * 
 * Date: May 24, 2010
 * Time: 9:21:19 PM
 */

class WPAM_Pages_AffiliatesHome extends WPAM_Pages_PublicPage
{
	private $response;
	
	public function __construct( $name, $title, WPAM_Pages_PublicPage $parentPage = NULL ) {
		parent::__construct($name, $title, $parentPage);
	}
	
	public function processRequest($request)
	{
		$db = new WPAM_Data_DataAccess();

		if (is_user_logged_in())
		{
			$currentUser = wp_get_current_user();

			if ($db->getAffiliateRepository()->isUserAffiliate($currentUser->ID))
			{
				$affiliate = $db->getAffiliateRepository()->loadByUserId($currentUser->ID);
				if ($affiliate->isApproved() || $affiliate->isActive())
				{
					$response = $this->doAffiliateControlPanel($affiliate, $request);
					$response->viewData['navigation'] = array(
						array( __( 'Overview', 'wpam' ), $this->getLink(array('sub' => 'overview'))),
						array( __( 'Sales', 'wpam' ), $this->getLink(array('sub' => 'sales'))),
						array( __( 'Payment History', 'wpam' ), $this->getLink(array('sub' => 'payments'))),
						array( __( 'Creatives', 'wpam' ), $this->getLink(array('sub' => 'creatives'))),
						array( __( 'Edit Profile', 'wpam' ), $this->getLink(array('sub' => 'profile'))),
					);
				}
				else if ($affiliate->isDeclined())
				{
					$response = $this->doDeclinedRequest($request);
				}
				else
				{
					$response = $this->doPendingApp();
				}
			}
			else
			{
				$response = $this->doAffiliateNotRegistered($request);
			}
		}
		else
		{
			// show log-in-to-register forms
			$response = $this->doAffiliateNotLoggedIn($request);
		}
		return $response;
	}

	public function doAffiliateNotLoggedIn($request)
	{
		$response = new WPAM_Pages_TemplateResponse('affiliate_not_logged_in');
		$response->viewData['loginUrl'] = wp_login_url($_SERVER['REQUEST_URI']);
		//$response->viewData['registerUrl'] = get_option('siteurl') . "/wp-register.php";
		$response->viewData['registerUrl'] = $this->getLink(array('page_id' => WPAM_Pages_AffiliatesRegister::getPageId()));
		return $response;
	}

	public function doAffiliateNotRegistered($request)
	{
		$response = new WPAM_Pages_TemplateResponse('affiliate_not_registered');
		$response->viewData['registerUrl'] = $this->getLink(array('page_id' => WPAM_Pages_AffiliatesRegister::getPageId()));
		return $response;
	}

	private function doPendingApp()
	{
		$response = new WPAM_Pages_TemplateResponse('affiliate_application_pending');
		return $response;
	}

	private function doHomeRequest($request)
	{
		$response = new WPAM_Pages_TemplateResponse('affiliate_home');
		return $response;
	}

	private function doDeclinedRequest($request)
	{
		$response = new WPAM_Pages_TemplateResponse('affiliate_application_declined');
		return $response;
	}

	protected function doConfirmed($request)
	{
		$response = new WPAM_Pages_TemplateResponse('affiliate_cp_payment_details_confirmed');
		$response->viewData['creativesLink'] = $this->getLink(array('sub' => 'creatives'));
		return $response;
	}

	protected function doInactive($request)
	{
		return new WPAM_Pages_TemplateResponse('affiliate_inactive');
	}

	public function doAffiliateControlPanel($model, $request)
	{
		$user = wp_get_current_user();
		$db = new WPAM_Data_DataAccess();

		$affiliate = $db->getAffiliateRepository()->loadByUserId($user->ID);

		if ($affiliate === null)
			wp_die('affiliates only.');

		if ($affiliate->isApproved())
		{
			if (isset($request['action']) && $request['action'] == 'confirm')
			{
				return $this->doConfirm($request, $affiliate);
			}
			return $this->doApproved($request);
		}
		else if ($affiliate->isActive())
		{
			return $this->doHome($request, $affiliate);
		}
		else if ($affiliate->isInactive())
		{
			return $this->doInactive($request);
		}
		else if ($affiliate->isConfirmed())
		{
			return $this->doConfirmed($request);
		}
	}

	protected function doHome( $request, $affiliate )
	{
		$sub = isset( $request['sub'] ) ? $request['sub'] : '';
		switch ( $sub )	{
			case 'overview':  return $this->doOverviewHome( $request, $affiliate );
			case 'sales':     return $this->doSales( $request, $affiliate );
			case 'payments':  return $this->doPayments( $request, $affiliate );
			case 'creatives': return $this->doCreatives( $request );
			case 'profile':   return $this->doContactInfo( $request, $affiliate );
			default:          return $this->doOverviewHome( $request, $affiliate );
		}

	}

	protected function doCreatives($request)
	{
		$db = new WPAM_Data_DataAccess();


		if ( isset( $request['action'] ) && $request['action'] == 'detail')
		{
			$response = new WPAM_Pages_TemplateResponse('affiliate_creative_detail');
			$affiliate = $db->getAffiliateRepository()->loadByUserId(wp_get_current_user()->ID);
			$creative = $db->getCreativesRepository()->load((int)$request['creativeId']);

			if ($creative === NULL)
				wp_die( __( 'Invalid creative.', 'wpam' ) );
			if ($affiliate === NULL)
				wp_die( __( 'Invalid affiliate', 'wpam' ) );
			if (!$creative->isActive())
				wp_die( __( 'Inactive creative.', 'wpam' ) );

			$response->viewData['affiliate'] = $affiliate;
			$response->viewData['creative'] = $creative;

			$linkBuilder = new WPAM_Tracking_TrackingLinkBuilder($affiliate, $creative);
			$response->viewData['htmlPreview'] = $linkBuilder->getHtmlSnippet();

			return $response;
		}

		$response = new WPAM_Pages_TemplateResponse('affiliate_creative_list');
		$response->viewData['creatives'] = $db->getCreativesRepository()->loadAllActiveNoDeletes();
		return $response;

	}



	protected function doOverviewHome($request, $affiliate)
	{
		$db = new WPAM_Data_DataAccess();
		$accountSummary = $db->getTransactionRepository()->getAccountSummary($affiliate->affiliateId);
		
		$eventSummary = $db->getEventRepository()->getSummaryForRange(
			strtotime(date("Y-m-01")),
			strtotime(date("Y-m-01", strtotime("+1 month"))),
			$affiliate->affiliateId
		);
		$todayEventSummary = $db->getEventRepository()->getSummaryForRange(
			strtotime('today'),
			strtotime('tomorrow'),
			$affiliate->affiliateId
		);

		$monthAccountSummary = $db->getTransactionRepository()->getAccountSummary(
			$affiliate->affiliateId,
			strtotime(date("Y-m-01")),
			strtotime(date("Y-m-01", strtotime("+1 month")))
		);
		$todayAccountSummary = $db->getTransactionRepository()->getAccountSummary(
			$affiliate->affiliateId,
			strtotime('today'),
			strtotime('tomorrow')
		);


		$response = new WPAM_Pages_TemplateResponse('affiliate_cp_home');
		$response->viewData['accountStanding'] = $accountSummary->standing;
		$response->viewData['commissionRateString'] = $this->getCommissionRateString($affiliate);
		$response->viewData['monthVisitors'] = $eventSummary->visits;
		$response->viewData['monthClosedTransactions'] = $eventSummary->purchases;
		$response->viewData['monthRevenue'] = $monthAccountSummary->credits;
		$response->viewData['todayVisitors'] = $todayEventSummary->visits;
		$response->viewData['todayClosedTransactions'] = $todayEventSummary->purchases;
		$response->viewData['todayRevenue'] = $todayAccountSummary->credits;


		return $response;

	}
	
	protected function doSales( $request, $affiliate ) {
		$response = new WPAM_Pages_TemplateResponse( 'affiliate_cp_transactions' );

		$where = array(
			'affiliateId' => $affiliate->affiliateId,
			'type' => 'credit' //load credits
		);

		$affiliateHelper = new WPAM_Util_AffiliateFormHelper();		
		$affiliateHelper->addTransactionDateRange( $where, $request, $response );
		
		$db = new WPAM_Data_DataAccess();
		$response->viewData['transactions'] = $db->getTransactionRepository()->loadMultipleBy(
			$where,
			array('dateCreated' => 'desc')
			);
		$response->viewData['showBalance'] = false;

		return $response;
	}

	protected function doPayments( $request, $affiliate ) {
		$response = new WPAM_Pages_TemplateResponse( 'affiliate_cp_transactions' );

		$where = array( 'affiliateId' => $affiliate->affiliateId,
						'type' => array( '!=', 'credit' ), //load payouts & adjustments
		);
		
		$affiliateHelper = new WPAM_Util_AffiliateFormHelper();		
		$affiliateHelper->addTransactionDateRange( $where, $request, $response );
		
		$db = new WPAM_Data_DataAccess();
		$response->viewData['transactions'] = $db->getTransactionRepository()->loadMultipleBy(
			$where,
			array('dateCreated' => 'desc')
			);

		return $response;
	}

	protected function doContactInfo( $request, $affiliate ) {
		add_action('wp_footer', array( $this, 'onFooter' ) );

		$db = new WPAM_Data_DataAccess();	   

		$affiliateFields = $db->getAffiliateFieldRepository()->loadMultipleBy(
			array('enabled' => true),
			array('order' => 'asc')
		);

		$response = new WPAM_Pages_TemplateResponse('affiliate_cp_contactinfo');
		$response->viewData['affiliateFields'] = $affiliateFields;

		$affiliateHelper = new WPAM_Util_AffiliateFormHelper();		
		$response->viewData['paymentMethods'] = $affiliateHelper->getPaymentMethods();
		$response->viewData['paymentMethod'] = isset( $request['ddPaymentMethod'] ) ? $request['ddPaymentMethod'] : $affiliate->paymentMethod;
		$response->viewData['paypalEmail'] = isset( $request['txtPaypalEmail'] ) ? $request['txtPaypalEmail'] : $affiliate->paypalEmail;

		$user = wp_get_current_user();
		
		if (isset($request['action']) && $request['action'] == 'saveInfo')
		{
			$validator = new WPAM_Validation_Validator();
			$validator->addValidator('ddPaymentMethod', new WPAM_Validation_SetValidator(array('check','paypal')));
				
			if ($request['ddPaymentMethod'] === 'paypal') {
				$validator->addValidator('txtPaypalEmail', new WPAM_Validation_EmailValidator());
			}
			
			$vr = $affiliateHelper->validateForm($validator, $request, $affiliateFields, TRUE);
			if ($vr->getIsValid()) {
				//#79 hackery to do the "normal" WP email approval process
				require_once ABSPATH . 'wp-admin/includes/ms.php';
				$_POST['email'] = $request['_email'];
				$_POST['user_id'] = $user->ID;
				unset( $request['_email'] );
				global $errors;
				//*try* to save email
				send_confirmation_on_profile_email();
				if ( ! empty( $errors->errors ) ) {
					$vr->addError( new WPAM_Validation_ValidatorError( '_email', $_POST['email'] . " " . $errors->get_error_message( 'user_email' ) ) );
					$response->viewData['validationResult'] = $vr;
					$response->viewData['affiliate'] = $affiliate;
					//save for form validation in the footer
					$this->response = $response;
					return $response;
				}
				
				$affiliateHelper->setModelFromForm( $affiliate, $affiliateFields, $request );
				$affiliateHelper->setPaymentFromForm( $affiliate, $request );				
				$db->getAffiliateRepository()->update( $affiliate );
			} else {
				$response->viewData['validationResult'] = $vr;
			}
		}
		
		$new_email = get_option( $user->ID . '_new_email' );
		if ( $new_email && $new_email != $user->user_email ) {
			$response->viewData['newEmail'] = $new_email;
			$response->viewData['userId'] = $user->ID;
		}

		$response->viewData['affiliate'] = $affiliate;
		
		//save for form validation in the footer
		$this->response = $response;

		return $response;
	}

	protected function getCommissionRateString( WPAM_Data_Models_AffiliateModel $affiliate ) {
		if ($affiliate->bountyType === 'fixed') {
			return sprintf( __('%s per sale.', 'wpam' ), wpam_format_money( $affiliate->bountyAmount, false ) );
		} else {
			return sprintf( __( '%s%% of each completed sale, pre-tax', 'wpam' ), $affiliate->bountyAmount );
		}
	}

	protected function doApproved($request)
	{
		$confirmUrl = $this->getLink(array(
			'action' => 'confirm',
			'step' => 'show_terms'
		));
		return new WPAM_Pages_TemplateResponse('affiliate_cp_approved', array('confirmUrl' => $confirmUrl));
	}

	protected function doConfirm($request, $affiliate)
	{
		if ($request['step'] === 'show_terms')
		{
			$response = new WPAM_Pages_TemplateResponse('affiliate_cp_agree_terms');
			$response->viewData['affiliate'] = $affiliate;

			$termsCompiler = new WPAM_TermsCompiler(get_option(WPAM_PluginConfig::$TNCOptionOption));
			$response->viewData['tnc'] = $termsCompiler->build();
			$response->viewData['nextStepUrl'] = $this->getLink(
				array(
					'step' => 'accept_terms',
					'action' => 'confirm'
				)
			);
			return $response;
		}
		else if ($request['step'] === 'accept_terms')
		{
			return $this->getPaymentMethodFormResponse($affiliate);
		}
		else if ($request['step'] === 'submit_payment_details')
		{
			$vr = $this->validateForm($request);
			if ($vr->getIsValid())
			{
				$this->confirmAffiliate($affiliate, $request);

				//Transition affiliate directly to activated without admin review
		        $db = new WPAM_Data_DataAccess();
				$affiliate->activate();
				$db->getAffiliateRepository()->update($affiliate);

				$user = new WP_User($affiliate->userId);
				$user->add_cap(WPAM_PluginConfig::$AffiliateActiveCap);

				return new WPAM_Pages_TemplateResponse('affiliate_cp_payment_details_confirmed', array(
					'creativesLink' => $this->getLink(array(
						'sub' => 'creatives'
					))
				));
			}
			else
			{
				return $this->getPaymentMethodFormResponse($affiliate, $request, $vr);
			}

		}
	}

	protected function confirmAffiliate($affiliate, $request)
	{
		$affiliate->confirm();
		if ($request['ddPaymentMethod'] === 'paypal')
		{
			$affiliate->setPaypalPaymentMethod($request['txtPaypalEmail']);
		}
		else if ($request['ddPaymentMethod'] === 'check')
		{
			$affiliate->setCheckPaymentMethod($request['txtCheckTo']);
		}

		$db = new WPAM_Data_DataAccess();
		$db->getAffiliateRepository()->update($affiliate);
	}

	protected function getPaymentMethodFormResponse($affiliate, $request = array(), $validationResult = NULL)
	{
		add_action('wp_footer', array( $this, 'onFooter' ) );

		$response = new WPAM_Pages_TemplateResponse('affiliate_cp_payment_details_simple');
		$response->viewData['request'] = $request;
		$response->viewData['affiliate'] = $affiliate;
		
		$affiliateHelper = new WPAM_Util_AffiliateFormHelper();
		$response->viewData['paymentMethods'] = $affiliateHelper->getPaymentMethods();

		$response->viewData['validationResult'] = $validationResult;
		$response->viewData['nextStepUrl'] = $this->getLink(array(
			'step' => 'submit_payment_details',
			'action' => 'confirm'
		));

		//save for form validation in the footer
		$this->response = $response;

		return $response;
	}

	protected function validateForm($request)
	{
		$validator = new WPAM_Validation_Validator();

		$validator->addValidator('ddPaymentMethod', new WPAM_Validation_SetValidator(array('paypal','check')));

		if ($request['ddPaymentMethod'] === 'check')
		{
			$validator->addValidator('txtCheckTo', new WPAM_Validation_StringValidator(1));
		}
		else if ($request['ddPaymentMethod'] === 'paypal')
		{
			$validator->addValidator('txtPaypalEmail', new WPAM_Validation_EmailValidator());
		}

		return $validator->validate($request);
	}


	public function isAvailable($wpUser)
	{
		// root is visible to all classes of users
		return true;
	}
	
	public static function getPageId() {
		return get_option( WPAM_PluginConfig::$HomePageId );
	}
	
	public function onFooter() {
		wp_localize_script( 'wpam_contact_info', 'currencyL10n', array(
								'fixedLabel' => sprintf( __( 'Bounty Rate (%s per Sale)', 'wpam' ), WPAM_MoneyHelper::getDollarSign() ),
								'percentLabel' => __( 'Bounty Rate (% of Sale)', 'wpam' ),
								'okLabel' => __( 'OK', 'wpam' ),
		) );
		wp_print_scripts( 'wpam_contact_info' );
		wp_print_scripts( 'wpam_payment_method' );
		
		$response = new WPAM_Pages_TemplateResponse('widget_form_errors', $this->response->viewData);
		echo $response->render();
	}

}
