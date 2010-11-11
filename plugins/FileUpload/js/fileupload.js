function Gdn_Uploaders() {
   
   this.Uploaders = [];
   this.UploaderID = 0;
   this.MaxUploadSize = 1;
   this.UploaderIndex = 0;
   this.MaxUploadSize = gdn.definition('maxuploadsize');
   
   Gdn_Uploaders.prototype.Prepare = function () {
      var Our = this;
      $('div.AttachmentWindow').each(function(i,AttachmentWindow){
         Our.Spawn($(AttachmentWindow));
      });
   }
   
   Gdn_Uploaders.prototype.Spawn = function(AttachmentWindow) {
      if (AttachmentWindow.attr('spawned')) return;
      
      AttachmentWindow.attr('spawned', true);
      this.Uploaders[this.UploaderIndex] = new Gdn_MultiFileUpload(AttachmentWindow, 'UploadAttachment', this);
      this.Uploaders[this.UploaderIndex].Apc((gdn.definition('apcavailable')) ? 'true' : 'false');
      this.Uploaders[this.UploaderIndex].Ready();
      this.UploaderIndex++;
   }
   
   Gdn_Uploaders.prototype.GetFreshID = function() {
      return ++this.UploaderID;
   }
   
   Gdn_Uploaders.prototype.GetUniqID = function() {
      var NewDate = new Date;
      return NewDate.getTime();
   }
   
}

