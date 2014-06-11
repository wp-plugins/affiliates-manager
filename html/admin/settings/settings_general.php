<script type="text/javascript">
jQuery(function($){

	var dialog = {
		resizable: false,
		height: 500,
		width: 500,
		autoOpen: false,
		modal: true,
		draggable: false,
		buttons: [ {
			  text : 'OK',
			  click : function() { $(this).dialog('close'); }
		} ]
	};
	
	$("#tnc_help").dialog(dialog);

	$("#tncInfo").click(function()
	{
		$("#tnc_help").dialog('open');
	});

	$("#cookie_help").dialog(dialog);

	$("#cookieInfo").click(function()
    {
		$("#cookie_help").dialog('open');
    });
	
	$("#email_help").dialog(dialog);

	$(".emailInfo").click(function()
    {
		$("#email_help").dialog('open');
    });

});
</script>

<div id="tnc_help" style="display: none;">
	This is the terms and conditions template that will be used when confirming new affiliates.
	You should review these.  There are special tokens through-out this document that will be replaced
	with special values:<br /><br/>
	<ul style="margin-left: 30px;">
		<li><strong>[site name]</strong>: The name of your site will go here.</li>
		<li><strong>[site url]</strong>: The URL to your website will be placed here.</li>
		<li><strong>[terms url]</strong>: This will be a permanent link to the terms & conditions.</li>
		<li><strong>[payout minimum]</strong>: Will be replaced with the minimum payout amount.</li>
	</ul>
</div>
	 
<div id="cookie_help" style="display: none;">
        <p>
        When a user comes to your site via an affiliate link, a cookie will be set in the users web browser.
        Normally, with a setting of zero (0), the affiliation only lasts as long as the user keeps their web
        browser open.  That is, if they don't purchase something and instead close their browser, the
        affiliation ends.
</p>
<p>
       Setting this to a value other than zero, will allow the cookie to stay around for that many days.  This would
       give credit to the affiliate if a user arrived at your site via an affiliate link, closed their browser, but came
       back later to your site by typing in the URL (rather than visiting through an affiliate link) and purchased something
       (within the specified number of days).
       </p>
       <p>In the affiliate industry a 15-30 day cookie duration is pretty standard. The longer the duration, the more attractive your program is to affiliates.
       </p>
</div>

<div id="email_help" style="display: none;">
        <p>
        The Affiliate Manager sends emails to new affiliates notifying them of their approval status.
By default, WordPress sends these messages as:</p>
<p>WordPress &lt;wordpress@[sitename].com&gt;
<p>You may choose to override the name & address with something more suitable. 
These addresses will only affect emails going to affiliates regarding their approval status.
       </p>
</div>

<table class="form-table">
	<tr>
		<th width="200">
			<label for="txtMinimumPayout">
				Minimum Payout Amount
			</label>
		</th>
		<td>
			<input type="text" size="30" name="txtMinimumPayout" id="txtMinimumPayout" value="<?php echo $this->viewData['request']['txtMinimumPayout']?>" />
		</td>
	</tr>
	<tr>
		<th width="200">
			<label for="txtTnC">Terms and Conditions</label>
			<img id="tncInfo" style="cursor: pointer;" src="<?php echo WPAM_URL . "/images/info_icon.png"?>" />
		</th>
		<td>
			<textarea id="txtTnC" name="txtTnc" cols="60" rows="20"><?php echo $this->viewData['request']['txtTnc']?></textarea>
		</td>
	</tr>
	<tr>
    	<th width="200">
            <label for="txtCookieExpire">Cookie Duration (days)</label>
			<img id="cookieInfo" style="cursor: pointer;" src="<?php echo WPAM_URL . "/images/info_icon.png"?>" />
        </th>
        <td>
        	<input type="text" size="30" name="txtCookieExpire" id="txtCookieExpire" value="<?php echo $this->viewData['request']['txtCookieExpire']?>" />
        </td>
	</tr>
 	<tr>
    	<th width="200">
            <label for="txtEmailName">Email name</label>
			<img class="emailInfo" style="cursor: pointer;" src="<?php echo WPAM_URL . "/images/info_icon.png"?>" />
        </th>
        <td>
        	<input type="text" size="30" name="txtEmailName" id="txtEmailName" value="<?php echo $this->viewData['request']['txtEmailName']?>" />
			<span>(Leave blank to use WordPress default)</span>
        </td>
	</tr>
 	<tr>
    	<th width="200">
            <label for="txtEmailAddress">Email address</label>
			<img class="emailInfo" style="cursor: pointer;" src="<?php echo WPAM_URL . "/images/info_icon.png"?>" />
        </th>
        <td>
        	<input type="text" size="30" name="txtEmailAddress" id="txtEmailAddress" value="<?php echo $this->viewData['request']['txtEmailAddress']?>" />
			<span>(Leave blank to use WordPress default)</span>
        </td>
	</tr>
        
        <tr>
		<th width="200">
			<label for="autoaffapprove">
				Automatically approve a new affiliate
			</label>
		</th>
		<td>
			<input type="checkbox" id="autoaffapprove" name="autoaffapprove" <?php
			if ($this->viewData['request']['autoaffapprove'])
				echo 'checked="checked"';
			?>/>
		</td>
	</tr>
        
        <tr>
        <th>
                <label for="affBountyType"><?php _e( 'Bounty Type', 'wpam' ) ?></label>
        </th>
        <td>
                <select id="affBountyType" name="affBountyType">
                        <option value="percent" <?php echo ($this->viewData['request']['affBountyType'] == 'percent' ? 'selected="selected"' : '')?>><?php _e( 'Percentage of Sales', 'wpam' ) ?></option>
                        <option value="fixed" <?php echo ($this->viewData['request']['affBountyType'] == 'fixed' ? 'selected="selected"' : '')?>><?php _e( 'Fixed Amount per Sale', 'wpam' ) ?></option>
                </select>
        </td>
        </tr>
        
        <tr>
                <th>
                        <label id="lblaffBountyAmount" for="affBountyAmount"><?php _e( 'Bounty Rate (% of Sale)', 'wpam' ) ?></label>
                </th>
                <td>
                        <input type="text" id="affBountyAmount" name="affBountyAmount" size="5" value="<?php echo $this->viewData['request']['affBountyAmount']?>" />
                </td>
        </tr>
        
        <tr valign="top">
        <th scope="row"><label for="enable_debug"><?php echo __('Enable Debug','wpam'); ?></label></th>
        <td>
        <input name="enable_debug" type="checkbox"<?php if($this->viewData['request']['enable_debug']!='') echo ' checked="checked"'; ?> value="1"/>
        <p class="description">If checked, debug output will be written to log files. This is useful for troubleshooting post payment failures.</p>
        <p class="description">You can check the debug log file by clicking on the link below (The log file can be viewed using any text editor):</p>
        
        <p><a href="<?php echo WPAM_URL.'/logs/wpam-log.txt'; ?>" target="_blank">wpam-log.txt</a></p>    
        <div class="submit">
            <input type="submit" name="wpam_reset_logfile" class="button" style="color:red" value="Reset Debug Log file"/> 
            <p class="descripiton">Use it to reset the affiliate manager plugin's log file.</p>
        </div>
        </td></tr>
        
</table>