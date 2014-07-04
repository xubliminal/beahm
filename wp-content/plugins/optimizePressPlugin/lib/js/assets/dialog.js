;(function($){
    var container,
        slides = [],
        slide_content = [],
        slide_widths = [],
        slides_ul,
        waiting,
        op_stored_configs = {core:{},addon:{},theme:{}},
        op_stored_steps = {core:{},addon:{},theme:{}},
        help_vids = {defaults:[],core:{},addon:{},theme:{}},
        wysiwyg_removed = false,
        disable_slide = false,
        disable_insert = false,
        wysiwygs_checked = false,
        use_wysiwyg = false,
        editor_switch = false,
        wp_post = false,
        current_config = {},
        current_picker,
        farbtastic,
        picker,
        folders = {},
        show_ons = {},
        stored_selectors = {},
        stored_elements = {},
        current_loading = '',
        current_asset = [],
        trigger_events = {},
        selector_classes = {},
        panda_box, panda_content, cur_selector,
        cat_options = [],
        subcat_options = [];

    $(document).ready(function(){
        var asset_list;
        var asset_list_parent;
        var no_asset_elem;
        var i = 0;
        var onKeyUp;
        var keyupTimeout;
        var shown;

        if(typeof switchEditors != 'undefined'){
            editor_switch = true;
        }
        use_wysiwyg = (typeof tinyMCE !== 'undefined' && typeof tinyMCEPreInit.mceInit.opassetswysiwyg !== 'undefined');
        //use_wysiwyg = (typeof tinyMCE != 'undefined');
        wp_post = (typeof pagenow != 'undefined');
        container = $('#op_asset_browser_container');
        slides_ul = $('#op_asset_browser_slider');
        waiting = $('#op_assets_waiting');

        for (i = 0; i < 5; i++ ) {
            slides.push($('#op_asset_browser_slide'+i));
            slide_content.push(slides[i].find('.op_asset_content'));
        }

        asset_list = slide_content[1].find('div.asset-list');
        asset_list_parent = asset_list.parent();
        no_asset_elem = $('#op_asset_browser_no_assets');

        onKeyUp = function(e, $that) {
            var $that = $that;
            var searchString = $.trim($that.val());
            var items = asset_list.find('li');
            var $spanTitles;

            if(!searchString || searchString === ''){
                asset_list.show();
                //items.filter(':not(:visible)').fadeIn('fast');
                items.filter(':not(:visible)').show();
                no_asset_elem.hide();
            } else {
                searchString = searchString.toLowerCase();
                shown = false;
                $spanTitles = items.find('span.title');
                $spanTitles.each(function () {
                    var $this = $(this);
                    var itemText = $this.text().toLowerCase();
                    var searchArray;
                    var searchArrayLength;
                    var show = (itemText.indexOf(searchString) > -1);
                    var add_class = 'hidden';
                    var remove_class = 'visible';
                    var func = 'hide';
                    var i;

                    if (show) {
                        shown = true;
                        add_class = 'visible';
                        remove_class = 'hidden';
                        func = 'fadeIn';
                        func = 'show';
                    }

                    $this.closest('li')
                        .filter((show ? ':not(:visible)' : ':visible'))
                        .addClass(add_class)
                        .removeClass(remove_class)
                        .stop()
                        [func](); //func is show or hide
                });

                // If initial search string doesn't return result try extended search
                // (allow any word to match, not only exact phrase)
                if (!shown) {
                    $spanTitles.each(function () {
                        var $this = $(this);
                        var itemText = $this.text().toLowerCase();
                        var searchArray;
                        var searchArrayLength;
                        var show = true;
                        var i;

                        searchArray = searchString.split(' ');
                        searchArrayLength = searchArray.length;
                        for (i = 0; i < searchArrayLength; i += 1) {
                            if (itemText.indexOf(searchArray[i]) < 0) {
                                show = false;
                                break;
                            }
                        }

                        if (show) {
                            shown = true;
                            $this.closest('li')
                                .addClass('visible')
                                .removeClass('hidden')
                                .stop()
                                .show();
                        }
                    });
                }

                if (shown) {
                    asset_list.show();
                    no_asset_elem.hide();
                    if (asset_list.find('a:visible').length <= 0) {
                        asset_list.hide();
                        no_asset_elem.show();
                    }
                } else {
                    asset_list.hide();
                    no_asset_elem.show();
                }

            }

        }

        $('#op_assets_filter')
            .keydown(function () {
                clearTimeout(keyupTimeout);
            })
            .keyup(function (e) {
                var $that = $(this);
                keyupTimeout = setTimeout(function () {
                    onKeyUp(e, $that);
                }, 60);
            })
            .change(function () {
                $(this).trigger('keyup');
            });

        $('#op-insert-asset').click(function(e){
            OP.previousWpActiveEditor = wpActiveEditor;
            e.preventDefault();
            open_asset_dialog();
        });

        init_color_pickers();
        //init_font_fields();

        $('.asset-list a',slides[1]).click(function(e){
            container.removeClass('edit-mode');
            e.preventDefault();
            var $t = $(this),
                h = $(this).get_hash();
            current_loading = h;
            h = h.split('/');
            if(h.length == 2){
                container.find('.op_asset_browser_slide .settings-container').css('display','none');
                if(typeof op_assets[h[0]] != 'undefined' && typeof op_assets[h[0]][h[1]] != 'undefined'){
                    load_config(h);
                    set_titles($t);
                    show_slide(2);
                }
            }
        });

        // Keyboard navigation through add element / edit element
        $(window).on('keydown', function(event) {

            var $activeElement = $(document.activeElement);
            var currentActiveSlide = get_active_slide();
            var keyEvent;
            var pageKeyIterator = 5;
            var pageKeyCounter = 0;

            switch (event.which) {
                // s key
                case 83:
                // "/" key
                case 111:
                case 55:
                    if ($activeElement.is('#op_assets_filter') || get_active_slide() !== 1) {
                        return;
                    }
                    event.preventDefault();
                    $('#op_assets_filter').focus();
                    break;

                // tab
                case 900:
                    if (currentActiveSlide === 3 || currentActiveSlide === 4
                        // checks for elements that don't have style chooser
                        || (currentActiveSlide === 2 && $('#op_asset_browser_slide2').find('.op-settings-core-custom_html').is(':visible'))
                        || (currentActiveSlide === 2 && $('#op_asset_browser_slide2').find('.op-settings-core-img_alert').is(':visible'))
                        || (currentActiveSlide === 2 && $('#op_asset_browser_slide2').find('.op-settings-core-javascript').is(':visible'))
                        || (currentActiveSlide === 2 && $('#op_asset_browser_slide2').find('.op-settings-core-vertical_spacing').is(':visible'))
                    ) {

                        if ($activeElement.hasClass('op-bsw-green-button') && !$activeElement.next().is('a') && !event.shiftKey) {
                            event.preventDefault();
                        }

                        if (event.shiftKey && $activeElement.is('.op_asset_content.op_no_outline')) {
                            event.preventDefault();
                        }
                    } else {
                        if (currentActiveSlide === 1 || currentActiveSlide === 2) {
                            keyEvent = $.Event("keydown");
                            if (event.shiftKey) {
                                keyEvent.which = 38; // up arrow
                            } else {
                                keyEvent.which = 40; // down arrow
                            }
                            $(window).trigger(keyEvent);
                        }
                        event.preventDefault();
                    }
                    break;

                // esc key
                case 27:
                    if (currentActiveSlide > 1) {
                        $('.fancybox-opened').eq(-1)
                            .find('.op_asset_browser_slide_active')
                                .find('.op_asset_content')
                                    .focus();
                    }
                    break;

                // page up
                case 33:
                    if (currentActiveSlide === 1 || currentActiveSlide === 2) {
                        keyEvent = $.Event("keydown");
                        keyEvent.which = 38; // up arrow
                        for (pageKeyCounter = 0; pageKeyCounter < pageKeyIterator; pageKeyCounter += 1) {
                            $(window).trigger(keyEvent);
                        }
                        event.preventDefault();
                    }
                    break;

                // page down
                case 34:
                    if (currentActiveSlide === 1 || currentActiveSlide === 2) {
                        keyEvent = $.Event("keydown");
                        keyEvent.which = 40; // down arrow
                        for (pageKeyCounter = 0; pageKeyCounter < pageKeyIterator; pageKeyCounter += 1) {
                            $(window).trigger(keyEvent);
                        }
                        event.preventDefault();
                    }
                    break;

                // down arrow
                case 40:
                    if (currentActiveSlide === 1) {
                        event.preventDefault();
                        if ($activeElement.parent().is('.op-asset-list-item') && $activeElement.parent().nextAll('.op-asset-list-item:visible').length > 0) {
                            $activeElement
                                .removeClass('op-last-selected-asset-list-item selected')
                                .parent()
                                .nextAll('.op-asset-list-item:visible')
                                .eq(0)
                                .find('a')
                                    .addClass('op-last-selected-asset-list-item')
                                    .focus();
                        } else if ($activeElement.is('#op_assets_filter')) {
                            $('.asset-list li:visible').eq(0).find('a').focus();
                        } else {
                            focusLastSelectedItem();
                        }
                    }
                    if (currentActiveSlide === 2) {
                        event.preventDefault();
                        if ($activeElement.parent().is('.op-asset-dropdown-list-item:visible') && $activeElement.parent().next('.op-asset-dropdown-list-item:visible').length > 0) {
                            $activeElement
                                .removeClass('op-last-selected-asset-dropdown-list-item selected')
                                .parent()
                                .next('.op-asset-dropdown-list-item')
                                .find('a')
                                    .addClass('op-last-selected-asset-dropdown-list-item')
                                    .focus();
                        } else if ($activeElement.is('#op_assets_filter')) {
                            $('.op-asset-dropdown-list-item:visible').eq(0).find('a').focus();
                        } else {
                            focusLastSelectedItem();
                        }
                    }
                    break;

                // up arrow
                case 38:
                    if (currentActiveSlide === 1) {
                        event.preventDefault();
                        if ($activeElement.is('#op_assets_filter')) {
                            return;
                        } else if ($activeElement.parent().is('.op-asset-list-item') && $activeElement.parent().prevAll('.op-asset-list-item:visible').length > 0) {
                            $activeElement
                                .removeClass('op-last-selected-asset-list-item')
                                .parent()
                                    .prevAll('.op-asset-list-item:visible')
                                    .eq(0)
                                    .find('a')
                                        .addClass('op-last-selected-asset-list-item')
                                        .focus();
                        } else if ($activeElement.parent().is('.op-asset-list-item')) {
                            $('#op_assets_filter').focus();
                        } else {
                            focusLastSelectedItem();
                        }
                    }

                    if (currentActiveSlide === 2) {
                        event.preventDefault();
                        if ($activeElement.parent().is('.op-asset-dropdown-list-item:visible') && $activeElement.parent().prev('.op-asset-dropdown-list-item:visible').length > 0) {
                            $activeElement
                                .removeClass('op-last-selected-asset-dropdown-list-item')
                                .parent()
                                .prev('.op-asset-dropdown-list-item')
                                .find('a')
                                    .addClass('op-last-selected-asset-dropdown-list-item')
                                    .focus();
                        } else if ($activeElement.is('#op_assets_filter')) {
                            $('.op-asset-dropdown-list-item:visible').eq(0).find('a').focus();
                        } else {
                            focusLastSelectedItem();
                        }
                    }
                    break;

                // right arrow
                case 39:
                    if ($activeElement.parent().is('.op-asset-list-item') || $activeElement.parent().is('.op-asset-dropdown-list-item')) {
                        $activeElement.trigger('click');
                    } else if (currentActiveSlide === 2) {
                        $('#op_asset_browser_slide2').find('.op-asset-dropdown-list-item .selected').trigger('click');
                    }
                    break;

                // left arrow
                case 37:
                    if (currentActiveSlide > 1) {
                        if ($activeElement.parent().hasClass('op-asset-dropdown-list-item') || !$activeElement.is('.insert-media, a, input, textarea, select, .op-bsw-green-button')) {
                            show_slide((currentActiveSlide - 1), true);
                        }
                    }
                    break;
            }
        });

        //$('a.op-slide-link',container).live('click',function(e){
        container.on('click', 'a.op-slide-link', function(e){
            e.preventDefault();
            show_slide($(this).get_hash());

            //Hack to fix bug with initial rendering of membership order button settings.
            if ($(e.target).parent().parent().hasClass('op-settings-core-membership_order_button')) {
                $('#op_assets_core_membership_order_button_gateway').trigger('change');
            }
        });

        panda_box = container.find('.sneezing-panda').hide();
        panda_content = panda_box.find('.content');
        slides[3].add(slides[4]).delegate('.op-asset-dropdown .selected-item','click',function(e){
            e.preventDefault();
            cur_selector = $(this);
            var dropdown = cur_selector.closest('.op-asset-dropdown');
            for(var i in selector_classes){
                panda_content.removeClass(i);
                if(dropdown.hasClass(i)){
                    panda_content.addClass(i);
                }
            }
            panda_content.html(cur_selector.next().html());
            panda_box.show().animate({bottom:0},400);
        });
        panda_box.find('.hide-the-panda').click(function(e){
            e.preventDefault();
            close_pandabox();
        });
        panda_content.delegate('li a','click',function(e){
            e.preventDefault();
            var el = $(this);
            if (cur_selector.parent().attr('id') != 'op_assets_core_button_button_preview_container'
            && cur_selector.parent().attr('id') != 'op_assets_core_optin_box_tabs_submit_button_button_preview_container'
            && cur_selector.parent().attr('id') != 'op_assets_core_optin_modal_tabs_submit_button_button_preview_container'
            && cur_selector.parent().attr('id') != 'op_assets_core_membership_order_button_button_preview_container'
            && cur_selector.parent().attr('id') != 'op_assets_core_op_popup_button_preview_container') {
                cur_selector.html(el.html()).parent().find('li img[alt="'+el.find('img').attr('alt')+'"]').parent().trigger('click');
            }

            $('#op_asset_browser_slide3 .op-settings-' + current_asset[0] + '-' + current_asset[1]).trigger({type: 'update_button_preview', id: cur_selector.parent().attr('id'), value: el.find('img').attr('alt'), font_type: el.find('img').attr('data-type'), font_family: el.find('img').attr('data-family'), group: current_asset[0], tag: current_asset[1]});
            close_pandabox();
        });

        $(document).mousedown( function(e) {
            picker.hide().css({'top':'auto','left':'auto'});
            if($(e.target).closest('.sneezing-panda').length == 0){
                close_pandabox();
            }
        }).trigger('mousedown');

        slides[2].delegate('.op-disable-selected a','click',function(e){
            e.preventDefault();
            $(this).closest('.op-asset-dropdown-list').find('a.selected').removeClass('selected').end().end().addClass('selected');
            show_slide(3);
        });

        $('body').on('click', '.wp-editor-wrap', function () {
            wpActiveEditor = $(this).find('textarea').attr('id');
        });

         $('body').on('mousedown', '.op-insert-buttons', function () {
            wpActiveEditor = OP.previousWpActiveEditor;
            OP.previousWpActiveEditor = undefined;
        });

        container.delegate('.op-multirow-tabs li a','click',function(e){
            e.preventDefault();
            var $t = $(this), ul = $t.closest('ul'), container = ul.parent(), idx = ul.find('li').index($t.parent());
            ul.find('li').removeClass('selected').filter(':eq('+idx+')').addClass('selected');
            container.find('> .op-multirow').hide().filter(':eq('+idx+')').show();

            //Find all feature titles and show them. This event hides them for some reason.
            $('.field-feature_title').parent().show();
        }).delegate('a[href$="#reset-font"]','click',function(e){
            var typography = $(this).prev(),
                font = typography.find('.font-view');
            typography.find(':input').val('').trigger('change');
            font.find('.selected-item').html(font.find('li:first a').html());
            e.preventDefault();
        });

        for(i=1;i<5;i++){
            help_vids.defaults.push($('#op_asset_browser_slide'+i+' .help-vid-link').html());
        };

        init_help_videos();

    }); // end document.ready()

    function close_pandabox(){
        if(panda_box.is(':visible')){
            panda_box.animate({bottom:-490},{duration:400,complete:function(){
                $(this).hide();
                if(panda_content.hasClass('help-video')){
                    panda_content.html('');
                }
            }});
        }
    };

    function set_titles($t){
        var title, link_title = $t.find('span.content span.title').text();
        for(var i=2;i<5;i++){
            title = translate('slide_'+i+'_title').replace(/%element_name%/,link_title);
            slide_content[i].find('div.asset-title:first span.title-text').text(title);
        };
    };

    function resize_content_areas(){
        var h = $('.fancybox-inner').height();
        $.each(slide_content,function(){
            this.height(h);
        });
    };

    function load_config(settingsAsset, callback){

        callback = callback || function(){};

        if(op_assets[settingsAsset[0]][settingsAsset[1]].settings == 'Y'){
            if(typeof op_stored_configs[settingsAsset[0]] == 'undefined'){
                op_stored_configs[settingsAsset[0]] = {};
                op_stored_steps[settingsAsset[0]] = {};
            }
            if(typeof op_stored_configs[settingsAsset[0]][settingsAsset[1]] == 'undefined'){
                var url = OptimizePress[settingsAsset[0]+'_assets_url']
                if(settingsAsset[0] != 'core'){
                    url += settingsAsset[1]+'/';
                }
                $.getScript(url+settingsAsset[1]+'.js',function(){
                    setup_settings_panel(settingsAsset[0], settingsAsset[1], op_asset_settings);
                    if($.object_length(folders) > 0){
                        $.post(OptimizePress.ajaxurl,
                            {
                                'action': OptimizePress.SN+'-assets-folder-list',
                                'folders': folders
                            },
                            function(resp){
                                for(var type in resp){
                                    for(var field in resp[type]){
                                        var html = resp[type][field];
                                        if(type == 'image' || type == 'style'){
                                            var el = $('#'+field+'_container'),
                                                id = el.closest('.op_asset_browser_slide').attr('id');
                                            el.find('.op-asset-dropdown-list').append(html);
                                            if(id == 'op_asset_browser_slide3' || id == 'op_asset_browser_slide4'){
                                                set_selector_value(field+'_container')
                                            }
                                        } else if(type == 'checkbox'){
                                            $('.field-id-'+field+' .checkbox-container').append(html);
                                        }
                                        stored_selectors[field] = html;
                                    };
                                };
                                //resize_content_areas();
                                show_settings(settingsAsset[0], settingsAsset[1]);
                                callback(op_stored_steps[settingsAsset[0]][settingsAsset[1]], op_stored_configs[settingsAsset[0]][settingsAsset[1]]);
                                //show_container(settingsAsset[0], settingsAsset[1]);

                                // Remove image loading indicators after images are loaded.
                                // This is needed because some element preview images are transparent and very small,
                                // so loading indicator is visible through the element.
                                $('#op_asset_browser_container')
                                    .find('.op-asset-dropdown-list-item:not(.op-asset-dropdown-list-item--loaded)')
                                    .find('img')
                                        .on('load', function () {
                                            $(this)
                                                .parent().addClass('op-asset-img-loaded')
                                                .parent().addClass('op-asset-dropdown-list-item--loaded');
                                            $(this).off();
                                        });
                            },
                            'json'
                        );
                        folders = {};
                    } else {
                        show_settings(settingsAsset[0], settingsAsset[1]);
                        callback(op_stored_steps[settingsAsset[0]][settingsAsset[1]], op_stored_configs[settingsAsset[0]][settingsAsset[1]]);
                        //show_container(h[0],h[1]);
                    }
                });
            } else {
                if(typeof op_stored_steps[settingsAsset[0]][settingsAsset[1]] != 'undefined'){
                    $.each(op_stored_steps[settingsAsset[0]][settingsAsset[1]],function(){
                        this.find('.wp-editor-area').each(function(){
                            var id = $(this).attr('id');
                            $(this).val(op_wpautop($(this).val()));
                            tinyMCE.execCommand("mceAddControl", true, id);
                        });
                    });
                }
                show_settings(settingsAsset[0], settingsAsset[1]);
                callback(op_stored_steps[settingsAsset[0]][settingsAsset[1]], op_stored_configs[settingsAsset[0]][settingsAsset[1]]);
                //show_container(h[0],h[1]);
            }
        } else {
            if(disable_insert){
                disable_slide = false;
                disable_insert = false;
                show_slide(1);
            } else {
                insert_content('['+settingsAsset[1]+']');
                $.fancybox.close();
            }
        }
    }

    function add_insert_buttons(form,option,cur_step){
        var str = '<div class="op-insert-buttons cf">';
        if(typeof option != 'boolean'){
            /*if(typeof option.actions != 'undefined'){
                for (var i in option.actions) {
                    str += '<a href="#' + i + '" class="op-slide-action" id="' + option.actions[i].id + '">'+translate(option.actions[i].label)+'</a>';
                }
            }*/

            if(typeof option.prev != 'undefined'){
                str += '<a href="#'+(cur_step+1)+'" class="op-slide-link">'+translate(option.prev)+'</a>';
            }
            if(typeof option.next != 'undefined'){
                str += '<a href="#'+(cur_step+2)+'" class="op-slide-link">'+translate(option.next)+'</a>';
            }
            if(typeof option.insert === 'undefined' || typeof option.insert !== 'boolean' || option.insert !== false){
                str += '<a href="#" class="op-bsw-green-button">'+translate('Insert')+'</a>';
            }
        } else if (option === true) {
            str += '<a href="#" class="op-bsw-green-button">'+translate('Insert')+'</a>';
        }
        str += '</div><div class="clear"></div>';
        form.append(str);
    };

    function show_container(group,tag){
        if(group+'/'+tag == current_loading){
            waiting.fadeOut('fast',function(){
                container.fadeIn('fast');
            });
        }
    };

    function show_slide(slide,animate){
        var margin = 0;
        var $fancyBoxOpened;
        var i = 0;

        if (disable_slide) {
            return;
        }
        animate = animate === false ? false : true;
        for (i = 0; i < slide; i++ ) {
            margin += slides[i].outerWidth(true);
        }
        $fancyBoxOpened = $('.fancybox-opened').eq(-1);
        $fancyBoxOpened.find('.op_asset_browser_slide_active').removeClass('op_asset_browser_slide_active');
        if (animate) {
            slides_ul.animate({'margin-left':(-margin)+'px'},'slow', function () {
                $fancyBoxOpened
                    .find('.op_asset_browser_slide')
                        .eq(slide)
                        .addClass('op_asset_browser_slide_active')
                        .find('.op_asset_content')
                            .addClass('op_no_outline')
                            .attr('tabindex', 0)
                            .focus();
                if (get_active_slide() === 1) {
                    focusLastSelectedItem();
                }
            });
        } else {
            slides_ul.css({'margin-left':(-margin)+'px'});
            $fancyBoxOpened
                .find('.op_asset_browser_slide')
                    .eq(slide)
                    .addClass('op_asset_browser_slide_active')
                    .find('.op_asset_content')
                        .addClass('op_no_outline')
                        .attr('tabindex', 0)
                        .focus();
            if (get_active_slide() === 1) {
                focusLastSelectedItem();
            }
        }
    };

    /**
     * Selects the last active list item in add element dialog for keyboard navigation
     */
    function focusLastSelectedItem() {

        var $lastSelectedListItem;
        var currentActiveSlide = get_active_slide();

        if (currentActiveSlide === 1) {
            $lastSelectedListItem = $('#op_asset_browser_slide1').find('.op-last-selected-asset-list-item');
            if ($lastSelectedListItem.length > 0) {
                $lastSelectedListItem.focus();
            } else {
                $('#op_assets_filter').focus();
            }
        }

        if (currentActiveSlide === 2) {
            $lastSelectedListItem = $('#op_asset_browser_slide2').find('.op-last-selected-asset-dropdown-list-item:visible');
            $('#op_asset_browser_slide2').find('.op-last-selected-asset-dropdown-list-item:hidden').removeClass('op-last-selected-asset-dropdown-list-item');
            if ($lastSelectedListItem.length > 0) {
                $lastSelectedListItem.removeClass('selected').focus();
            } else {
                $('.op-asset-dropdown-list-item:visible').eq(0).find('a').focus();
            }
        }

    }


    /**
     * Function returns the active slide number
     * @return number or false if no slide is active
     */
    function get_active_slide() {
        var currentSlide;
        $('#op_asset_browser_slider').find('> li').each(function (index) {
            if ($(this).hasClass('op_asset_browser_slide_active')) {
                currentSlide = index;
            }
        });
        return currentSlide;
    }

    function show_settings(group,tag){
        set_help_videos(group,tag);
        current_asset = [group,tag];
        reset_form(group,tag);
        container.find('.op_asset_browser_slide .op-settings-'+group+'-'+tag).css('display','block');
    };

    function setup_settings_panel(group,tag,settings){
        op_stored_steps[group][tag] = [];
        op_stored_configs[group][tag] = settings;
        help_vids[group][tag] = [];
        var classname = 'op-settings-'+group+'-'+tag, idprefix = 'op_assets_'+group+'_'+tag+'_',
            insert_steps = settings.insert_steps || {},
            vids = settings.help_vids || {};
        if(slides[2].find('> .'+classname).length == 0){
            trigger_events = {};
            for(var i=1;i<4;i++){
                var tmp_vid = '';
                if(typeof vids['step_'+i] != 'undefined'){
                    tmp_vid = generate_video_link(vids['step_'+i]);
                }
                help_vids[group][tag].push(tmp_vid);
                if(typeof settings.attributes['step_'+i] != 'undefined'){
                    var step = $('<div class="'+classname+' settings-container"><div class="op-asset-actual-content" />');
                    op_stored_steps[group][tag].push(step);
                    slide_content[i+1].append(step);
                    var cont = step.find('.op-asset-actual-content');
                    for(var j in settings.attributes['step_'+i]){
                        generate_field.apply(cont,[idprefix,j,settings.attributes['step_'+i][j],group,tag]);
                    };
                    init_color_picker(step);
                    if(typeof insert_steps[i] != 'undefined'){
                        add_insert_buttons(step,insert_steps[i],i);
                    }
                } else {
                    break;
                }
            };
            if(typeof settings.onGenerateComplete != 'undefined'){
                settings.onGenerateComplete(op_stored_steps[group][tag]);
            }
            $.each(trigger_events,function(i,v){
                var el = $(i);
                $.each(v,function(i2,v2){
                    el.trigger(v2);
                });
            });
            //$('.'+classname,container).find('.op-insert-buttons .op-bsw-green-button').live('click',function(e){
            container.find('.'+classname).on('click', '.op-insert-buttons .op-bsw-green-button', function(e){
                e.preventDefault();
                insert_tag(group,tag);
            });
            init_show_ons();
        }
    };

    function reset_form(group,tag){
        var reset_opts = false;
        if(!disable_slide && !disable_insert){
            disable_slide = true;
            disable_insert = true;
            reset_opts = true;
        }
        if(typeof op_stored_configs[group][tag].attributes != 'undefined'){
            var idprefix = 'op_assets_'+group+'_'+tag+'_',
                classprefix = '.field-id-op_assets_'+group+'_'+tag+'_';
            for(var i in op_stored_configs[group][tag].attributes){
                for(var j in op_stored_configs[group][tag].attributes[i]){
                    var v = op_stored_configs[group][tag].attributes[i][j];
                    reset_field(idprefix+j,classprefix+j,v);
                }
            };
        }
        if(reset_opts){
            disable_slide = false;
            disable_insert = false;
        }
    };

    function reset_field(idprefix,classprefix,field){
        var type = field.type || '';
        if(type == 'tabs'){
            for(var i in field.tabs){
                for(var j in field.tabs[i].fields){
                    reset_field(idprefix+'_'+i+'_'+j,classprefix+'_'+i+'_'+j,field.tabs[i].fields[j]);
                };
            };
        } else if (type == 'column') {
            idprefix = idprefix.replace('_left_column','').replace('_right_column','');
            classprefix = classprefix.replace('_left_column','').replace('_right_column','');
            for (var i in field.elements) {
                reset_field(idprefix + '_' + i, classprefix + '_' + i, field.elements[i]);
            }
        } else if (type == 'container') {
            for (var i in field.attributes) {
                reset_field(idprefix + '_' + i, classprefix + '_' + i, field.attributes[i]);
            }
            $(classprefix + ' .panel-controlx').attr('checked', field.default_value).trigger('change');
        } else if (type == 'slider') {
            $slider = $('#' + idprefix);
            $slider.slider({value: get_default_val(field)});
            $slider.slider('option', 'slide').call($slider, {}, {value: get_default_val(field), id: idprefix});
            $slider.slider('option', 'stop').call($slider, {}, {value: get_default_val(field), id: idprefix});
        } else if (type == 'text_properties') {
            $('#' + idprefix + '_text').val(field.text_default).trigger('keydown');
            $('#' + idprefix + '_size').val(field.size_default).trigger('change');
            $('#' + idprefix + '_color').val(field.color_default).trigger('keydown');
            $('#' + idprefix + '_container a.selected-item').html($('#' + idprefix + '_container.op-asset-dropdown-list li:first a').html());
            $('#op_asset_browser_slide3 .op-settings-' + current_asset[0] + '-' + current_asset[1]).trigger({type: 'update_button_preview', id: idprefix, value: 'Default', font_type: 'default', tag: current_asset[1]});
            $(classprefix + ' .op-font-style-bold').attr('checked', field.bold_default).trigger('change');
            $(classprefix + ' .op-font-style-italic').attr('checked', field.italic_default).trigger('change');
            $(classprefix + ' .op-font-style-underline').attr('checked', field.underline_default).trigger('change');
        } else if(type == 'media'){
            set_uploader_value(idprefix,'');
        } else if(type == 'multirow'){
            container.find(classprefix+'-multirow-container .op-multirow').remove();
        } else if(type == 'style-selector' || type == 'image-selector'){
            set_selector_value(idprefix+'_container',get_default_val(field));
        } else if(type == 'wysiwyg'){
            set_wysiwyg_content(idprefix,get_default_val(field));
        } else if(type == 'font'){
            container.find(classprefix+' > a.reset').trigger('click');
        } else if(type == 'checkbox'){
            var els = $('.field-id-'+idprefix+' :checkbox').attr('checked',false).trigger('change'),
                val = get_default_val(field);
            if(typeof val == 'object'){
                $.each(val,function(i,v){
                    els.filter('[value="'+v+'"]').attr('checked',true).trigger('change');
                });
            } else if (val === true) {
                els.attr('checked', true).trigger('change');
            }
        } else if(type == 'radio'){
            $('.field-id-'+idprefix+' :radio[value="'+get_default_val(field)+'"]').attr('checked',true);
        } else {
            var el = $('#'+idprefix).val(get_default_val(field)).trigger('change'),
                val = el.val();
            if(typeof field.multirow != 'undefined'){
                for(var k=0;k<val;k++){
                    for(var idx in field.multirow.attributes){
                        reset_field(idprefix+'_'+k+'_'+idx,classprefix+'_'+k+'_'+idx,field.multirow.attributes[idx]);
                        //var vm = field.multirow.attributes[idx];
                        //$('#'+idprefix+'_'+k+'_'+idx).val(get_default_val(vm)).trigger('change');
                    };
                }
            }
        }
    };

    function attach_panel_control(name, id, value) {
        /*
         * Creates markup for iButton
         */
        var panel_control = this.append('<div class="panel-control"><input type="checkbox" name="op[' + name + '][enabled]" id="' + id + '" value="' + value + '" class="panel-controlx"></div>');

        /*
         * Initiates iButton and 'change' event
         */
        panel_control.find('.panel-controlx').iButton({
            change: function(elem){
                var parent = elem.closest('.op-bsw-grey-panel'),
                    panel = parent.find('.op-bsw-grey-panel-content:first'),
                    link_el = parent.find('.show-hide-panel a:first'),
                    visible = panel.is(':visible'),
                    value = elem.is(':checked');
                if (!visible && value === true) {
                    panel.show();
                    link_el.addClass('op-bsw-visible');
                } else if (visible && value === false) {
                    panel.hide();
                    link_el.removeClass('op-bsw-visible');
                }
            }
        });
    }

    function generate_field(idprefix,id,field,group,tag,classextra){
        if (typeof field.skip != 'undefined' && field.skip == true) {
            return;
        }

        var type = field.type || 'input', help = field.help || '', title = field.title || '', classextra = classextra || '',
            add_class = (field.type ==  'button_preview' ? '' : field.addClass || ''),
            tmp_id = idprefix+id,
            removeCf = field.removeCf || false,
            str = $('<div class="field-row field-'+type+' field-id-'+tmp_id+' '+classextra+' '+add_class+' field-'+id+(removeCf?'':' cf')+'" />'),
            $t = this,
            prefix = field.prefix || '',
            suffix = field.suffix || '',
            multirow_container;
        prefix = prefix == '' ? '' : translate(prefix)+' ';
        suffix = suffix == '' ? '' : ' '+translate(suffix);

        if(type == 'microcopy'){
            $t.append('<p class="micro-copy'+(add_class==''?'':' '+add_class)+'"><img src="'+OptimizePress.imgurl+'70_888.png" alt="Help" /> '+translate(field.text)+'</p>');
            return;
        } else if(type == 'tabs'){
            var div = $t.append('<div class="field-id-'+tmp_id+'-multirow-container multirow-container field-type-tabs cf" />').find('.field-id-'+tmp_id+'-multirow-container'),
                tmp_id2 = tmp_id,
                ul = div.append('<ul class="op-multirow-tabs cf" />').find('.op-multirow-tabs'),
                multi;
            for(var i in field.tabs){
                ul.append('<li><a href="#'+i+'">'+translate(field.tabs[i].title)+'</a></li>');
                multi = div.append('<div class="op-multirow op-multirow-'+i+'" />').find('.op-multirow-'+i);
                for(var j in field.tabs[i].fields){
                    generate_field.apply(multi,[tmp_id2+'_'+i+'_',j,field.tabs[i].fields[j],group,tag]);
                    $('#'+tmp_id2+'_'+i+'_'+j).val(get_default_val(field.tabs[i].fields[j]));
                }
            }
            ul.find('a:first').trigger('click');
            return;
        }
        str.appendTo(this);
        if($.inArray(type,['h1','h2','h3','h4','h5','h6','p']) > -1){
            str.append('<'+type+'>'+translate(title)+'</'+type+'>');
        } else if(type == 'multirow'){
            str.before('<div class="field-id-'+tmp_id+'-multirow-container multirow-container cf" />');
            multirow_container = $t.find('.field-id-'+tmp_id+'-multirow-container').append(title==''?'':'<label for="'+tmp_id+'">'+translate(field.title)+'</label>');
            str.append('<a href="#" class="new-row">'+translate('add_new')+' '+translate(field.link_suffix || '')+'</a>');
            multirow_container.data('op_current_increment',0);
        } else if (type === 'column') {
            /*
             * Generating child elements
             */
            for (key in field.elements) {
                generate_field.apply(str, [prefix, idprefix + key, field.elements[key], group, tag]);
            }
        } else if (type === 'container') {
            /*
             * Appending container markup
             */
            var container = str.append('<div class="op-bsw-grey-panel section-' + tmp_id + '"><div class="op-bsw-grey-panel-header cf"><h3><a href="#">' + translate(field.title) + '</a></h3></div><div class="op-bsw-grey-panel-content op-bsw-grey-panel-no-sidebar cf" id="op_container_content_' + tmp_id + '"></div></div>');

            /*
             * Attaching click event
             */
            container.delegate('.op-bsw-grey-panel-header h3 a', 'click', function(e) {
                e.preventDefault();
                var $t = $(this),
                    func1 = 'addClass',
                    func2 = 'show',
                    parent = $t.closest('.op-bsw-grey-panel'),
                    panel = parent.find('.op-bsw-grey-panel-content:first');
                if(panel.is(':visible')){
                    func1 = 'removeClass';
                    func2 = 'hide';
                }
                $t[func1]('op-bsw-visible');
                panel[func2]();
            });

            /*
             * Displaying panel control if needed
             */
            if (typeof field.showPanelControl != 'undefined' && true === field.showPanelControl) {
                attach_panel_control.apply(container.find('.op-bsw-grey-panel-header'), [tmp_id, 'panel_control_' + tmp_id, 'Y']);
            }

            /*
             * Generating child elements
             */
            var $container_content = container.find('#op_container_content_' + tmp_id);
            for (key in field.attributes) {
                generate_field.apply($container_content, [prefix, tmp_id + '_' + key, field.attributes[key], group, tag]);
            }
            /*
             * Input type text will be getting its own events as it needs to respond to keypresses, pastes and stuff like that
             */
            container.find('input[type!="text"],select').change(function(e) {
                var element_id, element_value;
                /*
                 * For checkbox element we need to modify its value based on checked status
                 */
                if ($(this).attr('type') === 'checkbox') {
                    element_id = $(this).attr('name');
                    if ($(this).is(':checked')) {
                        element_value = 1;
                    } else {
                        element_value = 0;
                    }
                } else {
                    element_id = $(this).attr('id');
                    element_value = $(this).val();
                }
                $('#op_asset_browser_slide3 .op-settings-' + current_asset[0] + '-' + current_asset[1]).trigger({type: 'update_button_preview', value: element_value, id: element_id, element_type: this.tagName.toLowerCase(), tag: current_asset[1]});
            });
            /*
             * Own events just for input type text (keypress, paste)
             */
            container.find('input[type="text"]').on('propertychange keydown input paste', function(e) {
                $('#op_asset_browser_slide3 .op-settings-' + current_asset[0] + '-' + current_asset[1]).trigger({type: 'update_button_preview', value: $(this).val(), id: $(this).attr('id'), element_type: this.tagName.toLowerCase(), tag: current_asset[1]});
            });
        } else {
            str.append(title==''?'':'<label for="'+tmp_id+'">'+translate(field.title)+'</label>');
            if (typeof field.helpPosition != 'undefined' && field.helpPosition == 'top' && help != '') {
                str.append('<p class="micro-copy"><img src="'+OptimizePress.imgurl+'70_888.png" alt="Help" /> '+translate(help)+'</p>');
            }
            switch(type){
                case 'custom_html':
                    str.append(field.html);
                    break;
                case 'button_preview':
                    str.append('<div class="preview_border preview-wrapper"><div class="preview-outer"><div class="preview-inner preview_border op-asset-dropdown" id="' + tmp_id + '_container"><a href="#" tabindex="-1" class="selected-item css-button style-1 pbox_' + field.addClass + '" id="op_button_preview">'
                        + '<span class="text">' + (typeof field.text != 'undefined' ? field.text : 'Placeholder text') + '</span>'
                        + '<span' + (typeof field.showSubtext != 'undefined' && field.showSubtext === false ? ' style="display:none !important;"' : '') + ' class="subtext">' + (typeof field.subtext != 'undefined' ? field.subtext : 'Placeholder subtext') + '</span>'
                        + '<div' + (typeof field.showGradient != 'undefined' && field.showGradient === false ? ' style="display:none !important;"' : '') + '  class="gradient"></div>'
                        + '<div' + (typeof field.showShine != 'undefined' && field.showShine === false ? ' style="display:none !important;"' : '') + '  class="shine"></div>'
                        + '<div class="hover"></div>'
                        + '<div class="active"></div>'
                        + '</a></div></div></div>');
                    if(typeof field.folder != 'undefined'){
                        add_folder('image',{fieldid:tmp_id,group:group,tag:tag,folder:field.folder},field);
                    }
                    $('#op_button_preview').parent().append(_preset_selector_html(tmp_id,'',field.selectorClass || ''));
                    break;
                case 'media':
                    str.append($('#op_dummy_media_container').html().replace(/op_dummy_media/g,tmp_id));
                    if(typeof field.callback != 'undefined'){
                        $('#'+tmp_id).change(field.callback);
                    }
                    break;
                case 'wysiwyg':
                    add_wysiwyg.apply(str,[tmp_id]);
                    break;
                case 'slider':
                    var output = '';
                    if (typeof field.showOutputElement != 'undefined' && field.showOutputElement === true) {
                        output = '<div class="slider-output"><span id="output_' + tmp_id + '" data-unit="' + field.unit + '">'
                        + (typeof field.default_value != 'undefined' ? field.default_value : 0)
                        + field.unit + '</span></div>'
                    }
                    slider = str.append(
                        prefix
                        + output
                        + '<div class="slider-item" id="' + tmp_id + '" data-min="' + (typeof field.min != 'undefined' ? field.min : 0) + '" data-max="' + (typeof field.max != 'undefined' ? field.max : 0) + '" data-value="' + (typeof field.default_value != 'undefined' ? field.default_value : 0) + '" />'
                        + suffix
                    );
                    slider.find('.slider-item').slider({
                        min: (typeof field.min != 'undefined' ? field.min : 0),
                        max: (typeof field.max != 'undefined' ? field.max : 0),
                        value: (typeof field.default_value != 'undefined' ? field.default_value : 0),
                        stop: function (event, ui) {
                            var id;
                            if (typeof ui.handle != 'undefined') {
                                id = $(ui.handle).parent().attr('id');
                            } else {
                                id = ui.id;
                            }
                            $('#op_asset_browser_slide3 .op-settings-' + current_asset[0] + '-' + current_asset[1]).trigger({type: 'update_button_preview', value: ui.value, id: id, element_type: 'slider', tag: current_asset[1]});
                        },
                        slide: function (event, ui) {
                            var id;
                            if (typeof ui.handle != 'undefined') {
                                id = $(ui.handle).parent().attr('id');
                            } else {
                                id = ui.id;
                            }
                            var $output = $('#output_' + id);
                            if ($output.length > 0) {
                                $output.html(ui.value + $output.attr('data-unit'));
                            }
                        }
                    });
                    break;
                case 'color':
                    str.append('<div class="color-picker-container cf">'+
                            prefix+'<input type="text" value="' + (typeof field.default_value != undefined ? field.default_value : '') + '" id="'+tmp_id+'" name="'+tmp_id+'" />'+suffix+'<a href="#" class="op-pick-color hide-if-no-js"></a>'+
                            '</div>');
                    break;
                case 'checkbox':
                    var func = field.func || 'prepend';
                    str[func]('<div class="checkbox-container">'+input_values(tmp_id,field,type,group,tag)+'</div>');
                    if(typeof field.appendTo != 'undefined'){
                        str.delegate(':checkbox', 'change', function(){
                            var f = $('.field-id-'+idprefix+field.appendTo+' :input'),
                                cc = f.val(),
                                val = $(this).val();

                            if (typeof cc === 'undefined') {
                                cc = '';
                            }

                            if($(this).is(':checked')){
                                cc += '|'+val;
                            } else {
                                cc = cc.replace(new RegExp(val,'g'),'');
                            }

                            cc = cc.replace(new RegExp('^[|]+', 'g'), '');
                            f.val(cc);
                        });
                    }
                    break;
                case 'radio':
                    str.append('<div class="checkbox-container">'+input_values(tmp_id,field,type,group,tag)+'</div>');
                    break;
                case 'image-selector':
                    if(typeof field.folder != 'undefined'){
                        add_folder('image',{fieldid:tmp_id,group:group,tag:tag,folder:field.folder},field);
                    }
                    str.append(_style_selector_html(tmp_id,'',field.selectorClass || ''));
                    break;
                case 'text_properties':
                    str.append(add_text_properties(tmp_id,field)).find('.selected-item').html(str.find('li a:first').html());
                    break;
                case 'font':
                    str.append(add_font_settings(tmp_id,field)).find('.selected-item').html(str.find('li a:first').html());//.find('.op-asset-dropdown-list li a:first').trigger('click');
                    break;
                case 'style-selector':
                    if(typeof field.folder != 'undefined'){
                        add_folder('style',{fieldid:tmp_id,group:group,tag:tag,folder:field.folder},field);
                    }
                    str.append(_style_selector_html(tmp_id,'',field.selectorClass || ''));
                    break;
                case 'select':
                    str.append(prefix+'<select name="'+tmp_id+'" id="'+tmp_id+'">'+input_values(tmp_id,field,type,group,tag)+'</select>'+suffix);
                    break;
                case 'membership_select':
                    str.append(prefix+'<select name="'+tmp_id+'" id="'+tmp_id+'">'+input_values(tmp_id,field,type,group,tag)+'</select>'+suffix);
                    break;
                case 'textarea':
                    str.append(prefix+'<textarea cols="30" rows="10" name="'+tmp_id+'" id="'+tmp_id+'"></textarea>'+suffix);
                    break;
                case 'hidden':
                    str.append(prefix+'<input type="hidden" name="'+tmp_id+'" id="'+tmp_id+'" value="' + (typeof field.default_value != undefined ? field.default_value : '') + '" />'+suffix);
                    break;
                case 'paragraph':
                    str.append(prefix+'<p name="'+tmp_id+'" id="'+tmp_id+'">' + (typeof field.text != undefined ? field.text : '') + '</p>'+suffix);
                    break;
                default:
                    str.append(prefix+'<input type="text" name="'+tmp_id+'" id="'+tmp_id+'" />'+suffix);
                    break;
            }
        }
        if ((typeof field.helpPosition == 'undefined' || field.helpPosition != 'top') && help != '') {
            str.append('<p class="micro-copy"><img src="'+OptimizePress.imgurl+'70_888.png" alt="Help" /> '+translate(help)+'</p>');
        }
        if(typeof field.events != 'undefined'){
            if((type == 'image-selector' || type == 'style-selector') && typeof field.events.change != 'undefined'){
                str.delegate('li a','click',function(e){
                    e.preventDefault();
                    $(this).closest('ul').find('a.selected').removeClass('selected').end().end().addClass('selected');
                    var ret = field.events.change($(this).find('img').attr('alt'),op_stored_steps[group][tag]);
                    if(ret === false){
                        e.stopPropagation();
                    }
                });
            } else {
                var trigger = typeof field.trigger_events,
                    el = $('#'+tmp_id);
                for(var i in field.events){
                    el.bind(i,op_stored_steps[group][tag],field.events[i]);
                };
                if(trigger != 'undefined'){
                    if(trigger != 'object'){
                        trigger = [field.trigger_events];
                    } else {
                        trigger = field.trigger_events;
                    }
                    trigger_events['#'+tmp_id] = [];
                    for(var i in trigger){
                        trigger_events['#'+tmp_id].push(trigger[i]);
                    };
                }
            }
        }
        if(typeof field.showOn != 'undefined'){
            if(typeof field.showOn.value == 'object'){
                var tmp_showon = $.extend({},field.showOn);
                for(var i in field.showOn.value){
                    tmp_showon.value = field.showOn.value[i];
                    add_showon(group,tag,id,tmp_showon,idprefix);
                    if (type == 'multirow') {
                        add_showon(group,tag,id + '-multirow-container',tmp_showon,idprefix);
                    }
                }
            } else {
                add_showon(group,tag,id,field.showOn,idprefix);
                if (type == 'multirow') {
                    add_showon(group,tag,id + '-multirow-container',field.showOn,idprefix);
                }
            }
        }
        if(typeof field.showFields != 'undefined'){
            var thefield = $t.find('.field-id-'+tmp_id);
                div = thefield.append('<div class="show-fields"/>').find('.show-fields'),
                tmp_id2 = tmp_id;
            for(var idx in field.showFields){
                generate_field.apply(div,[tmp_id2+'_',idx,field.showFields[idx],group,tag,'show-field-'+idx]);
            };
            div.find('.field-row').hide();
            if(type == 'image-selector' || type == 'style-selector'){
                //$('#'+tmp_id2+'_container .op-asset-dropdown-list a').live('click',function(e){
                $('body').on('click', '#'+tmp_id2+'_container .op-asset-dropdown-list a', function(e){
                    e.preventDefault();
                    var v = $(this).find('img').attr('alt');
                    div.find('.field-row').hide().filter('.show-field-'+v).show();
                });
            } else {
                $('#'+tmp_id2).change(function(){
                    div.find('.field-row').hide().filter('.show-field-'+$(this).val()).show();
                });
            }
        } else if(typeof field.multirow != 'undefined'){
            var div = multirow_container,
                tmp_id2 = tmp_id,
                show_ons_init = false;
            if(typeof div == 'undefined'){
                div = $t.append('<div class="field-id-'+tmp_id+'-multirow-container multirow-container cf" />').find('.field-id-'+tmp_id+'-multirow-container');
            }
            if(type == 'multirow'){
                var multi,
                    prefix = field.multirow.link_prefix || '',
                    suffix = field.multirow.link_suffix || '';
                    prefix = prefix == '' ? '' : translate(prefix)+' ';
                    suffix = suffix == '' ? '' : ' '+translate(suffix),
                    remove_row = '',
                    remove_row_str = '<a href="#" class="remove-row"><img src="'+OptimizePress.imgurl+'remove-row.png" alt="'+translate('remove_row')+'" /></a>';
                $('.field-id-'+tmp_id2+' a.new-row').click(function(e){
                    var cur_i = div.data('op_current_increment'),
                        multi = div.append('<div class="op-multirow cf" />').find('.op-multirow:last');
                    cur_i++;
                    div.data('op_current_increment',cur_i);
                    remove_row =  field.multirow.remove_row || 'before';
                    if(remove_row == 'before'){
                        multi.append(remove_row_str);
                    }
                    for(var idx in field.multirow.attributes){
                        var v = field.multirow.attributes[idx],
                            mtype = v.type || '';
                        generate_field.apply(multi,[tmp_id2+'_'+cur_i+'_',idx,v,group,tag]);
                        if(typeof stored_selectors[tmp_id2+'_'+idx] != 'undefined'){
                            // var el = $('#'+tmp_id2+'_'+cur_i+'_'+idx+'_container').removeClass('loading-asset-dropdown');
                            var el = $('#'+tmp_id2+'_'+cur_i+'_'+idx+'_container');
                            el.find('a.selected-item').html(el.find('.op-asset-dropdown-list').html(stored_selectors[tmp_id2+'_'+idx]).find('a:first').html());
                        }
                        if(typeof v.default_value != 'undefined'){
                            switch(mtype){
                                case 'style-selector':
                                case 'image-selector':
                                    set_selector_value(tmp_id2+'_'+cur_i+'_'+idx+'_container',get_default_val(v));
                                    break;
                                case 'wysiwyg':
                                    set_wysiwyg_content(tmp_id2+'_'+cur_i+'_'+idx,get_default_val(v));
                                    break;
                                case 'paragraph':
                                    $('#'+tmp_id2+'_'+cur_i+'_'+idx).html(get_default_val(v));
                                    break;
                                default:
                                    $('#'+tmp_id2+'_'+cur_i+'_'+idx).val(get_default_val(v));
                                    break;
                            }
                        }
                    };
                    if(remove_row == 'after'){
                        multi.append(remove_row_str);
                    }
                    init_show_ons();
                    if(typeof field.multirow.onAdd == 'function'){

                        //Initialize tinymce editor for added fields
                        // if (op_stored_steps[group][tag][1]) {
                        //  $(op_stored_steps[group][tag][1]).find('.wp-editor-area').each(function () {
                        //      tinyMCE.execCommand("mceAddControl", true, $(this).attr('id'));
                        //  });
                        // }

                        field.multirow.onAdd.apply(multi,[op_stored_steps[group][tag]]);
                    }
                    e.preventDefault();
                });
                $('.field-id-'+tmp_id2+'-multirow-container').delegate('a.remove-row','click',function(e){
                    e.preventDefault();
                    var $parent = $(this).parent();
                    var $closestMultirow;
                    if ($parent.hasClass('pricing-table-row') || $parent.hasClass('op-feature-title-row')) {
                        $parent.remove();
                    } else {
                        $closestMultirow = $(this).closest('.op-multirow');
                        $closestMultirow.parent().next().find('a.new-row').focus();
                        $closestMultirow.remove();
                    }
                });
                for(var idx in field.multirow.attributes){
                    if(typeof field.multirow.attributes[idx].folder != 'undefined'){
                        var mtype = field.multirow.attributes[idx].type || '',
                            folder = field.multirow.attributes[idx].folder;
                        if(mtype == 'style-selector'){
                            add_folder('style',{fieldid:tmp_id2+'_'+idx,group:group,tag:tag,folder:folder},field.multirow.attributes[idx]);
                        } else if(mtype == 'image-selector'){
                            add_folder('image',{fieldid:tmp_id2+'_'+idx,group:group,tag:tag,folder:folder},field.multirow.attributes[idx]);
                        }
                    }
                };
            } else {
                var ul = div.append('<ul class="op-multirow-tabs cf" />').find('.op-multirow-tabs');
                $('#'+tmp_id).change(function(){
                    var v = parseInt($(this).val()),
                        el = div.find('.op-multirow'),
                        vis = ul.find('li:visible').length;
                    if(el.length < v){
                        ul.find('li').show().addClass('visible');
                        var multi,
                            prefix = field.multirow.link_prefix || '',
                            suffix = field.multirow.link_suffix || '';
                            prefix = prefix == '' ? '' : translate(prefix)+' ';
                            suffix = suffix == '' ? '' : ' '+translate(suffix);
                        for(var i=el.length;i<v;i++){
                            multi = div.append('<div class="op-multirow op-multirow-'+i+' cf" />').find('.op-multirow:last');
                            ul.append('<li class="visible"><a href="#'+i+'">'+prefix+(i+1)+'</a></li>');
                            for(var idx in field.multirow.attributes){
                                var mfield = field.multirow.attributes[idx],
                                    mtype = mfield.type || 'input';
                                generate_field.apply(multi,[tmp_id2+'_'+i+'_',idx,mfield,group,tag]);
                                if(typeof mfield.default_value != 'undefined'){
                                    switch(mtype){
                                        case 'style-selector':
                                        case 'image-selector':
                                            set_selector_value(tmp_id2+'_'+i+'_'+idx+'_container',get_default_val(mfield));
                                            break;
                                        case 'wysiwyg':
                                            set_wysiwyg_content(tmp_id2+'_'+i+'_'+idx,get_default_val(mfield));
                                            break;
                                        case 'paragraph':
                                            $('#'+tmp_id2+'_'+i+'_'+idx).html(get_default_val(mfield));
                                            break;
                                        default:
                                            $('#'+tmp_id2+'_'+i+'_'+idx).val(get_default_val(mfield));
                                            break;
                                    }
                                }
                            };
                            if(!show_ons_init){
                                init_show_ons();
                                show_ons_init = true;
                            }
                            if(typeof field.multirow.onAdd == 'function'){
                                field.multirow.onAdd.apply(div,[op_stored_steps[group][tag]]);
                            }
                        }
                    } else {
                        ul.find('li')
                            .filter(':gt('+(v-1)+')')
                                .hide().removeClass('visible')
                            .end()
                            .filter(':lt('+v+')')
                                .show().addClass('visible');
                        el.hide();
                    }
                    el = ul.find('li.visible.selected a');
                    if(el.length == 0){
                        el = ul.find('li.visible:first a');
                    }
                    el.trigger('click');
                }).trigger('change');
            }
        }
    };

    function get_default_val(def){
        if(typeof def.default_value != 'undefined'){
            if($.isFunction(def.default_value)){
                return def.default_value();
            } else {
                return def.default_value;
            }
        }
        return '';
    };

    function fetch_values(group, tag) {
        var attributes = op_stored_configs[group][tag].attributes,
            attrs = {},

            /**
             * Previous line was missing a comma up until 2.1.8.1
             * In case something along  idprefix or ignore lines starts causing errors, other code could be relying on this error.
             */
            idprefix = 'op_assets_' + group + '_' + tag + '_',
            ignore = ['h1','h2','h3','h4','h5','h6','p'],
            styleCheckboxTextModifier = 'text';

        /*
         * If attributes are undefined we return empty object
         */
        if (typeof attributes == 'undefined') {
            return attrs;
        }
        for (var i in attributes) {
            /*
             * Iterating through the attributes
             */
            for(var j in attributes[i]){
                var type = attributes[i][j].type || '';
                if (type == 'column') {
                    /*
                     * Iterating through column elements
                     */
                    for (var k in attributes[i][j].elements) {
                        var type = attributes[i][j].elements[k].type || '';
                        if (type == 'container') {
                            var panel = attributes[i][j].elements[k].showPanelControl || false;
                            /*
                             * We need to see if panel has ON/OFF switch and if it is in ON position
                             */
                            if ((panel == true && $('#panel_control_' + idprefix + k).is(':checked')) || panel == false) {
                                /*
                                 * Iterating through container attributes
                                 */
                                for (var l in attributes[i][j].elements[k].attributes) {
                                    var type = attributes[i][j].elements[k].attributes[l].type || '';
                                    if (type == 'text_properties') {
                                        attrs[l + '_text'] = get_value(idprefix+k+'_'+l + '_text',{});
                                        attrs[l + '_size'] = get_value(idprefix+k+'_'+l + '_size',{});
                                        attrs[l + '_color'] = get_value(idprefix+k+'_'+l + '_color',{});

                                        styleCheckboxTextModifier = (k === 'subtext_box') ? 'subtext' : 'text';
                                        attrs[l + '_bold'] = $('.field-id-' + idprefix+k+'_'+l + ' input[name="op_font[style_checkbox_' + styleCheckboxTextModifier + '][bold]"]').is(':checked') ? 'Y' : '';
                                        attrs[l + '_italic'] = $('.field-id-' + idprefix+k+'_'+l + ' input[name="op_font[style_checkbox_' + styleCheckboxTextModifier + '][italic]"]').is(':checked') ? 'Y' : '';
                                        attrs[l + '_underline'] = $('.field-id-' + idprefix+k+'_'+l + ' input[name="op_font[style_checkbox_' + styleCheckboxTextModifier + '][underline]"]').is(':checked') ? 'Y' : '';

                                        var font = $('#' + idprefix+k+'_'+l + '_container a.selected-item img');
                                        if (font.length > 0) {
                                            attrs[l + '_font'] = font.attr('alt') + ';' + font.attr('data-type');
                                        } else {
                                            attrs[l + '_font'] = '';
                                        }
                                    } else if (type != 'microcopy' && type != 'custom_html' && $.inArray(type,ignore) < 0){
                                        attrs[l] = get_value(idprefix+k+'_'+l,attributes[i][j].elements[k].attributes[l]);
                                    }
                                }
                                attrs[k + '_panel'] = 'Y';
                            } else {
                                attrs[k + '_panel'] = 'N';
                            }
                        } else if (type != 'microcopy' && type != 'custom_html' && $.inArray(type,ignore) < 0){
                            attrs[k] = get_value(idprefix+j,attributes[i][j].elements[k]);
                        }
                    }
                } else if (type == 'container') {
                    /*
                     * Iterating through container attributes
                     */
                    for (var k in attributes[i][j].attributes) {
                        var type = attributes[i][j].attributes[k].type || '';
                        if (type != 'microcopy' && type != 'custom_html' && $.inArray(type,ignore) < 0){
                            attrs[k] = get_value(idprefix+j+'_'+k,attributes[i][j].attributes[k]);
                        }
                    }
                } else if (type != 'microcopy' && type != 'custom_html' && $.inArray(type,ignore) < 0){
                    attrs[j] = get_value(idprefix+j,attributes[i][j]);
                }
            }
        };
        return attrs;
    };

    function insert_tag(group,tag){
        if (disable_insert) return false;
        var ignore = ['h1','h2','h3','h4','h5','h6','p'];

        if(typeof op_stored_configs[group][tag] != 'undefined'){
            var idprefix = 'op_assets_'+group+'_'+tag+'_';
            if(typeof op_stored_configs[group][tag].customInsert == 'function'){
                var attrs = fetch_values(group, tag);
                op_stored_configs[group][tag].customInsert(attrs);
            } else {
                var attr_str = '', content = '', close_tag = false;
                if(typeof op_stored_configs[group][tag].attributes != 'undefined'){
                    for(var j in op_stored_configs[group][tag].attributes){
                        for(var i in op_stored_configs[group][tag].attributes[j]){
                            var type = op_stored_configs[group][tag].attributes[j][i].type || '';
                            if(type != 'microcopy' && type != 'custom_html' && $.inArray(type,ignore) < 0){
                                var v = op_stored_configs[group][tag].attributes[j][i],
                                    val = get_value(idprefix+i,v), add = true;
                                if(typeof val == 'object'){
                                    for(var idx in val){
                                        attr_str += (attr_str==''?'':' ')+i+'_'+idx+'="'+val[idx]+'"';
                                    }
                                } else {
                                    if(!(typeof v.exclude != 'undefined' && v.exclude === true)){
                                        if(typeof v.showOn != 'undefined'){
                                            add = false;
                                            //console.log(v.showOn.field.split('.'));
                                            var showon_field = v.showOn.field.split('.'),
                                                showon_val = get_value(idprefix+showon_field[1],op_stored_configs[group][tag].attributes[showon_field[0]][showon_field[1]]);
                                            if(typeof v.showOn.value == 'object'){
                                                for(var idx in v.showOn.value){
                                                    if(showon_val == v.showOn.value[idx]){
                                                        add = true;
                                                        break;
                                                    }
                                                }
                                            } else {
                                                if(showon_val == v.showOn.value){
                                                    add = true;
                                                }
                                            }
                                        }
                                        if(add){
                                            if(i == 'content'){
                                                close_tag = true;
                                                if(val == ''){
                                                    val = ' ';
                                                }
                                                content = val;
                                            } else {
                                                if(val != ''){
                                                    if(typeof v.attr != 'undefined'){
                                                        i = v.attr;
                                                    }
                                                    attr_str += (attr_str==''?'':' ')+i+'="'+val+'"';
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    };
                }
                var str = '['+tag+(attr_str==''?'':' ')+attr_str+']';
                if(close_tag){
                    str += content+'[/'+tag+']';
                }
                str += '';
                wpActiveEditor = wpActiveEditor || 'content';
                insert_content(str);
                $.fancybox.close();
            }
        }

        return true;
    };

    function get_value(id,field){
        var type = field.type || 'input';
        if(typeof field.multirow != 'undefined'){
            el = $('.field-id-'+id+'-multirow-container');
            if(type == 'multirow'){
                var multirow = [];
                el.find('.op-multirow, .op-feature-title-row').each(function(){
                    var $t = $(this), row = {};
                    for(var idx in field.multirow.attributes){
                        var mtype = field.multirow.attributes[idx].type || '',
                            mid;
                        if(mtype == 'style-selector' || mtype == 'image-selector'){
                            mid = $t.find('div[id$="_'+idx+'_container"]').attr('id');
                            mid = mid.substring(0,(mid.length-10));
                        } else {
                            mid = $t.find(':input[id$="_'+idx+'"]').attr('id');
                        }
                        row[idx] = get_value(mid,field.multirow.attributes[idx]);
                    };
                    multirow.push(row);
                });
            } else {
                var multirow = {
                    total: get_value(id,{type:field.type}),
                    rows: []
                };
                for(var i=0;i<multirow.total;i++){
                    var row = {};
                    for(var idx in field.multirow.attributes){
                        row[idx] = get_value(id+'_'+i+'_'+idx,field.multirow.attributes[idx]);
                    };
                    multirow.rows.push(row);
                };
            }
            return multirow;
        } else if(type == 'tabs'){
            var tabs = {};
            for(var i in field.tabs){
                tabs[i] = {};
                for(var j in field.tabs[i].fields){
                    tabs[i][j] = {};
                    if (field.tabs[i].fields[j].type == 'column') {
                        /*
                         * Iterating through column elements
                         */
                        for (var k in field.tabs[i].fields[j].elements) {
                            var type = field.tabs[i].fields[j].elements[k].type || '';
                            if (type == 'container') {
                                var panel = field.tabs[i].fields[j].elements[k].showPanelControl || false;
                                /*
                                 * We need to see if panel has ON/OFF switch and if it is in ON position
                                 */
                                if ((panel == true && $('#panel_control_' + id + '_' + i + '_' + k).is(':checked')) || panel == false) {
                                    /*
                                     * Iterating through container attributes
                                     */
                                    for (var l in field.tabs[i].fields[j].elements[k].attributes) {
                                        var type = field.tabs[i].fields[j].elements[k].attributes[l].type || '';
                                        if (type == 'text_properties') {
                                            tabs[i][j][l + '_text'] = get_value(id+'_'+i+'_'+k+'_'+l + '_text',{});
                                            tabs[i][j][l + '_size'] = get_value(id+'_'+i+'_'+k+'_'+l + '_size',{});
                                            tabs[i][j][l + '_color'] = get_value(id+'_'+i+'_'+k+'_'+l + '_color',{});
                                            tabs[i][j][l + '_bold'] = $('.field-id-' + id+'_'+i+'_'+k+'_'+l + ' input[name="op_font[style_checkbox_text][bold]"]').is(':checked') ? 'Y' : '';
                                            tabs[i][j][l + '_italic'] = $('.field-id-' + id+'_'+i+'_'+k+'_'+l + ' input[name="op_font[style_checkbox_text][italic]"]').is(':checked') ? 'Y' : '';
                                            tabs[i][j][l + '_underline'] = $('.field-id-' + id+'_'+i+'_'+k+'_'+l + ' input[name="op_font[style_checkbox_text][underline]"]').is(':checked') ? 'Y' : '';
                                            var font = $('#' + id+'_'+i+'_'+k+'_'+l + '_container a.selected-item img');
                                            if (font.length > 0) {
                                                tabs[i][j][l + '_font'] = font.attr('alt') + ';' + font.attr('data-type');
                                            } else {
                                                tabs[i][j][l + '_font'] = '';
                                            }
                                        } else if (type != 'microcopy' && type != 'custom_html'){
                                            tabs[i][j][l] = get_value(id+'_'+i+'_'+k+'_'+l,field.tabs[i].fields[j].elements[k].attributes[l]);
                                        }
                                    }
                                    tabs[i][j][k + '_panel'] = 'Y';
                                } else {
                                    tabs[i][j][k + '_panel'] = 'N';
                                }
                            } else if (type != 'microcopy' && type != 'custom_html'){
                                tabs[i][j][k] = get_value(id+'_'+j,field.tabs[i].fields[j].elements[k]);
                            }
                        }
                    } else {
                        tabs[i][j] = get_value(id+'_'+i+'_'+j,field.tabs[i].fields[j]);
                    }
                }
            }
            return tabs;
        } else {
            var val;
            switch(type){
                case 'slider':
                    val = $('#' + id).slider('value');
                    break;
                case 'text_properties':
                    break;
                case 'font':
                    var cont = $('.field-id-'+id), font_elems = {'size':['select:first','val'],'font':['.op-asset-dropdown a:first img','attr','alt'],'style':['select[name$="[style]"]','val'],'color':['.color-picker-container :input','val'],'spacing':['select[name$="[spacing]"]','val'],'shadow':['select[name$="[shadow]"]','val']};
                    val = {};
                    if(typeof field.size_text != 'undefined'){
                        font_elems.size[0] = ':input[name$="[size]"]';
                    }
                    for(var i in font_elems){
                        var tmp = $(font_elems[i][0],cont), tmpval = '';
                        if(tmp.length > 0){
                            if(typeof font_elems[i][2] != 'undefined'){
                                tmpval = tmp[font_elems[i][1]](font_elems[i][2]);
                            } else {
                                tmpval = tmp[font_elems[i][1]]();
                            }
                        }
                        if(tmpval != ''){
                            val[i] = tmpval;
                        }
                    };
                    break;
                case 'image-selector':
                case 'style-selector':
                    val = get_selector_value(id+'_container');
                    break;
                case 'checkbox':
                    val = $('#'+id).is(':checked') ? 'Y' : '';
                    break;
                case 'radio':
                    val = $('.field-id-'+id+' :radio:checked').val();
                    break;
                case 'textarea':
                    var format = field.format || 'op_unautop';
                    val = $('#'+id).val();
                    if (format === 'custom') {
                        val = val;
                    } else {
                        val = (format == 'br' ? nl2br(val) : op_wpautop(val));
                    }
                    //val = val.replace(/\n\r?/g, '<br />');
                    break;
                case 'wysiwyg':
                    val = get_wysiwyg_content(id);
                    break;
                case 'paragraph':
                    val = $('#'+id).html();
                    break;
                default:
                    val = $('#'+id).val();
                    break;
            }
            if(typeof field.showFields != 'undefined'){
                var show_fields = {
                    value: val,
                    fields: {}
                };
                for(var idx in field.showFields){
                    if(show_fields.value == idx){
                        show_fields.fields[idx] = get_value(id+'_'+idx,field.showFields[idx]);
                    }
                };
                return show_fields;
            } else {
                return val;
            }
        }
    };

    function add_wysiwyg(id,content){
        var fragment, $fragment;

        if(use_wysiwyg){
            /*if ('op_assets_core_custom_html_content' === id) {}*/
            content = content || '';
            //this.append('<div class="op-wysiwyg">'+$('#op_dummy_wysiwyg').html().replace(/opassetswysiwyg/ig,id)+'</div>');
            $('#qt_'+id+'_toolbar').remove();
            fragment = $('#op_dummy_wysiwyg').html().replace(/opassetswysiwyg/ig,id);
            $fragment = $(fragment);
            $fragment.find('#qt_' + id + '_toolbar').remove();
            $('#' + id + '_tbl').remove();
            this.append('<div class="op-wysiwyg">' + '<div id="wp-' + id + '-wrap" class="wp-core-ui wp-editor-wrap tmce-active">' + $fragment.html() + '</div>' + '</div>');
            $('#qt_'+id+'_toolbar').remove();
            tinyMCEPreInit.mceInit[id] = tinyMCEPreInit.mceInit.opassetswysiwyg;
            if ( typeof QTags == 'function' ) {
                var tmpq = {};
                if(typeof tinyMCEPreInit.qtInit.opassetswysiwyg != 'undefined'){
                    tmpq = tinyMCEPreInit.qtInit.opassetswysiwyg;
                } else {
                    tmpq = tinyMCEPreInit.qtInit.content;
                }
                tmpq.id = id;
                try {
                    quicktags(tmpq);
                    QTags._buttonsInit();
                } catch(e){}
            }
            tinyMCE.execCommand('mceRemoveControl', true, id);
            $('#' + id + '_tbl').remove();
            //document.getElementById(id).style.display = 'block';
            $('#' + id).css({ 'display': 'block', 'height': '100px' });

            // Zvonko thinks that this is not needed
            // This is needed only when wysiwyg is dynamically added to the page (for example on testimonials element when you click add),
            // therefore initialization was moved into multirow.addOn function line 887 of this file
            // In live editor tis is needed for edit elements.
            if ($('.op-live-editor').length > 0) {
                tinyMCE.execCommand("mceAddControl", true, id);
            }
        } else {
            this.append('<textarea cols="30" rows="10" id="'+id+'" name="'+id+'" />');
        }
    };

    function add_font_settings(id,field){
        var font_elems = {'size':'','font':'.font-dropdown','style':'','spacing':'','shadow':''}, el, str = '', disable = field.disable || {};
        for(var i in font_elems){
            if(typeof stored_elements[i] != 'undefined'){
                continue;
            }
            el = $('#op_font'+i);
            if(font_elems[i] != ''){
                el = el.find(font_elems[i]);
            }
            stored_elements[i] = el.html();
        }
        if(typeof disable.size == 'undefined'){
            str += (typeof field.size_text != 'undefined' ? '<input type="text" name="'+id+'[size]" id="'+id+'_size" />px':'<select name="'+id+'[size]" id="'+id+'_size">'+stored_elements['size']+'</select>');
        }
        if(typeof disable.font == 'undefined'){
            str += _style_selector_html(id,stored_elements['font'],'font-view');
        }
        if(typeof disable.style == 'undefined'){
            str += '<select name="'+id+'[style]" id="'+id+'_style">'+stored_elements['style']+'</select>';
        }
        if(typeof disable.color == 'undefined'){
            str += '<div class="color-picker-container cf">'+
            '<input type="text" value="" id="'+id+'_color" name="'+id+'[color]" /><a href="#" class="op-pick-color hide-if-no-js"></a>'+
            '</div>';
        }
        if(typeof disable.spacing == 'undefined'){
            str += '<select name="'+id+'[spacing]" id="'+id+'_spacing">'+stored_elements['spacing']+'</select>';
        }
        if(typeof disable.shadow == 'undefined'){
            str += '<select name="'+id+'[shadow]" id="'+id+'_shadow">'+stored_elements['shadow']+'</select>';
        }
        if(str != ''){
            str = '<div class="op-typeography font-chooser cf">'+str+'</div><a href="#reset-font" class="reset">'+translate('reset')+'</a>';
            return str;
        }
        return '';
    };

    function add_text_properties(id, field) {

        var elements = {'size':'','font':'.font-dropdown','style_checkbox_text':'','style_checkbox_subtext':''}, el, str = '';
        if (id === 'op_assets_core_button_text_box_text_properties_1' ||
            id === 'op_assets_core_membership_order_button_text_box_text_properties_1' ||
            id === 'op_assets_core_membership_order_button_text_box_text_properties_1' ||
            id === 'op_assets_core_op_popup_text_box_text_properties_1' ||
            id === 'op_assets_core_optin_box_tabs_submit_button_text_box_text_properties_1' ||
            id === 'op_assets_core_optin_modal_tabs_submit_button_text_box_text_properties_1') {
            var style_name = 'style_checkbox_text';
        } else {
            var style_name = 'style_checkbox_subtext';
        }
        for (var i in elements) {
            if (typeof stored_elements[i] != 'undefined') {
                continue;
            }
            el = $('#op_font'+i);
            if (elements[i] != '') {
                el = el.find(elements[i]);
            }
            stored_elements[i] = el.html();
        }

        /*
         * Text subelement
         */
        str += '<input type="text" name="'+id+'[text]" id="'+id+'_text" value="' + (typeof field.text_default != 'undefined' ? field.text_default : '') + '" />';

        /*
         * Size subelement
         */
        str += (typeof field.size_text != 'undefined' ? '<input type="text" name="'+id+'[size]" id="'+id+'_size" />px':'<select name="'+id+'[size]" id="'+id+'_size">'+stored_elements['size']+'</select>');

        /*
         * Font family subelement
         */
        str += _style_selector_html(id,stored_elements['font'],'font-view');

        /*
         * Color picker subelement
         */
        str += '<div class="color-picker-container cf"><input type="text" value="" id="'+id+'_color" name="'+id+'[color]" /><a href="#" class="op-pick-color hide-if-no-js"></a></div>';

        /*
         * Style subelement
         */
        str += '<div class="style-checkbox-selector cf">' + stored_elements[style_name] + '</div>';

        return str;
    }

    function value_fields(type,id,value,label,selected,single){
        var str = '';
        single = single || false;
        label = label || '';
        selected = selected || false;
        switch(type){
            case 'checkbox':
                str = '<input type="checkbox" name="'+id+(single?'" id="'+id+'"':'[]"')+' value="'+value+'"'+(selected?' checked="checked"':'')+' />';
                if(label != ''){
                    str = '<label>'+str+' '+label+'</label>';
                }
                break;
            case 'radio':
                str = '<input type="radio" name="'+id+'[]" value="'+value+'"'+(selected?' checked="checked"':'')+' />';
                if(label != ''){
                    str = '<label>'+str+' '+label+'</label>';
                }
                break;
            case 'select':
                str = '<option value="'+value+'"'+(selected?' selected="selected"':'')+'>'+label+'</option>';
                break;
            case 'membership_select':
                // value="789-658"
                if (value != '') {
                    var temp = value.split('-');
                    value = temp[1];
                    classStr = temp[0];
                } else {
                    classStr = '';
                    value = '';
                }
                str = '<option class="parent-' + classStr + '" value="'+value+'"'+(selected?' selected="selected"':'')+'>'+label+'</option>';
                break;
        }
        return str;
    };
    function input_values(id,field,type,group,tag){
        var str = '',
            def_val = get_default_val(field);
        if(typeof field.folder != 'undefined'){
            add_folder(type,{fieldid:id,group:group,tag:tag,folder:field.folder},field);
        } else if(typeof field.valueRange != 'undefined'){
            var end = (field.valueRange.finish+1),
                prefix = field.valueRange.text_prefix || '',
                suffix = field.valueRange.text_suffix || '';
            prefix = prefix == '' ? '' : translate(prefix)+' ';
            suffix = suffix == '' ? '' : ' '+translate(suffix);
            for(var i=field.valueRange.start;i<end;i++){
                str += value_fields(type,id,i,prefix+i+suffix,(def_val==i));
            }
        } else if(typeof field.values != 'undefined') {
            for(var value in field.values){
                str += value_fields(type,id,value,translate(field.values[value]),(def_val==value));
            };
        } else {
            str += value_fields(type,id,'Y','',(def_val=='Y'),true);
        }
        return str;
    };


    function init_show_ons(){
        for(var i in show_ons){
            var cont = container.find('.'+i),
                changes = [];
            for(var field in show_ons[i]){
                var fieldinfo = show_ons[i][field];
                if(fieldinfo.type == 'image-selector' || fieldinfo.type == 'style-selector' || fieldinfo.type == 'button_preview'){
                    //$('#'+field+'_container').data({'show_on_values':fieldinfo.values}).find('.op-asset-dropdown-list a').live('click',function(e){
                    $('#'+field+'_container').data({'show_on_values':fieldinfo.values});
                    $('#'+field+'_container').on('click', '.op-asset-dropdown-list a', function(e){
                        e.preventDefault();
                        show_hide_fields.apply(cont,[$(this).find('img').attr('alt'),$(this).closest('.op-asset-dropdown').data('show_on_values')]);
                    });
                } else {
                    changes.push('#'+field);
                    cont.find('#'+field).data({
                        'show_on_values': fieldinfo.values
                    }).change(function(){
                        show_hide_fields.apply(cont,[$(this).val(),$(this).data('show_on_values')]);
                    }).trigger('change');
                }
            };
        };

        show_ons = {};
    };
    function show_hide_fields(val,values){

        var $t = this,
            show = '',
            hidden = {},
            allValues = [],
            tempValues,
            tempDisplayTypes,
            value,
            valuesToShow,
            displayTypesToShow,
            i = 0;

        for (value in values) {
            tempValues = [];
            tempDisplayTypes = [];

            for (i = 0; i < values[value].length; i += 1) {
                tempValues.push(values[value][i].selector);
                tempDisplayTypes.push(values[value][i].displayType);
            }

            allValues.push(tempValues);

            //var fields = values[value].join(',');
            var fields = tempValues.join(',');

            if (value == val) {
                show = fields;
                valuesToShow = tempValues;
                displayTypesToShow = tempDisplayTypes;
            } else {
                if (typeof hidden[fields] == 'undefined') {
                    hidden[fields] = true;
                    $t.find(fields).css('display','none');
                }
            }
        }

        if (show != '') {
            $(show).css('display','block').find(':input').trigger('change');
            for (i = 0; i < valuesToShow.length; i += 1) {
                //$(tempValues[i]).css('display','block').find(':input').trigger('change');
                $(valuesToShow[i]).css('display',displayTypesToShow[i]).find(':input').trigger('change');
            }
        }

    }

    function add_showon(group,tag,id,showOn,idprefix){
        var key = 'op-settings-'+group+'-'+tag,
            field = showOn.field.split('.'),
            fieldid = field[field.length-1];
        fieldid = (typeof showOn.idprefix != 'undefined') ? showOn.idprefix+fieldid : idprefix+fieldid;
        if(typeof show_ons[key] == 'undefined'){
            show_ons[key] = {};
        }
        if(typeof show_ons[key][fieldid] == 'undefined'){
            show_ons[key][fieldid] = {
                values:{}
            };
            var el = op_stored_configs[group][tag].attributes;

            for(var i=0,il=field.length;i<il;i++){
                el = el[field[i]];
            };
            show_ons[key][fieldid].type = (typeof showOn.type != 'undefined' ? showOn.type : el.type || 'input');
        }
        if(typeof show_ons[key][fieldid].values[showOn.value] == 'undefined'){
            show_ons[key][fieldid].values[showOn.value] = [];
        }

        show_ons[key][fieldid].values[showOn.value].push({
            selector: '.field-id-'+idprefix+id,
            displayType: showOn['displayType'] || 'block'
        });
    };

    function add_folder(type,folder,field){
        if(typeof field.asset != 'undefined'){
            folder.group = field.asset[0];
            folder.tag = field.asset[1];
        }
        if(typeof field.ignore_vals != 'undefined'){
            folder.ignore_vals = field.ignore_vals;
        }
        if(typeof folders[type] == 'undefined'){
            folders[type] = [];
        }
        folders[type].push(folder);
    };

    function _preset_selector_html(id,html,addclass){
        html = html || '';
        return '<div class="op-asset-dropdown-list">' + html + '</div>';
    };

    function _style_selector_html(id,html,addclass){
        html = html || '';
        if(addclass != ''){
            selector_classes[addclass] = true;
        }
        return '<div class="op-asset-dropdown'+(html==''?' loading-asset-dropdown':'')+' '+addclass+'" id="'+id+'_container"><a class="selected-item" href="#"></a><div class="op-asset-dropdown-list">'+html+'</div></div>';
    };

    var color_pickers_initialized = false;
    function init_color_pickers(){
        if($('#op-color-picker').length == 0){
            $('body').append('<div id="op-color-picker" />');
        }
        picker = $('#op-color-picker');
        farbtastic = $.farbtastic('#op-color-picker',pick_color);
        //$('.color-picker-container :input',container).live('focus',function(){
        container.on('focus', '.color-picker-container :input', function(){
            $cp_link = (typeof($(this).data('cp_link'))=='undefined' ? $(this).siblings('a.op-pick-color') : $(this).data('cp_link'));
            $(this).data('cp_link', $cp_link);
            $cp_link.trigger('click');
        //}).live('blur',function(){
        }).on('blur', '.color-picker-container :input', function(){
            var $t = $(this),
                c = get_color($t.val());
            c = (c === '#') ? '' : c;
            if (current_picker && current_picker[1]) {
                pick_color(c);
            }
            picker.hide();
        //}).live('change',function(){
        }).on('change', '.color-picker-container :input', function(){
            var $t = $(this),
                c = get_color($t.val());
            $cp_link = (typeof($t.data('cp_link'))=='undefined' ? $(this).siblings('a.op-pick-color') : $t.data('cp_link'));
            $(this).data('cp_link', $cp_link);
            $cp_link.css('background-color',(c == '#' ? 'transparent' : c));
            if(picker.is(':visible') && c != '#'){
                farbtastic.setColor(c);
            }
        });
        //$('a.op-pick-color',container).live('click',function(e){
        container.on('click', 'a.op-pick-color', function(e){
            $input = (typeof($(this).data('input'))=='undefined' ? $(this).prev('input') : $(this).data('input'));
            $(this).data('input', $input);
            current_picker = [$(this),$input];
            pick_color(current_picker[1].val());
            picker.position({
                of: current_picker[0],
                my: "left top",
                at: "left bottom"
            }).show();
            e.preventDefault();
        });
    };
    function insert_content(str){
        if(typeof op_le_insert_content != 'undefined'){
            op_le_insert_content(str);
        } else {
            send_to_editor(str+'<br /><br />');
        }
    };

    function set_settings(settings,steps,config){
        if(typeof config.customSettings == 'function'){
            config.customSettings(settings,steps);
        } else {
            var idprefix = 'op_assets_'+settings.asset[0]+'_'+settings.asset[1]+'_',
                attrs = settings.attrs || {};
            for(var i=1;i<4;i++){
                if(typeof config.attributes['step_'+i] != 'undefined'){
                    for(var j in config.attributes['step_'+i]){
                        var field = config.attributes['step_'+i][j],
                            type = field.type || 'input',
                            val = attrs[j] || '';
                        switch(type){
                            case 'multirow':
                                val = settings[j] || '';
                                if(typeof val == 'object'){
                                    var add_link = steps[i-1].find('.field-id-'+idprefix+j+' a.new-row'),
                                        element_container = steps[i-1].find('.field-id-'+idprefix+j+'-multirow-container'),
                                        cur_element;
                                    for(var k=0,kl = val.length;k<kl;k++){
                                        add_link.trigger('click');
                                        cur_element = element_container.find('> .op-multirow:last');
                                        $.each(field.multirow.attributes,function(idx,v){
                                            var tmp_val = val[k].attrs[idx] || '';
                                            if((v.type || 'input') == 'textarea'){
                                                tmp_val = op_unautop(tmp_val);
                                            }
                                            cur_element.find(':input[name$="'+idx+'"]').val(tmp_val);
                                        });
                                    }
                                }
                                break;
                            case 'style-selector':
                            case 'image-selector':
                                set_selector_value(idprefix+j+'_container',val);
                                //var  el = steps[i-1].find('.op-asset-dropdown-list img'+(val==''?':first':'[alt="'+val+'"]')).parent().trigger('click');
                                break;
                            case 'checkbox':
                                $('#'+idprefix+j).attr('checked',(val=='Y')).trigger('change');
                                break;
                            case 'radio':
                                $('.field-id-'+idprefix+j+' :radio[value="'+val+'"]').attr('checked',true).trigger('change');
                                break;
                            case 'font':
                                set_font_settings(j,attrs,idprefix+j)
                                break;
                            case 'wysiwyg':
                                set_wysiwyg_content(idprefix+j,val);
                                break;
                            case 'textarea':
                                var format = field.format || 'op_unautop';
                                if (format === 'custom') {
                                    $('#'+idprefix+j).val(val);
                                } else {
                                    $('#'+idprefix+j).val((format == 'br' ? br2nl(val) : op_unautop(val)));
                                }
                                break;
                            case 'media':
                                set_uploader_value(idprefix+j,val);
                                break;
                            case 'paragraph':
                                if (typeof val == 'string' && val != '') {
                                    $('#'+idprefix+j).html(val).trigger('change');
                                }
                                break;
                            default:
                                $('#'+idprefix+j).val(val).trigger('change');
                                break;
                        };
                    };
                }
            };
        }
    };

    function edit_element(settings){

        var settingsAsset = settings.asset;

        container.addClass('edit-mode');
        disable_slide = true;
        disable_insert = true;

        if(typeof op_assets[settingsAsset[0]] != 'undefined' && typeof op_assets[settingsAsset[0]][settingsAsset[1]] != 'undefined'){
            set_titles(slides[1].find('a[href$="#'+settingsAsset[0]+'/'+settingsAsset[1]+'"]'));
            container.find('.op_asset_browser_slide .settings-container').css('display','none');
            load_config(settingsAsset, function(steps,config){
                set_settings(settings,steps,config);
                disable_insert = false;
                disable_slide = false;
                if(typeof config.default_slide != 'undefined'){
                    if($.isFunction(config.default_slide)){
                        show_slide(config.default_slide(steps));
                    } else {
                        show_slide(config.default_slide);
                    }
                } else if(typeof steps[1] != 'undefined'){
                    show_slide(3, false);
                } else {
                    show_slide(2, false);
                }
            });
        }
    };


    function init_color_picker(form){
        $('.color-picker-container :input',form).each(function(){
            var atag = $(this).siblings('a.op-pick-color');
            atag.data('input',$(this));
            $(this).data('cp_link',atag).data('cp_link').css('background-color',get_color($(this).val()));
        });
    };
    function pick_color(color){
        farbtastic.setColor(color);
        current_picker[1].val(color);
        current_picker[0].css('background-color',color);
        $('#op_asset_browser_slide3 .op-settings-' + current_asset[0] + '-' + current_asset[1]).trigger({type: 'update_button_preview', id: current_picker[1].attr('id'), value: color, tag: current_asset[1]});
    };
    function get_color(val){
        return '#'+val.replace(/[^a-fA-F0-9]/, '');
    };


    function translate(s){
        if (!s || typeof s !== 'string') {
            return '';
        }
        return op_assets_lang[s] || s.replace(/{\#([^}]+)\}/g, function(a, b) {
            return op_assets_lang[b] || '{#' + b + '}';
        });
    };


    function get_wysiwyg_content(id){
        var content = '';
        if(use_wysiwyg){
            var ed = tinyMCE.get(id);
            if((ed && ed.isHidden()) || !ed){
                content = op_wpautop($('#'+id).val());
            } else {
                content = ed.getContent();
            }
        } else {
            content = $('#'+id).val();
        }
        if(wp_post){
            content = op_wpautop(content);
        } else {
            content = op_unautop(content);
        }
        return content;
    };

    function set_wysiwyg_content(id,content){
        if (use_wysiwyg) {
            //switchEditors.go(id, 'tmce');
            $('#wp-'+id+'-wrap').removeClass('html-active').addClass('tmce-active');
            var ed = tinyMCE.get(id);
            if (ed) {
                ed.setContent(op_wpautop(content),{ no_events: true });
            } else {
                if ($('.op-live-editor').length === 0) {
                    switchEditors.go(id,'tmce');
                }
                $('#'+id).val(op_unautop(content));
            }
        } else {
            $('#'+id).val(op_unautop(content));
        }
    };

    $.fn.get_hash = function(){
        return $.get_hash(this.attr('href'));
    };
    $.get_hash = function(el){
        el = el.split('#');
        return el[1];
    };
    $.object_length = function(obj){
        var c = 0;
        for(var i in obj){
            c++;
        };
        return c;
    };

    function set_selector_value(id,value){
        /*
         * We don't want preview element to be prefilled with first item...
         */
        if (id == 'op_assets_core_button_button_preview_container' ||
            id == 'op_assets_core_membership_order_button_button_preview_container' ||
            id == 'op_assets_core_optin_box_tabs_submit_button_button_preview_container' ||
            id == 'op_assets_core_optin_modal_tabs_submit_button_button_preview_container' ||
            id == 'op_assets_core_op_popup_button_preview_container') {
            return;
        }
        value = typeof value != 'undefined' ? value : '';
        var el = $('#'+id),
            p = el.parent(),
            d = p.hasClass('op-disable-selected'),
            show = el.data('show_on_values');
        if(value == ''){
            if(d){
                el.find('.selected').removeClass('selected');
                value = null;
            } else {
                value = $('#'+id+' a.selected-item').html($('#'+id+' .op-asset-dropdown-list a:first').html()).find('img').attr('alt');
            }
        } else {
            if(d){
                el.find('.selected').removeClass('selected').end().find('img[alt="'+value+'"]').parent().trigger('click');
            } else {
                el.find('a.selected-item').html(el.find('img[alt="'+value+'"]').parent().html());
            }
        }
        if(typeof show != 'undefined' && value !== null){
            show_hide_fields.apply(el.closest('.op_asset_browser_slide'),[value,show]);
        }
    };

    function get_selector_value(id){
        var el = $('#'+id);
        if(el.closest('.field-row').hasClass('op-disable-selected')){
            el = el.find('.selected img');
        } else {
            el = el.find('.selected-item img');
        }
        return el.attr('alt');
    };

    /*
    * Function: set_color_value
    * Description: Sets the input elements for a color selector
    * Parameters:
    *   id: ID of the color element (the HTML element is the textbox containing the color code)
    *   value: Value to be inserted into element
    */
    function set_color_value(id, value){
        //Set textbox and then the background color of the preview next to it
        $('#' + id).val(value).next('a.op-pick-color').css({ backgroundColor: value });
    };

    function set_font_settings(fieldname,values,fieldid){
        var chks = ['size','font','style','color','spacing','shadow'];
        $.each(chks,function(i,v){
            var varname = fieldname+'_'+v,
                val = values[varname] || '';
            if(v == 'font'){
                set_selector_value(fieldid+'_container',val);
            } else {
                $('#'+fieldid+'_'+v).val(val).trigger('change');
            }
        });
    };

    /*
    * Function: set_select_value
    * Description: Sets the value for a select element
    * Parameters:
    *   id: ID of the select element
    *   value: value to be selected by default
    */
    function set_select_value(id, value){
        //If we are not passed a jQuery object, create one from the passed ID string
        if (typeof(id)=='string') id = $('#' + id);

        //Loop through all the option elements inside the select and set selected if the value desired is found
        id.find('option').each(function(){
            if ($(this).val()==value) $(this).attr('selected', 'selected');
        });
    };

    function set_uploader_value(id,value,callback){
        callback = callback === false ? false : true;
        var el = $('#'+id),
            content = el.next().find('.content');
        if(typeof value == 'undefined' || value == ''){
            content.html('');
            el.val('');
            if(callback){
                el.trigger('change');
            }
            return;
        }
        var ext = value.match(/\.([^.]+)$/);
        el.val(value);
        if(callback){
            el.trigger('change');
        }
        ext = ext === null ? false : ext[1];
        if($.inArray(ext,['jpg','jpeg','gif','png']) > -1){
            content.html('<a href="'+value+'" target="_blank" class="preview-image"><img src="'+value+'" alt="uploaded-image" /></a><a href="#remove" class="remove-file">'+translate('remove_image')+'</a>');
        } else {
            content.html('<a href="'+value+'" target="_blank" class="preview-image">'+translate('view_file')+'</a><a href="#remove" class="remove-file">'+translate('remove_file')+'</a>');
        }
    };

    function get_column_width(default_val){
        default_val = default_val || '';
        if(typeof op_le_column_width != 'undefined'){
            return op_le_column_width();
        }
        return default_val;
    };
    function disable_asset_wysiwygs(){
        if(!use_wysiwyg || wysiwygs_checked === true){
            return;
        }
        wysiwygs_checked = true;
        if(editor_switch && typeof this.content != 'string'){
            this.content.find('.wp-editor-area').each(function(i){
                var id = $(this).attr('id'),
                    ed = tinyMCE.get(id);
                if(ed && id != 'opassetswysiwyg'){
                    tinyMCE.execCommand('mceFocus', false, id);
                    tinyMCE.execCommand('mceRemoveControl', false, id);
                }
            });
        }
    };
    function open_asset_dialog(slide){
        slide = slide === 0 ? 0 : 1;
        $.fancybox.open({
            type: 'inline',
            scrolling: 'no',
            autoSize: false,
            fitToView: false,
            helpers: {
                overlay: {
                    closeClick: false
                }
            },
            keys: false,
            margin: 0,
            padding: 0,
            width: 760,
            height: 500,
            minHeight: 500,
            maxHeight: 500,
            href: '#op_asset_browser_container',
            beforeClose: disable_asset_wysiwygs,
            beforeLoad: function () {
                $('#op_asset_browser_container').css({ position: 'fixed', top: 0, height: '100%', bottom: 0 });
            },
            beforeShow: function(){
                resize_content_areas();
                show_slide(slide,false);

                //This is necessary in order to hide the parent fancybox scrollbars and close button
                $('html').css({
                    overflow: 'hidden',
                    height: '100%'
                });
                $(window.parent.document).find('.fancybox-close').css({ display: 'none' });
            },
            afterShow: function () {
                var $assetBrowserContainer;
                $('#op_asset_browser_container')
                    .css({ position: 'static', top: 'auto' })
                    .find('.op_asset_content').css({ height: '100%' });

                //Get the last opened fancybox (in case there's more of them)
                $fancyBoxOpened = $('.fancybox-opened').eq(-1);
                $fancyBoxIframe = $fancyBoxOpened.find('iframe');
                if ($fancyBoxIframe.length > 0) {
                    $fancyBoxIframe.focus();
                } else {
                    $assetBrowserContainer = $fancyBoxOpened.find('#op_asset_browser_container');
                    if ($assetBrowserContainer.length > 0) {
                        $fancyBoxOpened
                            .find('.op_asset_browser_slide')
                                .eq(slide)
                                .addClass('op_asset_browser_slide_active')
                                .find('.op_asset_content')
                                    .addClass('op_no_outline')
                                    .attr('tabindex', 0)
                                    .focus();
                        focusLastSelectedItem();
                    } else {
                        $fancyBoxOpened
                            .find('.fancybox-inner')
                                .addClass('op_no_outline')
                                .attr('tabindex', 0)
                                .focus();
                    }
                }
            },
            afterClose: function(){
                wysiwygs_checked = false;
                show_slide(1);
                container.find('.multirow-container:not(.field-type-tabs)').find('.op-multirow-tabs li').remove().end().find('.op-multirow').remove();

                // Since fancybox is closed, no slide is active anymore.
                $('#op_asset_browser_slider .op_asset_browser_slide_active').removeClass('op_asset_browser_slide_active');

                //This is necessary in order to hide the parent fancybox scrollbars and close button
                $('html').css({
                    overflow: 'auto',
                    height: 'auto'
                });
                $(window.parent.document).find('.fancybox-close').css({ display: 'block' });
            }
        });
    };

    function nl2br(str){
        str = str.replace(/(\r\n|\n)/g,'<br />');
        return str;
    };
    function br2nl(str){
        str = str.replace(/<br\s*\/?>/g,"\n",str);
        return str;
    };


    function set_help_videos(group,tag){
        var vids = help_vids[group][tag],
            tmp,
            el;
        for(i=0;i<3;i++){
            tmp = vids[i];
            el = slide_content[i+2].find('.help-vid-link');
            if(tmp != ''){
                el.html(tmp);
            } else {
                el.html(help_vids.defaults[i]);
            }
        };
    };

    function generate_video_link(vid){
        var opts = "{'playlist':[{'autoPlay':true,'autoBuffering':true,'url':'"+vid.url.split('/').join('\\/')+"'}],'plugins':{'controls':{'url':'"+OptimizePress.flowplayer_control.split('/').join('\\/')+"'}}}";
        return '<a href="'+OptimizePress.flowplayer+'?config='+opts+'" title="'+translate('help_video')+'" tabindex="-1" class="help-me"><span>?</span></a> \
<div> \
    <input type="hidden" name="help_vid_width[]" value="'+vid.width+'" class="help_vid_width" /> \
    <input type="hidden" name="help_vid_height[]" value="'+vid.height+'" class="help_vid_height" /> \
</div>';
    };

    function init_help_videos(){
        selector_classes['help-video'] = true;
        container.delegate('a.help-me','click',function(e){
            e.preventDefault();
            var $t = $(this),
                info = $t.next(),
                width = info.find('.help_vid_width').val(),
                height = info.find('.help_vid_height').val();
            e.preventDefault();
            for(var i in selector_classes){
                panda_content.removeClass(i);
            }
            panda_content.addClass('help-video');
            var content = '<div style="width:{width}px;height:{height}px;margin:0 auto"><object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" width="{width}" height="{height}"><param name="wmode" value="transparent" /><param name="allowfullscreen" value="true" /><param name="allowscriptaccess" value="always" /><param name="movie" value="{href}" /><embed src="{href}" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="{width}" height="{height}" wmode="transparent"></embed></object></div>'.replace(/\{width\}/g, width).replace(/\{height\}/g, height).replace(/\{href\}/g, $t.attr('href'));
            panda_content.html(content);
            panda_box.show().animate({bottom:0},400);
        });
    };

    /*
     * Replaces some characters to HTML entities
     */
    function encode_html(str) {
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    window.OP_AB = {
        autop: op_wpautop,
        br2nl: br2nl,
        column_width: get_column_width,
        edit_element: edit_element,
        reset_form: reset_form,
        insert_content: insert_content,
        get_selector_value: get_selector_value,
        nl2br: nl2br,
        open_dialog: open_asset_dialog,
        resize_content_areas: resize_content_areas,
        set_font_settings: set_font_settings,
        set_selector_value: set_selector_value,
        set_color_value: set_color_value,
        set_wysiwyg_content: set_wysiwyg_content,
        set_uploader_value: set_uploader_value,
        show_slide: show_slide,
        get_active_slide: get_active_slide,
        encode_html: encode_html,
        trigger_insert: function(){
            insert_tag(current_asset[0],current_asset[1]);
        },
        translate: translate,
        unautop: op_unautop,
        wysiwyg_content: get_wysiwyg_content
    };

    $.expr[':'].icontains = function(obj,index,meta,stack){
        return (obj.textContent || obj.innerText || $(obj).text() || '').toLowerCase.indexOf(meta[3].toLowerCase()) >= 0;
    };

    $(document).on('update_button_preview', '.op-settings-core-op_popup', function(e) {
        op_custom_popup.update(e);
        return false;
    });

    $(document).on('update_button_preview', '.op-settings-core-button', function(e) {
        op_custom_button.update(e);
        return false;
    });
    $(document).on('update_button_preview', '.op-settings-core-membership_order_button', function(e) {
        op_custom_membership_button.update(e);
        return false;
    });
    $(document).on('update_button_preview', '.op-settings-core-optin_box', function(e) {
        op_custom_optin_button.update(e);
        return false;
    });
    $(document).on('update_button_preview', '.op-settings-core-optin_modal', function(e) {
        op_custom_optin_button.update(e);
        return false;
    });
}(opjq));