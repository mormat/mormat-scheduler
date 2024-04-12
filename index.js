(function($) {

    $(document).ready(function() {

        $('.mormat_scheduler').each(function() {

            var params = $(this).data('params');
            
            if (params.height) {
                $(this).css('height', params.height);
            }
            
            var ajaxData = {
                namespace: params['events_namespace']
            };
            
            var props = {
                initialDate: params['initial_date'],
                viewMode:    params['default_view'],
                editable:    params['editable'],
                draggable:   params['draggable'],
                events:      params['events'],
                locale:      params['locale'].replace('_', '-'),
                labels:      params['labels'],
                height:     'auto',
                width:      'auto',
                onEventCreate: function(event) {       
                    
                    $.post({
                        url:  params.urls['save_event'],
                        data: $.extend( {}, event.getData(), ajaxData, { id: null } ),
                        success: function( res ) {
                            event.id = res.data.id;    
                        }
                    });
                },
                onEventUpdate: function(event) {
                    $.post({
                        url:  params.urls['save_event'],
                        data: $.extend( {}, event.getData(), ajaxData )
                    });
                },
                onEventDelete: function(event) {
                    $.post({
                        url:  params.urls['delete_event'],
                        data: $.extend( {}, event.getData(), ajaxData )
                    });
                }
            };
            
            mormat_standalone_scheduler.renderScheduler(this, props);
            
        }).removeAttr('data-params');

    });

})(jQuery);

