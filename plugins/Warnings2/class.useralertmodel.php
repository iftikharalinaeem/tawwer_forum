<?php

class UserAlertModel extends Gdn_Model {
   
   public function __construct() {
      parent::__construct('UserAlert');
      $this->PrimaryKey = 'UserID';
   }
   
   public function GetID($ID) {
      $Row = parent::GetID($ID, DATASET_TYPE_ARRAY);
      if (empty($Row))
         return $Row;
      $Row = $this->ExpandAttributes($Row);
      return $Row;
   }
   
   public function GetWhere($Where = FALSE, $OrderFields = '', $OrderDirection = 'asc', $Limit = FALSE, $Offset = FALSE) {
      $Data = parent::GetWhere($Where, $OrderFields, $OrderDirection, $Limit, $Offset);
      $Data->DatasetType(DATASET_TYPE_ARRAY);
      $Data->ExpandAttributes();
      return $Data;
   }
   
   public function SetTimeExpires(&$Alert) {
      $TimeExpires = 0;
      foreach ($Alert as $Name => $Value) {
         if ($Name == 'TimeExpires' || !StringEndsWith($Name, 'Expires'))
            continue;
         if (!$TimeExpires || ($Value && $Value < $TimeExpires))
            $TimeExpires = $Value;
      }
      if (!$TimeExpires)
         $Alert['TimeExpires'] = NULL;
      else
         $Alert['TimeExpires'] = $TimeExpires;
      
      return $Alert['TimeExpires'];
   }
   
   public function Save($FormPostValues, $Settings = FALSE) {
      $Row = $this->CollapseAttributes($FormPostValues);
      
      return parent::Save($Row, $Settings);
   }
}
