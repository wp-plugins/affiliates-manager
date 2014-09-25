<?php
/**
 * @author John Hargrove
 * 
 * Date: Jun 4, 2010
 * Time: 12:38:45 AM
 */

class WPAM_Util_JsonHandler
{
	public function approveApplication($affiliateId, $bountyType, $bountyAmount)
	{
		if (!wp_get_current_user()->has_cap(WPAM_PluginConfig::$AdminCap))
			throw new Exception( __('Access denied.', 'wpam' ) );

		if (!is_numeric($bountyAmount))
			throw new Exception( __( 'Invalid bounty amount.', 'wpam' ) );

		if (!in_array($bountyType, array("fixed", "percent")))
			throw new Exception( __('Invalid bounty type.', 'wpam' ) );

		$affiliateId = (int)$affiliateId;

		$db = new WPAM_Data_DataAccess();
		$affiliate = $db->getAffiliateRepository()->load($affiliateId);

		if ($affiliate === null)
			throw new Exception( __('Invalid affiliate', 'wpam' ) );

		if ( $affiliate->isPending() ) {
			$userHandler = new WPAM_Util_UserHandler();
			$userHandler->approveAffiliate( $affiliate, $bountyType, $bountyAmount );

			return new JsonResponse( JsonResponse::STATUS_OK );
		} else {
			throw new Exception( __( 'Invalid state transition.', 'wpam' ) );
		}
	}

	
	public function declineApplication($affiliateId)
	{
		if (!wp_get_current_user()->has_cap(WPAM_PluginConfig::$AdminCap))
			throw new Exception( __('Access denied.', 'wpam' ) );

		$affiliateId = (int)$affiliateId;

		$db = new WPAM_Data_DataAccess();
		$affiliate = $db->getAffiliateRepository()->load($affiliateId);

		if ($affiliate === null)
			throw new Exception( __( 'Invalid affiliate', 'wpam' ) );

		if ($affiliate->isPending() || $affiliate->isBlocked())
		{
			$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
			if ($affiliate->isPending())
			{
				$mailer = new WPAM_Util_EmailHandler();
				$mailer->mailAffiliate( $affiliate->email, sprintf( __( 'Affiliate Application for %s', 'wpam' ), $blogname ), WPAM_MessageHelper::GetMessage( 'affiliate_application_declined_email' ) );
			}
			$affiliate->decline();
			$db->getAffiliateRepository()->update($affiliate);
			return new JsonResponse(JsonResponse::STATUS_OK);
		}
		else
		{
			throw new Exception( __( 'Invalid state transition.', 'wpam' ) );
		}
	}

	public function blockApplication($affiliateId)
	{
		if (!wp_get_current_user()->has_cap(WPAM_PluginConfig::$AdminCap))
			throw new Exception( __('Access denied.', 'wpam' ) );

		$affiliateId = (int)$affiliateId;

		$db = new WPAM_Data_DataAccess();
		$affiliate = $db->getAffiliateRepository()->load($affiliateId);

		if ($affiliate === null)
			throw new Exception( __( 'Invalid affiliate', 'wpam' ) );

		if ($affiliate->isPending() || $affiliate->isDeclined())
		{
			$affiliate->block();
			$db->getAffiliateRepository()->update($affiliate);
			return new JsonResponse(JsonResponse::STATUS_OK);
		}
		else
		{
			throw new Exception( __( 'Invalid state transition.', 'wpam' ) );
		}

	}

	public function activateApplication($affiliateId)
	{
		if (!wp_get_current_user()->has_cap(WPAM_PluginConfig::$AdminCap))
			throw new Exception( __('Access denied.', 'wpam' ) );

		$affiliateId = (int)$affiliateId;

		$db = new WPAM_Data_DataAccess();
		$affiliate = $db->getAffiliateRepository()->load($affiliateId);

		if ($affiliate === NULL)
			throw new Exception( __( 'Invalid affiliate', 'wpam' ) );

		if ( !$affiliate->isConfirmed() && !$affiliate->isInactive() )
			throw new Exception( __( 'Invalid state transition.', 'wpam' ) );

		$affiliate->activate();
		$db->getAffiliateRepository()->update($affiliate);

		$user = new WP_User($affiliate->userId);
		$user->add_cap(WPAM_PluginConfig::$AffiliateActiveCap);		

		return new JsonResponse(JsonResponse::STATUS_OK);
	}
	
