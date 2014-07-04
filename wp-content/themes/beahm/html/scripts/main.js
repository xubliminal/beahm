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
        
        $('body').click(function(){
            $('.person-cv').removeClass('show');
        });
    });
})(window.jQuery);