<?php

class WPAM_Pages_Admin_SettingsPage {

    private $response;

    function __construct() {
        
    }

    public function render_settings_page() {   
        $request = $_REQUEST;
        $request = stripslashes_deep($request);

        if (isset($request['wpam_reset_logfile'])) {
            WPAM_Logger::reset_log_file();
            echo '<div class="updated fade"><p>Log file has been reset</p></div>';
        }

        if (isset($request['action']) && $request['action'] === 'submitSettings') {
            $this->doFormSubmit($request);
        } else {
            $this->getSettingsForm();
        }

        echo $this->response->render();
    }

    protected function doFormSubmit($request) {
        $validator = new WPAM_Validation_Validator();
        if (isset($request['AffGeneralSettings'])) {
            $validator->addValidator('txtMinimumPayout', new WPAM_Validation_MoneyValidator());
            $validator->addValidator('txtCookieExpire', new WPAM_Validation_NumberValidator());
        }
        if (isset($request['AffRegSettings'])) {
            //$validator->addValidator('txtTnc', new WPAM_Validation_StringValidator(1));
        }
        //#61 allow these to be unset/null
        if (!empty($request['txtEmailName']))
            $validator->addValidator('txtEmailName', new WPAM_Validation_StringValidator(1));
        if (!empty($request['txtEmailAddress']))
            $validator->addValidator('txtEmailAddress', new WPAM_Validation_EmailValidator());

        if (isset($request['chkEnablePaypalMassPay'])) {
            $validator->addValidator('txtPaypalAPIUser', new WPAM_Validation_StringValidator(1));
            $validator->addValidator('txtPaypalAPIPassword', new WPAM_Validation_StringValidator(1));
            $validator->addValidator('txtPaypalAPISignature', new WPAM_Validation_StringValidator(1));
        }

        $vr = $validator->validate($request);

        if ($vr->getIsValid()) {
            $db = new WPAM_Data_DataAccess();
            if (isset($request['AffGeneralSettings'])) {  //General settings options submitted
                update_option(WPAM_PluginConfig::$MinPayoutAmountOption, $request['txtMinimumPayout']);
                update_option(WPAM_PluginConfig::$CookieExpireOption, $request['txtCookieExpire']);
                update_option(WPAM_PluginConfig::$EmailNameOption, $request['txtEmailName']);
                update_option(WPAM_PluginConfig::$EmailAddressOption, $request['txtEmailAddress']);
                update_option(WPAM_PluginConfig::$AffBountyType, $request['affBountyType']);
                update_option(WPAM_PluginConfig::$AffBountyAmount, $request['affBountyAmount']);
                update_option(WPAM_PluginConfig::$AffCurrencySymbol, $request['affCurrencySymbol']);
                update_option(WPAM_PluginConfig::$AffCurrencyCode, $request['affCurrencyCode']);
                if (isset($request['autoaffapprove'])) {
                    update_option(WPAM_PluginConfig::$AutoAffiliateApproveIsEnabledOption, 1);
                } else {
                    update_option(WPAM_PluginConfig::$AutoAffiliateApproveIsEnabledOption, 0);
                }
                if (isset($request['chkImpressions'])) {
                    update_option(WPAM_PluginConfig::$AffEnableImpressions, 1);
                } else {
                    update_option(WPAM_PluginConfig::$AffEnableImpressions, 0);
                }
                if (isset($request['enable_debug'])) {
                    update_option(WPAM_PluginConfig::$AffEnableDebug, 1);
                } else {
                    update_option(WPAM_PluginConfig::$AffEnableDebug, 0);
                }
            }

            if (isset($request['AffPaymentSettings'])) {   //Payment settings options submitted
                if (isset($request['chkEnablePaypalMassPay'])) {
                    update_option(WPAM_PluginConfig::$PaypalMassPayEnabledOption, 1);
                    update_option(WPAM_PluginConfig::$PaypalAPIUserOption, $request['txtPaypalAPIUser']);
                    update_option(WPAM_PluginConfig::$PaypalAPIPasswordOption, $request['txtPaypalAPIPassword']);
                    update_option(WPAM_PluginConfig::$PaypalAPISignatureOption, $request['txtPaypalAPISignature']);
                    update_option(WPAM_PluginConfig::$PaypalAPIEndPointOption, $request['ddPaypalAPIEndPoint']);
                } else {
                    update_option(WPAM_PluginConfig::$PaypalMassPayEnabledOption, 0);
                }
            }

            if (isset($request['AffMsgSettings'])) {      //Messaging settings options submitted
                foreach ($request['messages'] as $message) {
                    $messageModel = $db->getMessageRepository()->loadBy(array('name' => $message['name']));
                    if ($messageModel != NULL) {
                        $messageModel->content = $message['content'];
                        $db->getMessageRepository()->update($messageModel);
                    }
                }
            }

            if (isset($request['AffRegSettings'])) {    //Registration settings options submitted
                if (isset($request['chkPayoutMethodPaypal'])) {
                    update_option(WPAM_PluginConfig::$PayoutMethodPaypalIsEnabledOption, 1);
                } else {
                    update_option(WPAM_PluginConfig::$PayoutMethodPaypalIsEnabledOption, 0);
                }
                if (isset($request['chkPayoutMethodCheck'])) {
                    update_option(WPAM_PluginConfig::$PayoutMethodCheckIsEnabledOption, 1);
                } else {
                    update_option(WPAM_PluginConfig::$PayoutMethodCheckIsEnabledOption, 0);
                }
                $affiliateFieldRepository = $db->getAffiliateFieldRepository();
                $affiliateFieldRepository->delete(array('type' => 'custom'));

                $order = 0;
                foreach ($request['field'] as $fieldName => $params) {
                    if ($params['type'] === 'custom') {

                        $field = new WPAM_Data_Models_AffiliateFieldModel();
                        $field->type = 'custom';
                        $field->databaseField = $fieldName;
                        $field->fieldType = $params['fieldType'];
                        $field->length = $params['maxLength'];
                        $field->name = $params['displayName'];
                    } else {
                        $field = $affiliateFieldRepository->loadby(array('databaseField' => $fieldName));
                    }

                    $field->order = $order++;
                    //#43 email is required (but not submitted b/c it's disabled on the form)
                    if ($fieldName == 'email') {
                        $field->enabled = 1;
                        $field->required = 1;
                    } else {
                        $field->enabled = isset($params['enabled']) ? 1 : 0;
                        $field->required = isset($params['required']) ? 1 : 0;
                    }

                    if ($params['type'] === 'custom') {
                        $affiliateFieldRepository->insert($field);
                    } else {
                        $affiliateFieldRepository->update($field);
                    }
                }
                
                $txtTnc = $request['txtTnc'];
                if(empty($txtTnc)){  //save the default T&C message if empty
                    $txtTnc = file_get_contents( WPAM_RESOURCES_DIR . "default_tnc.txt" );
                }
                update_option(WPAM_PluginConfig::$TNCOptionOption, $txtTnc);
                
                $affhomemsg = $request['affhomemsg'];
                if(empty($affhomemsg)){  //save the default home message if empty
                    $login_url = get_option(WPAM_PluginConfig::$AffLoginPageURL);
                    $register_page_id = get_option(WPAM_PluginConfig::$RegPageId);
                    $register_page_url = get_permalink($register_page_id);
                    $affhomemsg = 'This is the affiliates section of this store. If you are an existing affiliate, please <a href="'.$login_url.'">log in</a> to access your control panel.';
                    $affhomemsg .= '<br />';
                    $affhomemsg .= '<br />';
                    $affhomemsg .= 'If you are not an affiliate, but wish to become one, you will need to apply. To apply, you must be a registered user on this blog. If you have an existing account on this blog, please <a href="'.$login_url.'">log in</a>. If not, please <a href="'.$register_page_url.'">register</a>.';
                }
                update_option(WPAM_PluginConfig::$AffHomeMsg, $affhomemsg);
            }

            if (isset($request['AffPagesSettings'])) {    //Affiliate pages/forms options submitted
                update_option(WPAM_PluginConfig::$AffLoginPageURL, $request['affLoginPage']);
            }

            return $this->getSettingsForm(NULL, "Settings updated.");
        }
        //else
        return $this->getSettingsForm($request, NULL, $vr);
    }