	public function deactivateApplication( $affiliateId ) {
		if ( !wp_get_current_user()->has_cap( WPAM_PluginConfig::$AdminCap ) )
			throw new Exception( __('Access denied.', 'wpam' ) );

		$affiliateId = (int)$affiliateId;

		$db = new WPAM_Data_DataAccess();
		$affiliate = $db->getAffiliateRepository()->load( $affiliateId );

		if ( $affiliate === NULL )
			throw new Exception( __( 'Invalid affiliate', 'wpam' ) );

		if ( !$affiliate->isActive() )
			throw new Exception( __('Access denied.', 'wpam' ) );

		$affiliate->deactivate();
		$db->getAffiliateRepository()->update( $affiliate );

		$user = new WP_User( $affiliate->userId );
		$user->remove_cap( WPAM_PluginConfig::$AffiliateActiveCap );

		return new JsonResponse(JsonResponse::STATUS_OK);
	}

	public function setCreativeStatus($creativeId, $status)
	{
		if (!wp_get_current_user()->has_cap(WPAM_PluginConfig::$AdminCap))
			throw new Exception( __('Access denied.', 'wpam' ) );

		if (!in_array($status, array('inactive', 'active')))
			throw new Exception( __( 'Invalid status.', 'wpam' ) );

		$validTransitions = array(
			'active' => array('inactive'),
			'inactive' => array('active')
		);

		$creativeId = (int)$creativeId;

		$db = new WPAM_Data_DataAccess();
		$creative = $db->getCreativesRepository()->load($creativeId);

		if ($creative === NULL)
			throw new Exception(  __( 'Invalid creative', 'wpam' ) );

		if (!in_array($status, $validTransitions[$creative->status]))
			throw new Exception( __( 'Invalid state transition.', 'wpam' ) );

		$creative->status = $status;
		$db->getCreativesRepository()->update($creative);

		return new JsonResponse(JsonResponse::STATUS_OK);
	}

	public function getPostImageElement($postId)
	{
		$imgElement = wp_get_attachment_image_src((int)$postId);

		if (is_array($imgElement))
		{
			$response = new JsonResponse(JsonResponse::STATUS_OK);
			$response->data = $imgElement[0];
			return $response;
		}
		else
		{
			return new JsonResponse(JsonResponse::STATUS_ERROR, "No image found.");
		}
	}

	public function addTransaction($affiliateId, $type, $amount, $description = NULL)
	{
		if (!wp_get_current_user()->has_cap(WPAM_PluginConfig::$AdminCap))
			throw new Exception( __('Access denied.', 'wpam' ) );

		if (!in_array($type, array('credit', 'payout', 'adjustment')))
			throw new Exception( __( 'Invalid transaction type.', 'wpam' ) );

		if (!is_numeric($amount))
			throw new Exception( __( 'Invalid value for amount.', 'wpam' ) );

		$db = new WPAM_Data_DataAccess();

		if (!$db->getAffiliateRepository()->exists($affiliateId))
			throw new Exception( __( 'Invalid affiliate', 'wpam' ) );

		$transaction = new WPAM_Data_Models_TransactionModel();
		$transaction->type = $type;
		$transaction->affiliateId = $affiliateId;
		$transaction->amount = $amount;
		$transaction->dateCreated = time();
		$transaction->description = $description;

		$db->getTransactionRepository()->insert($transaction);

		return new JsonResponse(JsonResponse::STATUS_OK);
	}

	public function deleteCreative($creativeId)
	{
		if (!wp_get_current_user()->has_cap(WPAM_PluginConfig::$AdminCap))
			throw new Exception( __('Access denied.', 'wpam' ) );
		$db = new WPAM_Data_DataAccess();

		if (!$db->getCreativesRepository()->exists($creativeId))
			throw new Exception( __( 'Invalid creative.', 'wpam' ) );

		$creative = $db->getCreativesRepository()->load($creativeId);
		$creative->status = 'deleted';
		$db->getCreativesRepository()->update($creative);

		return new JsonResponse(JsonResponse::STATUS_OK);
	}
}

class JsonResponse
{
	const STATUS_OK = "OK";
	const STATUS_ERROR = "ERROR";

	public $status;
	public $message;
	public $data;
	public function __construct($status, $message = NULL) { $this->status = $status; $this->message = $message; }

}
