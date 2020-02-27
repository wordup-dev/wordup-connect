
jQuery(document).ready(function(){
    
    jQuery("#wordup-create-project").on("submit", function(e) {

        e.preventDefault();

        var form = jQuery(this);
        form.find('input[type="submit"]').prop( "disabled", true );
 
        jQuery.ajax({
            url:wordupApiSettings.root + 'wordup/v1/projects', 
            data:form.serialize(),
            method: 'POST',
            beforeSend: function ( xhr ) {
                xhr.setRequestHeader( 'X-WP-Nonce', wordupApiSettings.nonce );
            },
            success:function(data){
                window.location = wordupApiSettings.tools;
                //alert(data); // show response from the php script.
            }
        });
    });


    jQuery(".wordup-projects button.install").on("click", function(e) {

        e.preventDefault();

        var link = jQuery(this);
        link.prop( "disabled", true );
        link.text('Installing ...');

        jQuery.ajax({
            url:wordupApiSettings.root + 'wordup/v1/projects/install', 
            data:{'wordup_project':link.attr('data-project')},
            method: 'POST',
            beforeSend: function ( xhr ) {
                xhr.setRequestHeader( 'X-WP-Nonce', wordupApiSettings.nonce );
            },
            success:function(data){
                if(data.success){
                    link.text('Installed');
                }else{
                    link.prop( "disabled", false );
                    link.text('Install');
                    alert('Installation failed. Reason: '+data.logs)
                }
            }
        });
    });

    jQuery(".wordup-projects button.delete").on("click", function(e) {
        e.preventDefault();

        var link = jQuery(this);
        
        link.prop( "disabled", true );

        jQuery.ajax({
            url:wordupApiSettings.root + 'wordup/v1/projects', 
            data:{'wordup_project':link.attr('data-project')},
            method: 'DELETE',
            beforeSend: function ( xhr ) {
                xhr.setRequestHeader( 'X-WP-Nonce', wordupApiSettings.nonce );
            },
            success:function(data){
                link.parents('tr').remove();
            }
        });

    });

});