function Gdn_MultiFileUpload(AttachmentWindow, AttachFileRootName, Uploaders) {

   this.Master = Uploaders;
   this.AttachmentWindow = $(AttachmentWindow);
   this.AttachmentWindowHTML = this.AttachmentWindow.html();

   this.AttachFileRootName = AttachFileRootName;
   
   this.UploaderContainer = null;
   this.IFrameContainer = null;
   this.IFrames = {};
   this.TID = 0;
   
   this.APC = false;
      
   Gdn_MultiFileUpload.prototype.Apc = function(ApcStatus) {
      this.APC = ApcStatus;
   }
   
   /**
    * Prepare the form
    *
    * Create an uploader, create the focus() link
    */
   Gdn_MultiFileUpload.prototype.Ready = function() {
      
      this.MaxUploadSize = this.Master.MaxUploadSize;
      this.UniqID = this.Master.GetUniqID();
   
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
      
      // Allow deletes
      $('div.Attachments a.DeleteFile').popup({
         confirm: true,
         followConfirm: false,
         deliveryType: 'JSON',
         afterConfirm: function(json, sender) {
            var MediaData = json.Delete;
            var FileRow = $(sender).parents('tr');
            var FileTable = $(sender).parents('table');
            if (MediaData.Status == 'success') {
               FileRow.remove();
               if (FileTable.find('tr').length == 0) {
                  FileTable.parents('div.Attachments').remove();
               }
            }
         }
      });
        
      this.Reset();
      $('#'+this.CurrentInput).css('opacity',0);
   }
   
   Gdn_MultiFileUpload.prototype.Reset = function() {
      this.AttachmentWindow.html(this.AttachmentWindowHTML);
      
      this.AttachFileLink = this.AttachmentWindow.find('div.AttachFileLink a').first();
      this.FileContainer = this.AttachmentWindow.find('div.AttachFileContainer').first();
      this.CurrentUploader = this.AttachmentWindow.find('div.AttachFileLink div.CurrentUploader').first();
      
      if (this.CurrentInput) {
         this.ShowUploader(true);
         this.RemoveUploader(this.CurrentInput);
      }
      
      this.MyFiles = [];
      this.ProgressBars = {};
      this.CurrentInput = null;
      
      // Create a new uploader
      var UploaderID = this.NewUploader();
      
      // Attach onClick event to the Attach File button
      var Click = jQuery.proxy(this.ShowUploader, this);
      this.AttachFileLink.click(function(){
         Click();
         return false;
      });
      this.AttachFileLink.parents('form').bind('complete',jQuery.proxy(this.Reset,this));
   }
   
   Gdn_MultiFileUpload.prototype.NewUploader = function() {
      var NewUploaderID = null; var AutoShow = true;
      if (this.CurrentInput == null)
         AutoShow = false;
      
      NewUploaderID = this.Master.GetFreshID();
      NewUploaderID = [this.AttachFileRootName,NewUploaderID].join('_');
      
      var UploaderForm = document.createElement('form');
      var Action = ['post','upload',NewUploaderID];

      UploaderForm.enctype = 'multipart/form-data';
      UploaderForm.method = 'POST';
      UploaderForm.className = 'FileUpload';
      UploaderForm.action = gdn.url(Action.join('/'));
      var IFrameName = this.NewFrame(NewUploaderID);
      var FormName = IFrameName+'_form';
      UploaderForm.id = FormName;
      UploaderForm.target = IFrameName;
      
      if (this.APC) {
         var APCNotifier = document.createElement('input');
         APCNotifier.type = 'hidden';
         APCNotifier.name = 'APC_UPLOAD_PROGRESS';
         APCNotifier.id = NewUploaderID+'_apckey';
         APCNotifier.value = this.UniqID + '_' + NewUploaderID;
         $(UploaderForm).append(APCNotifier);
      }
            
      var NewUploader   = document.createElement('input');
      NewUploader.type  = 'file';
      NewUploader.name  = NewUploaderID;
      NewUploader.id    = NewUploaderID;
      NewUploader.className = '';
      NewUploader.rel = FormName;
      $(UploaderForm).append(NewUploader);
      
      var MaxUploadSize = document.createElement('input');
      MaxUploadSize.type = 'hidden';
      MaxUploadSize.name = 'MAX_UPLOAD_SIZE';
      MaxUploadSize.value = this.MaxUploadSize;
      $(UploaderForm).append(MaxUploadSize);
      
      this.CurrentUploader.append(UploaderForm);
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
      
      if (AutoShow)
         this.ShowUploader(true);
      $('#'+this.CurrentInput).change(jQuery.proxy(this.DispatchCurrentUploader,this));
      
      return NewUploaderID;
   }
   

   Gdn_MultiFileUpload.prototype.ShowUploader = function(NoAnimate) {
   
      var UploaderElement = this.CurrentUploader;
   
      if (typeof(NoAnimate) == 'object') {
         UploaderElement.animate({
            'height': '24px'
         },300,jQuery.proxy(function(){
            UploaderElement.find('form input[type=file]').css('display', 'block');
            UploaderElement.find('form input[type=file]').animate({
               'opacity': 1
            });
         },this));
      } else {
         UploaderElement.animate({
            'height': '24px'
         },0,function(){
            UploaderElement.find('form input[type=file]').css('display','block');
            UploaderElement.find('form input[type=file]').css('opacity',1);
         });
      }
   }
   
   // Create a new named iframe to which our uploads can be submitted
   Gdn_MultiFileUpload.prototype.NewFrame = function(TargetUploaderID) {
      var IFrameName = 'frm'+Math.floor(Math.random() * 99999);
      var ContainerDiv = document.createElement('div');
      
      var isOpera, isIE = false;
      if (typeof(window.opera) != 'undefined') {
         isOpera = true;
         console.log('found opera');
      }
      
      if (!isOpera && navigator.userAgent.indexOf('Internet Explorer') >= 0) {
         console.log(navigator.userAgent.indexOf('Internet Explorer'));
         isIE = true;
         console.log('found IE');
      }
      
      if (isIE) {
         var IFrame = document.createElement('<iframe name="'+IFrameName+'" id="'+IFrameName+'">');
      } else {
         var IFrame = document.createElement('iframe');
         IFrame.name = IFrameName;
         IFrame.id = IFrameName;
      }
      
      $(IFrame).style = "display:none;";
      IFrame.src = "about:blank";
      
      
      $(ContainerDiv).append(IFrame);
      $(this.IFrameContainer).append(ContainerDiv);
      
      this.IFrames[IFrameName] = {ready:'no'};
      
      // Re-target just to be safe
      $('#'+IFrameName).load(jQuery.proxy(function(){ this.UploadComplete(IFrameName,TargetUploaderID); }, this));
      
      return IFrameName;
   }
   
   // Submit the form parent of the current uploader and hide the current uploader's input
   Gdn_MultiFileUpload.prototype.DispatchCurrentUploader = function(ChangeEvent) {
      this.UploaderContainer.append($('form#'+$('#'+this.CurrentInput).attr('rel')));
      
      var Target = $(ChangeEvent.target);
      $('#'+Target.attr('rel')).append(Target);
      var UploaderID = Target.attr('id');
      this.RememberFile(Target);
      var IFrameName = Target.parent().attr('target');
      this.IFrames[IFrameName].ready = 'yes';
      
      Target.parent().submit();
      this.NewUploader();
   }
   
   Gdn_MultiFileUpload.prototype.RememberFile = function(FileInput) {
      var FileName = FileInput.val();
      var UploaderID = FileInput.attr('id');
      this.ProgressBars[UploaderID].Filename = FileName
      
      // Handle the control (remove style, hide)
      FileInput.attr('style','');
      FileInput.hide();
      
      // Handle the file list UI
      var PrototypeFileAttachment = $(this.FileContainer.find('div.PrototypicalAttachment')[0]).clone();
      var FileNameDiv = $(PrototypeFileAttachment).find('div.FileName');
      var FileSizeDiv = $(PrototypeFileAttachment).find('div.FileSize');
      var ProgressDiv = $(PrototypeFileAttachment).find('div.UploadProgress');
      $(FileNameDiv).html(FileName);
      $(FileSizeDiv).html('');
      $($(ProgressDiv).find('div.Background')).css('width','0px');
      
      var FileListingID = [FileInput.attr('id'),'listing'].join('_');
      PrototypeFileAttachment.attr('id', FileListingID);
      PrototypeFileAttachment.css('display', 'block');
      PrototypeFileAttachment.appendTo(this.FileContainer);
      // PrototypeFileAttachment.css('display','table-row');
      
      this.Progress(FileInput.attr('id'));
      
      // Return the old ID
      return UploaderID;
   }
   
   Gdn_MultiFileUpload.prototype.Progress = function(Data, ResponseStatus, XMLResponse) {
      if (!this.APC) return;
      var ExecuteApcLookup = this.APC;
   
      if (this.ProgressBars[Data]) {
         var ApcKey = this.ProgressBars[Data].ApcKey;
         var Progress = this.ProgressBars[Data].Progress;
         
         var UploaderID = Data;
      } else {
         if (!Data) return;
         
         var JData = jQuery.parseJSON(Data);
         if (JData && JData.Progress) {

            var JProgress = JData.Progress;
            var UploaderID = JProgress.uploader;
            
            if (!this.ProgressBars[UploaderID]) return;
            if (this.ProgressBars[UploaderID].Complete == true) return;
            
            if (JProgress.apc == 'no') {
               return;
            }
            
            var Progress = JProgress.progress;
            this.ProgressBars[UploaderID].Progress = Progress;
            this.ProgressBars[UploaderID].Total = JProgress.total;

            var FileListing = $('#'+UploaderID+'_listing');

            // Update the filesize
            if (JProgress.total != null && JProgress.total != -1) {
               $(FileListing.find('div.FileSize')).html(JProgress.format_total);
            }
            
            // Update progress bar
            if (!this.ProgressBars[UploaderID].Complete) {
               var UploadProgress = FileListing.find('div.UploadProgress');
               var ProgressForeground = FileListing.find('div.UploadProgress div.Foreground');
               var ProgressBackground = FileListing.find('div.UploadProgress div.Background');
               ProgressForeground.html('<strong>Uploading:</strong> ' + Math.ceil(Progress)+'%');
               ProgressBackground.css('width', ((Progress * $(UploadProgress).width()) / 100)+'px');
               // if (Progress >= 15)
               //    ProgressBar.html(Math.ceil(Progress)+'%');
            }
            
         }
         
         if (ExecuteApcLookup) {
            // Wait 100 MS and then trigger another request
            Progress = parseInt(Progress);
            if ((!this.ProgressBars[UploaderID].Complete && Progress < 100) || (this.ProgressBars[UploaderID].Complete && Progress <= 0)) {
               this.TID = this.ProgressBars[UploaderID].TimerID = setTimeout(jQuery.proxy(function(){ this.Progress(UploaderID); }, this), 100);
            }
         }
         
         return;
      }
   
      if (ExecuteApcLookup) {
         var Action = ['post','checkupload',ApcKey,this.ProgressBars[UploaderID].Progress];
         var FinalURL = gdn.url(Action.join('/')+'?randval='+Math.random());
         
         jQuery.ajax({
            url:FinalURL,
            type:'GET',
            async:true,
            //data:{'Previous':Progress},
            success:jQuery.proxy(this.Progress, this)
         });
      }
   }
   
   Gdn_MultiFileUpload.prototype.UploadComplete = function(IFrameName, TargetUploaderID) {
      if (this.IFrames[IFrameName].ready != 'yes') {
         this.IFrames[IFrameName].ready = 'yes';
         return;
      }
      
      var IFR = document.getElementById(IFrameName);
      var Response = IFR.contentWindow.document.body.innerHTML;
      
      var UploadResultStatus = 'fail';
      var FailReason = 'An unknown error occured.';
      
      var JResponse = jQuery.parseJSON(Response);
      if (JResponse && JResponse.MediaResponse) {
      
         if (JResponse.MediaResponse.Status == 'success') {
            UploadResultStatus = 'success';
            // SUCCESS
            
            var Filename = JResponse.MediaResponse.Filename;
            if (!this.ProgressBars[TargetUploaderID]) return;
            if (this.ProgressBars[TargetUploaderID].Filename != Filename) return;
            
            var MediaID = JResponse.MediaResponse.MediaID;
            this.ProgressBars[TargetUploaderID].Complete = true;
            this.MyFiles[MediaID] = Filename;
            this.RemoveUploader(TargetUploaderID);
            var EnableMe = document.createElement('input');
            EnableMe.type = 'checkbox';
            EnableMe.name = 'AttachedUploads[]';
            EnableMe.value = MediaID;
            EnableMe.checked = 'checked';
            
            var TrackAll = document.createElement('input');
            TrackAll.type = 'hidden';
            TrackAll.name = 'AllUploads[]';
            TrackAll.value = MediaID;
            
            var FileListing = $('#'+[TargetUploaderID,'listing'].join('_'));
            
            // Update the filesize
            if (JResponse.MediaResponse.Filesize != null) {
               $(FileListing.find('div.FileSize')).html(JResponse.MediaResponse.FormatFilesize);
            }
            
            $(FileListing.find('div.FileOptions')).append(EnableMe);
            $(FileListing.find('div.FileOptions')).append(TrackAll);
            $(FileListing.find('div.UploadProgress')).remove();
            
         } else {
            // FAILURE
            FailReason = JResponse.MediaResponse.StrError;
            this.ProgressBars[TargetUploaderID].Complete = true;
            
         }
      }
      
      if (UploadResultStatus == 'fail') {
         clearTimeout(this.ProgressBars[TargetUploaderID].TimerID);
         this.RemoveUploader(TargetUploaderID);
         
         var FileListing = $('#'+[TargetUploaderID,'listing'].join('_'));
         FileListing.html("File upload failed. Reason: "+FailReason);
         FileListing.css({
            'background-color':'#ffbfbf',
            'color':'#a70000',
            'padding-left':'20px',
            'background-position':'8px center',
            'cursor':'pointer'
         });
         FileListing.click(function(){FileListing.remove();});
         setTimeout(function(){
            FileListing.fadeTo(1500,0,function(){ FileListing.animate({'height':0},600,function(){ FileListing.remove(); }) });
         },6000);
         delete this.ProgressBars[TargetUploaderID];
      }
   }
   
   Gdn_MultiFileUpload.prototype.RemoveUploader = function(UploaderID) {
      var TargetFrame = $('#'+this.ProgressBars[UploaderID].Target);
      var TargetForm = $('#'+this.ProgressBars[UploaderID].Target+'_form');
      
      TargetFrame.parent().remove();
      TargetForm.remove();
      
      // If a progress request is pending, cancel it
      //clearTimeout(this.ProgressBars[UploaderID].TimerID);
   }
   
   Gdn_MultiFileUpload.prototype.Stop = function() {
      clearTimeout(this.TID);
   }

}

var GdnUploaders = null;
jQuery(document).ready(function(){
   GdnUploaders = new Gdn_Uploaders();
   GdnUploaders.Prepare()
});