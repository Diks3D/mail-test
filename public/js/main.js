jQuery(document).ready(function ($) {
    
    //Init tinyMCE
    tinymce.init({
        selector: ".htmlText",
        menubar: false
    });
    
    // Show popup messsage
    function showMessage(type, message){
        var cssClass = '';
        switch(type) {
            case 'success':
                cssClass = 'alert-success';
                break;
            case 'info':
                cssClass = 'alert-info';
                break;
            case 'warning':
                cssClass = 'alert-warning';
                break;
            case 'error':
                cssClass = 'alert-danger';
                break;
        }
        $('.messageBox').find('.message').text(message);
        $('.messageBox').addClass(cssClass).fadeIn(500);
        setTimeout(function(){
            $('.messageBox').fadeOut(500).removeClass(cssClass).find('.message').text('');
        } , 4000);
    };
    
    //Init jQuery file upoad
    $('#fileupload').fileupload({
        dataType: 'json',
        singleFileUploads: true,
        sequentialUploads: true,
        formData: { action: 'upload-files'},
        done: function (e, data) {
            $.each(data.result.files, function (index, file) {
                $('<p/>').text(file.name).appendTo('.uploadedFilesList');
                var $newHiddenInput = $('<input type="hidden" name="uploadedFiles[]" class="uploadedInput" />');
                $newHiddenInput.attr('id', file.id).attr('value', file.name);
                $newHiddenInput.appendTo('.uploadedFilesInputs');
            });
            setTimeout(function(){
                $('.progress').fadeOut(100);
                $('.progress .progress-bar').css('width', '0%').attr('aria-valuenow', 0).text('');
            } , 3000);
        },
        fail: function (e, data){
            showMessage('error', data.textStatus);
        },
        progressall: function (e, data) {
            $('.progress').fadeIn(200);
            var progress = parseInt(data.loaded / data.total * 100, 10);
            $('.progress .progress-bar').css('width', progress + '%');
            $('.progress .progress-bar').attr('aria-valuenow', progress);
            $('.progress .progress-bar').text(progress + '%');
        }
    });
    
    //Send email content via ajax 
    $('#mailForm #submitBtn').click(function(e){
        e.stopPropagation();

        $('.loader').show();
        
        var uploadedFiles = [];
        var $form = $(this).parents('#mailForm');
        $form.find('.uploadedInput').each(function(index, element){
            uploadedFiles.push({
                id: $(element).attr('id'),
                name: $(element).val()
            });
        });
        
        var formData = {
            mailAddress: $form.find('.mailAddress').val(),
            mailSubjec: $form.find('.mailSubject').val(),
            mailContent: $form.find('.mailContent').html(),
            uploadedFiles: uploadedFiles,
            action: 'send'
        };
        
        var jqxhr = $.post('', formData)
                .done(function (answer) {
                    var data = answer.responseJSON;
                    $('.loader').hide();
                    if (data.code === 0) {
                        showMessage('warning', data.message);
                    } else if (data.code === 1) {
                        //Clear form
                        showMessage('success', data.message);
                        $('#mailForm')[0].reset();
                    }
                })
                .fail(function (answer) {
                    $('.loader').hide();
                    showMessage('error', answer.responseJSON.message);
                });
                
        return false;
    });
    
    //reset form and clear files storage on server
    $('#resetBtn').click(function(){
        $.post('', {action: 'clear'}, function(data){
            $('.uploadedFilesList').html('');
            showMessage('success', data.message);
        });
    });
});