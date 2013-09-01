<?php

class UserNoteModel extends Gdn_Model {
   
   public function __construct() {
      parent::__construct('UserNote');
   }
   
   public function Calculate(&$Data) {
      Gdn::UserModel()->JoinUsers($Data, array('InsertUserID'));
      foreach ($Data as &$Row) {
         $Row['Body'] = Gdn_Format::To($Row['Body'], $Row['Format']);
      }
   }
   
   public function GetID($ID) {
      $Row = parent::GetID($ID, DATASET_TYPE_ARRAY);
      $Row = $this->ExpandAttributes($Row);
      return $Row;
   }
   
   public function GetWhere($Where = FALSE, $OrderFields = '', $OrderDirection = 'asc', $Limit = FALSE, $Offset = FALSE) {
      $Data = parent::GetWhere($Where, $OrderFields, $OrderDirection, $Limit, $Offset);
      $Data->DatasetType(DATASET_TYPE_ARRAY);
      $Data->ExpandAttributes();
      return $Data;
   }
   
   public function Save($FormPostValues, $Settings = FALSE) {
      $Row = $this->CollapseAttributes($FormPostValues);
      
      return parent::Save($Row, $Settings);
   }
   
   public function SetField($RowID, $Name, $Value = NULL) {
      if (!is_array($Name))
         $Name = array($Name => $Value);
      
      $this->DefineSchema();
      $Fields = $this->Schema->Fields();
      $InSchema = array_intersect_key($Name, $Fields);
      $NotInSchema = array_diff_key($Name, $InSchema);
      
      if (empty($NotInSchema)) {
         return parent::SetField($RowID, $Name);
      } else {
         $Row = $this->SQL->Select('Attributes')->GetWhere('UserNote', array('UserNoteID' => $RowID))->FirstRow(DATASET_TYPE_ARRAY);
         if (isset($Row['Attributes'])) {
            $Attributes = @unserialize($Row['Attributes']);
            if (is_array($Attributes))
               $Attributes = array_merge($Attributes, $NotInSchema);
            else
               $Attributes = $NotInSchema;
         } else {
            $Attributes = $NotInSchema;
         }
         $InSchema['Attributes'] = $Attributes;
         return parent::SetField($RowID, $InSchema);
      }
   }
}
