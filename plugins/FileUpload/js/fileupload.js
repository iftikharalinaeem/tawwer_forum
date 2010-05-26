var Gdn_MultiFileUpload = Class.create({

   init: function(Action, AttachmentWindow, FileContainerID, AttachFileLinkID, AttachFileRootName, MaxFiles, UniqID) {
      this.AttachmentWindow = AttachmentWindow;
      this.AttachmentWindowHTML = $('#'+AttachmentWindow).html();
   
      this.ActionRoot = Action;
      this.FileContainerID = FileContainerID;
      this.AttachFileLinkID = AttachFileLinkID;
      this.AttachFileRootName = AttachFileRootName;
      this.MaxFiles = MaxFiles;
      this.UniqID = UniqID;
      
      this.UploaderContainer = null;
      this.IFrameContainer = null;
      
      $(document).ready(jQuery.proxy(this.Ready, this));
   },
   
   Reset: function() {
      $('#'+this.AttachmentWindow).html(this.AttachmentWindowHTML);
      
      if (this.CurrentInput) {
         this.RemoveUploader(this.CurrentInput);
      }
      
      this.MyFiles = [];
      this.ProgressBars = {};
      this.CurrentInput = null;
      
      // Create a new uploader
      var UploaderID = this.NewUploader();
      
      // Attach onClick event to the Attach File button
      //$('#'+this.AttachFileLinkID).click(jQuery.proxy(this.AlignUploader, this));
      
   },
   
   /**
    * Prepare the form
    *
    * Create an uploader, create the focus() link
    */
   Ready: function() {
   
      // Create uploader container
      var UploaderContainer = document.createElement('div');
      var UploaderContainerID = 'ctnr' + Math.floor(Math.random() * 99999);
      UploaderContainer.id = UploaderContainerID;
      $(document.body).append(UploaderContainer);
      this.UploaderContainer = $('#'+UploaderContainerID);
      
      // Create iframe container
      var IFrameContainer = document.createElement('div');
      var IFrameContainerID = 'frmz' + Math.floor(Math.random() * 99999);
      IFrameContainer.id = IFrameContainerID;
      $(document.body).append(IFrameContainer);
      this.IFrameContainer = $('#'+IFrameContainerID);
      this.IFrameContainer.hide();
      
      this.Reset();
      
      $('#'+this.AttachFileLinkID).parents('form').bind('complete',jQuery.proxy(this.Reset,this));
   },
   
   FocusCurrentUploader: function() {
      $('#'+this.CurrentInput).click();
   },
   
   NewUploader: function() {
      var NewUploaderID = null;
      if (this.CurrentInput == null) {
         NewUploaderID = 1;
      } else {
         NewUploaderID = parseInt(this.CurrentInput.split('_').pop()) + 1;
      }
      
      NewUploaderID = [this.AttachFileRootName,NewUploaderID].join('_');
      
      var UploaderForm = document.createElement('form');
      var Action = ['post','upload',NewUploaderID];
      if (this.ActionRoot)
         Action.unshift(this.ActionRoot);
      Action.unshift('');

      UploaderForm.enctype = 'multipart/form-data';
      UploaderForm.method = 'POST';
      UploaderForm.action = Action.join('/');
      var IFrameName = this.NewFrame(NewUploaderID);
      var FormName = IFrameName+'_form';
      UploaderForm.id = FormName;
      UploaderForm.target = IFrameName;
      
      var NewUploader   = document.createElement('input');
      NewUploader.type  = 'file';
      NewUploader.name  = NewUploaderID;
      NewUploader.id    = NewUploaderID;
      NewUploader.className = 'HiddenFileInput';
      $(NewUploader).fadeTo(0,0);
      $(UploaderForm).append(NewUploader);
      this.AlignUploader(NewUploader);
      
      var APCNotifier = document.createElement('input');
      APCNotifier.type = 'hidden';
      APCNotifier.name = 'APC_UPLOAD_PROGRESS';
      APCNotifier.id = NewUploaderID+'_apckey';
      APCNotifier.value = this.UniqID + '_' + NewUploaderID;
      $(UploaderForm).append(APCNotifier);
      
      this.UploaderContainer.append(UploaderForm);
      this.CurrentInput = NewUploaderID;
      this.ProgressBars[NewUploaderID] = {
         'Target':   IFrameName,
         'Filename': '',
         'TimerID':  0,
         'ApcKey':   this.UniqID + '_' + NewUploaderID,
         'Progress': 0,
         'Size':     0,
         'Complete': false
      };
      $('#'+this.CurrentInput).change(jQuery.proxy(this.DispatchCurrentUploader,this));
   },
   
   AlignUploader: function(Uploader) {
      var Offset = $('#'+this.AttachFileLinkID).offset();
      $(Uploader).offset(Offset);
      $(Uploader).css('top', (parseInt(Offset.top) - 5)+'px');
      $(Uploader).css('width', parseInt($('#'+this.AttachFileLinkID).width())+'px');
   },
   
   // Create a new named iframe to which our uploads can be submitted
   NewFrame: function(TargetUploaderID) {
		var IFrameName = 'frm' + Math.floor(Math.random() * 99999);
		var ContainerDiv = document.createElement('div');
		var IFrame = document.createElement('iframe');
		$(IFrame).style = "display:none;";
		IFrame.src = "about:blank";
		IFrame.id = IFrameName;
		IFrame.name = IFrameName;
		$(ContainerDiv).append(IFrame);
		$(this.IFrameContainer).append(ContainerDiv);
      
      // Re-target just to be safe
		$(IFrame).load(jQuery.proxy(function(){ this.UploadComplete(IFrameName,TargetUploaderID); }, this));
      
		return IFrameName;
   },
   
   // Submit the form parent of the current uploader and hide the current uploader's input
   DispatchCurrentUploader: function(ChangeEvent) {
      var Target = $(ChangeEvent.target);
      Target.parent().submit();
      var UploaderID = Target.attr('id');
      
      this.RememberFile(Target);
      this.NewUploader();
   },
   
   RememberFile: function(FileInput) {
      var FileName = FileInput.val();
      var UploaderID = FileInput.attr('id');
      this.ProgressBars[UploaderID].Filename = FileName
      
      // Handle the control (remove style, hide)
      FileInput.attr('style','');
      FileInput.hide();
      
      // Handle the file list UI
      var FileListContainer = $('#'+this.FileContainerID);
      var PrototypeFileListing = $($(FileListContainer).find('tr')[0]).clone();
      var PrototypeFileListingElements = PrototypeFileListing.find('td');
      $(PrototypeFileListingElements[2]).html(FileName);
      $(PrototypeFileListingElements[3]).html('? Kb');
      $($(PrototypeFileListingElements[4]).find('div')[1]).css('width','0px');
      
      var FileListingID = [FileInput.attr('id'),'listing'].join('_');
      PrototypeFileListing.attr('id', FileListingID);
      PrototypeFileListing.appendTo(FileListContainer);
      PrototypeFileListing.css('display','table-row');
      
      this.Progress(FileInput.attr('id'));
      
      // Return the old ID
      return UploaderID;
   },
   
   Progress: function(UploaderID, ResponseStatus, XMLResponse) {
      if (this.ProgressBars[UploaderID]) {
      
         // Timeout response call
         var ApcKey = this.ProgressBars[UploaderID].ApcKey;
         var Progress = this.ProgressBars[UploaderID].Progress;
      } else {
         var Data = UploaderID;
         if (!Data) return;
         
         var JData = jQuery.parseJSON(Data);
         if (JData && JData.Progress) {
            var JProgress = JData.Progress;
            var Progress = JProgress.progress;
            var UploaderID = JProgress.uploader;
            
            this.ProgressBars[UploaderID].Progress = Progress;
            this.ProgressBars[UploaderID].Total = JProgress.total;
            
            if (!this.ProgressBars[UploaderID].Complete) {
               // Update progress bar
               var FileListing = $('#'+UploaderID+'_listing');
               $(FileListing.find('td')[3]).html(JProgress.format_total);
               var ProgressBar = FileListing.find('div.ProgressTicker');
               ProgressBar.css('width',Progress+'%');
            }
            
         }
         
         // Wait 100 MS and then trigger another request
         if (Progress && parseInt(Progress) < 100) {
            this.ProgressBars[UploaderID].TimerID = setTimeout(jQuery.proxy(function(){ this.Progress(UploaderID); }, this), 1000);
         }
         return;
      }
   
      var Action = ['post','checkupload',ApcKey];
      if (this.ActionRoot)
         Action.unshift(this.ActionRoot);
      Action.unshift('');
      jQuery.ajax({
         url:Action.join('/'),
         type:'POST',
         //data:{'Previous':Progress},
         success:jQuery.proxy(this.Progress, this)
      });
   },
   
   UploadComplete: function(IFrameName, TargetUploaderID) {
      var IFR = document.getElementById(IFrameName);
      var Response = IFR.contentWindow.document.body.innerHTML;
      if (!Response) return;
      
      var JResponse = jQuery.parseJSON(Response);
      if (JResponse && JResponse.MediaResponse) {
         var Filename = JResponse.MediaResponse.Filename;
         var MediaID = JResponse.MediaResponse.MediaID;
         
         if (this.ProgressBars[TargetUploaderID]) {
            if (this.ProgressBars[TargetUploaderID].Filename == Filename) {
               this.ProgressBars[TargetUploaderID].Complete = true;
               this.MyFiles[MediaID] = Filename;
               this.RemoveUploader(TargetUploaderID);
               var EnableMe = document.createElement('input');
               EnableMe.type = 'checkbox';
               EnableMe.name = 'AttachedUploads[]';
               EnableMe.value = MediaID;
               EnableMe.checked = 'checked';
               
               var FileListing = $('#'+[TargetUploaderID,'listing'].join('_'));
               $(FileListing.find('td')[1]).append(EnableMe);
               $(FileListing.find('td')[4]).find('div').remove();
            }
         }
      }
   },
   
   RemoveUploader: function(UploaderID) {
      var TargetFrame = $('#'+this.ProgressBars[UploaderID].Target);
      var TargetForm = $('#'+this.ProgressBars[UploaderID].Target+'_form');
      
      TargetFrame.parent().remove();
      TargetForm.remove();
      
      // If a progress request is pending, cancel it
      clearTimeout(this.ProgressBars[UploaderID].TimerID);
   }

});