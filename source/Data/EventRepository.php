<?php
/**
 * @author John Hargrove
 * 
 * Date: Jun 28, 2010
 * Time: 9:42:04 PM
 */

require_once WPAM_BASE_DIRECTORY . "/source/Data/Models/EventModel.php";
require_once WPAM_BASE_DIRECTORY . "/source/Data/Models/ActionModel.php";

class WPAM_Data_EventRepository extends WPAM_Data_GenericRepository
{
	public function insert(WPAM_Data_Models_EventModel $event)
	{
		parent::insert($event);
	}
	public function update(WPAM_Data_Models_EventModel $event)
	{
		parent::update($event);
	}

	public function quickInsert($dateCreated, $trackingKey, $actionKey)
	{
		$query = "
			INSERT INTO `{$this->tableName}`
			SET
				dateCreated = %s,
				trackingTokenId = (
					select trackingTokenId
					from " . $this->db->prefix . WPAM_Data_DataAccess::TABLE_TRACKING_TOKENS . "
					where `trackingKey` = %s
				),
				actionId = (
					select actionId
					from " . $this->db->prefix . WPAM_Data_DataAccess::TABLE_ACTIONS . "
					where `name` = %s
				)";
		$binConverter = new WPAM_Util_BinConverter();
		$query = $this->db->prepare($query, date("Y-m-d H:i:s", $dateCreated), $binConverter->binToString($trackingKey), $actionKey);
		
		$this->db->query($query);
	}

	public function getSummaryForRange($dateStart, $dateEnd, $affiliateId = NULL)
	{
		$query = "
			select
				COALESCE(SUM(IF(a.name = 'visit', 1, 0)), 0) visits,
				COALESCE(SUM(IF(a.name = 'purchase', 1, 0)), 0) purchases
			from `{$this->tableName}` ev
			inner join `".$this->db->prefix . WPAM_Data_DataAccess::TABLE_TRACKING_TOKENS."` tt using (`trackingTokenId`)
			inner join `".$this->db->prefix . WPAM_Data_DataAccess::TABLE_ACTIONS."` a using (`actionId`)
			where
				ev.`dateCreated` >= '".date("Y-m-d", $dateStart)."'
				and ev.`dateCreated` < '".date("Y-m-d", $dateEnd) . "'
		";

		if ($affiliateId !== NULL)
			$query .= "
				and tt.sourceAffiliateId = " . ((int)$affiliateId);

		return $this->db->get_row($query);
	}
}
