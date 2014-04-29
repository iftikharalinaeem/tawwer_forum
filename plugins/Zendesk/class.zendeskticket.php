<?php if (!defined('APPLICATION')) exit();

class ZendeskTicket {

   public $ID;
   public $Subject;
   public $Description;
   public $Status;
   public $CreatedAt;
   public $UpdatedAt;

   public function SetFromZendeskResponse($Response) {
      $this->ID = $Response->ticket->id;
      $this->Status = $Response->ticket->status;
      $this->Subject = $Response->ticket->subject;
      $this->Description = $Response->ticket->description;
      $this->CreatedAt = $Response->ticket->created_at;
      $this->UpdatedAt = $Response->ticket->updated_at;
   }

   public function Set($property, $value) {
      $this->$property = $value;
   }

   public function Get($property) {
      return $this->$property;
   }

   public function GetStatusForDisplay() {
      return ucwords($this->Status);
   }

   public function GetCreatedAtForDisplay() {
      return Gdn_Format::DateFull(strtotime($this->CreatedAt));
   }

}