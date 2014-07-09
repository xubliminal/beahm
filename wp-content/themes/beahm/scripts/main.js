(function($) {
    $(function(){
        $('.scroll').each(function(){
            $(this).parent().localScroll();
        });
        
        $('#menu-item-34').localScroll();
        
        $('#nav-wrap .sub-menu').each(function(){
            $(this).addClass('dropdown-menu');
            $a = $(this).prev('a');
            $a.addClass('dropdown-toggle')
            $a.append('<span class="caret"></span>');
            $a.dropdown()
            $(this).parents('li').addClass('dropdown');
        });
        
        $('.person-photo img').click(function(e){
            $ttip = $(this).parents('.person').find('.person-cv');
            $ttip.toggleClass('show');
            return false;
        });
        
        var menuOpen = false;
        $('#menu-item-501 a.dropdown-toggle').click(function(e){
            $li = $(this).parents('li');
            if(menuOpen) {
                $li.removeClass('open');
            }
            menuOpen = !menuOpen;
            return false;
        });

        $('body').click(function(){
            $('.person-cv').removeClass('show');
            $('#menu-item-501').removeClass('open');
        });
    });
})(window.jQuery);