    protected function getSettingsForm($request = NULL, $message = NULL, $vr = NULL) {
        //add widget_form_error js to settings page
        add_action('admin_footer', array($this, 'onFooter'));

        $response = new WPAM_Pages_TemplateResponse('admin/settings/settings');
        $db = new WPAM_Data_DataAccess();

        $response->viewData['affiliateRegisterFields'] = $db->getAffiliateFieldRepository()->loadMultipleBy(array(), array('order' => 'asc'));
        $response->viewData['messages'] = $db->getMessageRepository()->loadAll();

        if ($request !== NULL) {
            $response->viewData['request']['txtTnc'] = isset($request['txtTnc']) ? $request['txtTnc'] : '';
            $response->viewData['request']['affhomemsg'] = isset($request['affhomemsg']) ? $request['affhomemsg'] : '';
            $response->viewData['request']['txtMinimumPayout'] = $request['txtMinimumPayout'];
            $response->viewData['request']['txtCookieExpire'] = $request['txtCookieExpire'];
            $response->viewData['request']['txtEmailName'] = $request['txtEmailName'];
            $response->viewData['request']['txtEmailAddress'] = $request['txtEmailAddress'];
            $response->viewData['request']['autoaffapprove'] = isset($request['autoaffapprove']) ? 1 : 0;
            $response->viewData['request']['affBountyType'] = $request['affBountyType'];
            $response->viewData['request']['affBountyAmount'] = $request['affBountyAmount'];
            $response->viewData['request']['affCurrencySymbol'] = $request['affCurrencySymbol'];
            $response->viewData['request']['affCurrencyCode'] = $request['affCurrencyCode'];
            $response->viewData['request']['chkImpressions'] = isset($request['chkImpressions']) ? 1 : 0;
            $response->viewData['request']['enable_debug'] = isset($request['enable_debug']) ? 1 : 0;
            $response->viewData['request']['chkPayoutMethodCheck'] = isset($request['chkPayoutMethodCheck']) ? 1 : 0;
            $response->viewData['request']['chkPayoutMethodPaypal'] = isset($request['chkPayoutMethodPaypal']) ? 1 : 0;
            $response->viewData['request']['chkEnablePaypalMassPay'] = isset($request['chkEnablePaypalMassPay']) ? 1 : 0;
            $response->viewData['request']['txtPaypalAPIUser'] = isset($request['txtPaypalAPIUser']) ? $request['txtPaypalAPIUser'] : '';
            $response->viewData['request']['txtPaypalAPIPassword'] = isset($request['txtPaypalAPIPassword']) ? $request['txtPaypalAPIPassword'] : '';
            $response->viewData['request']['txtPaypalAPISignature'] = isset($request['txtPaypalAPISignature']) ? $request['txtPaypalAPISignature'] : '';
            $response->viewData['request']['ddPaypalAPIEndPoint'] = isset($request['ddPaypalAPIEndPoint']) ? $request['ddPaypalAPIEndPoint'] : '';
            $response->viewData['request']['affLoginPage'] = isset($request['affLoginPage']) ? $request['affLoginPage'] : '';
            $response->viewData['validationResult'] = $vr;
        } else {
            $response->viewData['request']['txtTnc'] = get_option(WPAM_PluginConfig::$TNCOptionOption);
            $response->viewData['request']['affhomemsg'] = get_option(WPAM_PluginConfig::$AffHomeMsg);
            $response->viewData['request']['txtMinimumPayout'] = get_option(WPAM_PluginConfig::$MinPayoutAmountOption);
            $response->viewData['request']['txtCookieExpire'] = get_option(WPAM_PluginConfig::$CookieExpireOption);
            $response->viewData['request']['txtEmailName'] = get_option(WPAM_PluginConfig::$EmailNameOption);
            $response->viewData['request']['txtEmailAddress'] = get_option(WPAM_PluginConfig::$EmailAddressOption);
            $response->viewData['request']['autoaffapprove'] = get_option(WPAM_PluginConfig::$AutoAffiliateApproveIsEnabledOption);
            $response->viewData['request']['affBountyType'] = get_option(WPAM_PluginConfig::$AffBountyType);
            $response->viewData['request']['affBountyAmount'] = get_option(WPAM_PluginConfig::$AffBountyAmount);
            $response->viewData['request']['affCurrencySymbol'] = get_option(WPAM_PluginConfig::$AffCurrencySymbol);
            $response->viewData['request']['affCurrencyCode'] = get_option(WPAM_PluginConfig::$AffCurrencyCode);
            $response->viewData['request']['chkImpressions'] = get_option(WPAM_PluginConfig::$AffEnableImpressions);
            $response->viewData['request']['enable_debug'] = get_option(WPAM_PluginConfig::$AffEnableDebug);
            $response->viewData['request']['chkPayoutMethodCheck'] = get_option(WPAM_PluginConfig::$PayoutMethodCheckIsEnabledOption);
            $response->viewData['request']['chkPayoutMethodPaypal'] = get_option(WPAM_PluginConfig::$PayoutMethodPaypalIsEnabledOption);
            $response->viewData['request']['chkEnablePaypalMassPay'] = get_option(WPAM_PluginConfig::$PaypalMassPayEnabledOption);
            $response->viewData['request']['txtPaypalAPIUser'] = get_option(WPAM_PluginConfig::$PaypalAPIUserOption);
            $response->viewData['request']['txtPaypalAPIPassword'] = get_option(WPAM_PluginConfig::$PaypalAPIPasswordOption);
            $response->viewData['request']['txtPaypalAPISignature'] = get_option(WPAM_PluginConfig::$PaypalAPISignatureOption);
            $response->viewData['request']['ddPaypalAPIEndPoint'] = get_option(WPAM_PluginConfig::$PaypalAPIEndPointOption);
            $response->viewData['request']['affLoginPage'] = get_option(WPAM_PluginConfig::$AffLoginPageURL);
        }

        if ($message !== NULL)
            $response->viewData['updateMessage'] = $message;

        //save for form validation in the footer
        $this->response = $response;

        return $response;
    }

    public function onFooter() {
        $response = new WPAM_Pages_TemplateResponse('widget_form_errors', $this->response->viewData);
        echo $response->render();
    }

}
