/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

(function () {
    'use strict';
    
    var workspaceId = parseInt($('#hometab-datas-box').data('workspace-id'));
    var currentHomeTabId = parseInt($('#hometab-datas-box').data('hometab-id'));
    var initWidgetsPosition =
        parseInt($('#widgets-hometab-datas-box').data('init-widgets-position')) === 1;
    var currentWidgetInstanceId;
    
    function persistWidgetsPosition()
    {
        var wdcIds = [];
        var datas = {};
        var i = 0;
        
        $('.grid-stack-item').each(function () {
                var wdcId = $(this).data('widget-display-config-id');
            if (wdcId !== undefined) {
                var column = $(this).attr('data-gs-x');
                var row = $(this).attr('data-gs-y');
                var width = $(this).attr('data-gs-width');
                var height = $(this).attr('data-gs-height');
                wdcIds[i] = wdcId;

                if (datas[wdcId] === undefined) {
                    datas[wdcId] = {};
                }
                datas[wdcId]['row'] = row;
                datas[wdcId]['column'] = column;
                datas[wdcId]['width'] = width;
                datas[wdcId]['height'] = height;
                i++;
            }
        });
        
        if (wdcIds.length > 0) {
            var parameters = {};
            parameters.wdcIds = wdcIds;
            var route = Routing.generate(
                'claro_workspace_update_widgets_display_config',
                {'workspace': workspaceId}
            );
            route += '?' + $.param(parameters);

            $.ajax({
                url: route,
                type: 'POST',
                data: datas
            });
        }
    }
    
    $(document).ready(function () {
        
        if (initWidgetsPosition) {
            persistWidgetsPosition();
        }
    });
    
    $('#workspace-home-content').on('click', '#add-hometab-btn', function () {
        window.Claroline.Modal.displayForm(
            Routing.generate(
                'claro_workspace_home_tab_create_form',
                {'workspace' : workspaceId}
            ),
            openHomeTab,
            function() {}
        );
    });
    
    $('#workspace-home-content').on('click', '.edit-hometab-btn', function (e) {
        e.preventDefault();
        var homeTabElement= $(this).parents('.hometab-element');
        var homeTabId = homeTabElement.data('hometab-id');
        var homeTabConfigId = homeTabElement.data('hometab-config-id');
        
        window.Claroline.Modal.displayForm(
            Routing.generate(
                'claro_workspace_home_tab_edit_form',
                {
                    'workspace': workspaceId,
                    'homeTab': homeTabId,
                    'homeTabConfig': homeTabConfigId
                }
            ),
            renameHomeTab,
            function() {}
        );
    });

    $('#workspace-home-content').on('click', '.delete-hometab-btn', function (e) {
        e.preventDefault();
        var homeTabElement = $(this).parents('.hometab-element');
        var homeTabId = homeTabElement.data('hometab-id');

        window.Claroline.Modal.confirmRequest(
            Routing.generate(
                'claro_workspace_home_tab_delete',
                {'workspace': workspaceId, 'homeTab': homeTabId}
            ),
            removeHomeTab,
            homeTabId,
            Translator.trans('home_tab_delete_confirm_message', {}, 'platform'),
            Translator.trans('home_tab_delete_confirm_title', {}, 'platform')
        );
    });

    $('#workspace-home-content').on('click', '.bookmark-hometab-btn', function (e) {
        e.preventDefault();
        var homeTabElement = $(this).parents('.hometab-element');
        var homeTabId = homeTabElement.data('hometab-id');

        window.Claroline.Modal.confirmRequest(
            Routing.generate(
                'claro_workspace_home_tab_bookmark',
                {'workspace': workspaceId, 'homeTab': homeTabId}
            ),
            doNothing,
            null,
            Translator.trans('home_tab_bookmark_confirm_message', {}, 'platform'),
            Translator.trans('home_tab_bookmark_confirm_title', {}, 'platform')
        );
    });
    
    $('#workspace-hometabs-list').sortable({
        items: '.movable-hometab',
        cursor: 'move'
    });

    $('#workspace-hometabs-list').on('sortupdate', function (event, ui) {

        if (this === ui.item.parents('#workspace-hometabs-list')[0]) {
            var hcId = $(ui.item).data('hometab-config-id');
            var nextHcId = -1;
            var nextElement = $(ui.item).next();
            
            if (nextElement !== undefined && nextElement.hasClass('movable-hometab')) {
                nextHcId = nextElement.data('hometab-config-id');
            }
            
            $.ajax({
                url: Routing.generate(
                    'claro_workspace_home_tab_config_reorder',
                    {
                        'workspace': workspaceId,
                        'homeTabConfig': hcId,
                        'nextHomeTabConfigId': nextHcId
                    }
                ),
                type: 'POST'
            });
        }
    });
    
    $('#widgets-section').on('click', '#create-widget-instance', function () {
        window.Claroline.Modal.displayForm(
            Routing.generate(
                'claro_workspace_widget_instance_create_form',
                {'workspace': workspaceId, 'homeTab': currentHomeTabId}
            ),
            addWidget,
            function() {}
        );
    });
    
    $('#widgets-list-panel').on('click', '.edit-widget-btn', function () {
        var widgetHomeTabId = $(this).data('widget-hometab-config-id');
        var widgetDisplayConfigId = $(this).data('widget-display-config-id');
        var widgetInstanceId = $(this).data('widget-instance-id');
        
        window.Claroline.Modal.displayForm(
            Routing.generate(
                'claro_workspace_widget_config_edit_form',
                {
                    'workspace': workspaceId,
                    'widgetInstance': widgetInstanceId,
                    'widgetHomeTabConfig': widgetHomeTabId,
                    'widgetDisplayConfig': widgetDisplayConfigId
                }
            ),
            updateWidget,
            function() {}
        );
    });
    
    $('#widgets-list-panel').on('click', '.edit-widget-content-btn', function () {
        currentWidgetInstanceId = $(this).data('widget-instance-id');
        var widgetInstanceName = $(this).data('widget-instance-name');
        
        $.ajax({
            url: Routing.generate(
                'claro_workspace_widget_configuration',
                {'workspace': workspaceId, 'widgetInstance': currentWidgetInstanceId}
            ),
            type: 'GET',
            success: function (datas) {
                $('#widget-content-config-modal-title').html(widgetInstanceName);
                $('#widget-content-config-modal-body').html(datas);
                $('#widget-content-config-modal-box').modal('show');
            }
        });
    });

    // Click on OK button of the configuration Widget form
    $('#widget-content-config-modal-box').on('submit', 'form', function (e) {
        e.stopImmediatePropagation();
        e.preventDefault();
        var form = e.currentTarget;
        var action = $(e.currentTarget).attr('action');
        var formData = new FormData(form);
        
        $.ajax({
            url: action,
            data: formData,
            type: 'POST',
            processData: false,
            contentType: false,
            complete: function (jqXHR) {
                switch (jqXHR.status) {
                    case 204:
                        $.ajax({
                            url: Routing.generate(
                                'claro_widget_content',
                                {'widgetInstance': currentWidgetInstanceId}
                            ),
                            type: 'GET',
                            success: function (datas) {
                                $('#widget-instance-content-' + currentWidgetInstanceId).html(datas);
                                $('#widget-content-config-modal-body').empty();
                                $('#widget-content-config-modal-box').modal('hide');
                            }
                        });
                        break;
                    default:
                        $('#widget-instance-content-' + currentWidgetInstanceId).html(jqXHR.responseText);
                }
            }
        });
    });

    // Click on CANCEL button of the configuration Widget form
    $('#widget-content-config-modal-box').on('click', '.claro-widget-form-cancel', function (e) {
        e.stopImmediatePropagation();
        e.preventDefault();
        $('#widget-content-config-modal-body').empty();
        $('#widget-content-config-modal-box').modal('hide');
    });
    
    $('#widgets-list-panel').on('click', '.close-widget-btn', function () {
        var whcId = $(this).data('widget-hometab-config-id');
        window.Claroline.Modal.confirmRequest(
            Routing.generate(
                'claro_workspace_widget_home_tab_config_delete',
                {'workspace': workspaceId, 'widgetHomeTabConfig': whcId}
            ),
            removeWidget,
            whcId,
            Translator.trans('widget_home_tab_delete_confirm_message', {}, 'platform'),
            Translator.trans('widget_home_tab_delete_confirm_title', {}, 'platform')
        );
    });
    
    $('body').on('focus', '#widget_display_config_form_color', function () {
        $(this).colorpicker();
    });
    
    $('#widgets-list-panel').on('change', function (e, items) {
        var wdcIds = [];
        var datas = {};
        
        for (var i = 0; i < items.length; i++) {
            
            if (items[i]['el'] !== undefined) {
                var wdcId = items[i]['el'].data('widget-display-config-id');
                var column = items[i]['el'].attr('data-gs-x');
                var row = items[i]['el'].attr('data-gs-y');
                var width = items[i]['el'].attr('data-gs-width');
                var height = items[i]['el'].attr('data-gs-height');
                wdcIds[i] = wdcId;
                
                if (datas[wdcId] === undefined) {
                    datas[wdcId] = {};
                }
                datas[wdcId]['row'] = row;
                datas[wdcId]['column'] = column;
                datas[wdcId]['width'] = width;
                datas[wdcId]['height'] = height;
            }
        }
        
        if (wdcIds.length > 0) {
            var parameters = {};
            parameters.wdcIds = wdcIds;
            var route = Routing.generate(
                'claro_workspace_update_widgets_display_config',
                {'workspace': workspaceId}
            );
            route += '?' + $.param(parameters);

            $.ajax({
                url: route,
                type: 'POST',
                data: datas
            });
        }
    });

    var openHomeTab = function (homeTabId) {
        window.location = Routing.generate(
            'claro_display_workspace_home_tab',
            {'workspace': workspaceId, 'tabId': homeTabId}
        );
    };

    var renameHomeTab = function (datas) {
        var id = datas['id'];
        var name = datas['name'];
        var visibility = datas['visibility'];
        $('#hometab-name-' + id).html(name);
        
        if (visibility === 'hidden') {
            $('#hometab-name-' + id).addClass('strike');
        } else {
            $('#hometab-name-' + id).removeClass('strike');
        }
    };
    
    var removeHomeTab = function (event, homeTabId) {
        
        if (currentHomeTabId === parseInt(homeTabId)) {
            window.location.reload();
        } else {
            $('#hometab-element-' + homeTabId).remove();
        }
    };
    
    var removeWidget = function (event, widgetHomeTabConfigId) {
        var widgetElement = $('#widget-element-' + widgetHomeTabConfigId);
        var grid = $('.grid-stack').data('gridstack');
        grid.remove_widget(widgetElement);
    };
    
    var addWidget = function (datas) {
        var wiId = datas['widgetInstanceId'];
        var whtcId = datas['widgetHomeTabConfigId'];
        var wdcId = datas['widgetDisplayConfigId'];
        var color = datas['color'];
        var name = datas['name'];
        var configurable = parseInt(datas['configurable']) === 1 ? true : false;
        var visible = parseInt(datas['visibility']) === 1 ? true : false;
        var width = parseInt(datas['width']);
        var height = parseInt(datas['height']);
        var widgetElement =
            '<div class="grid-stack-item"' +
                ' id="widget-element-' + whtcId + '"' +
                ' data-widget-display-config-id="' + wdcId + '"' +
            '>' +
                '<div class="grid-stack-item-content panel panel-default"' +
                    ' id="widget-element-content-' + whtcId + '"';
                     
        if (color !== null) {
            widgetElement += 'style="border-color: ' + color + ';"';
        }
        widgetElement +=
                '>' +
                    '<div class="panel-heading"' +
                        ' id="widget-element-header-' + whtcId + '"';
                     
        if (color !== null) {
            widgetElement += 'style="background-color: ' + color + ';"';
        }    
        widgetElement +=
                    '>' +
                        '<h3 class="panel-title">' +
                            '<span class="pull-right">' +
                                '<i class="fa fa-times close close-widget-btn"' +
                                  ' data-widget-hometab-config-id="' + whtcId + '"' +
                                '></i>' +
                                '<span class="close">&nbsp;</span>' +
                                    '<i class="fa fa-cog close edit-widget-btn"' +
                                      ' data-widget-hometab-config-id="' + whtcId + '"' +
                                      ' data-widget-instance-id="' + wiId + '"' +
                                      ' data-widget-display-config-id="' + wdcId + '"' +
                                    '></i>';
             
        if (configurable) {
            widgetElement +=        '<span class="close">&nbsp;</span>' +
                                        '<i class="fa fa-pencil close edit-widget-content-btn"' +
                                          ' data-widget-instance-id="' + wiId + '"' +
                                          ' data-widget-instance-name="' + name + '"' +
                                        '></i>';
        }    
        widgetElement +=        '</span>' +
                            '<span id="widget-element-title-' + whtcId + '"';
          
        if (!visible) {
            widgetElement +=     ' class="strike"';
        }                      
        widgetElement +=    '>' +
                               name +
                            '</span>' +
                        '</h3>' +
                    '</div>' +
                    '<div id="widget-instance-content-' + wiId + '" class="panel-body">' +
                    '</div>' +
                '</div>' +
            '</div>';
            
        var grid = $('.grid-stack').data('gridstack');
        grid.add_widget(widgetElement, 0, 0, width, height, true);
        
        $.ajax({
            url: Routing.generate(
                'claro_workspace_widget_display_config_position_update',
                {
                    'workspace': workspaceId,
                    'widgetDisplayConfig': wdcId,
                    'row': $('#widget-element-'+ whtcId).attr('data-gs-y'),
                    'column': $('#widget-element-'+ whtcId).attr('data-gs-x'),
                }
            ),
            type: 'POST'
        });
        
        $.ajax({
            url: Routing.generate(
                'claro_widget_content',
                {'widgetInstance': wiId}
            ),
            type: 'GET',
            success: function (datas) {
                $('#widget-instance-content-' + wiId).html(datas);
            }
        });
    };
    
    var updateWidget = function (datas) {
        var id = datas['id'];
        var color = (datas['color'] === null) ? '' : datas['color'];
        var visibility = datas['visibility'];
        $('#widget-element-title-' + id).html(datas['title']);
        $('#widget-element-header-' + id).css('background-color', color);
        $('#widget-element-content-' + id).css('border-color', color);
        
        if (visibility === 'hidden') {
            $('#widget-element-title-' + id).addClass('strike');
        } else {
            $('#widget-element-title-' + id).removeClass('strike');
        }
    };
    
    var doNothing = function () {};
})();