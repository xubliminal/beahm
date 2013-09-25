(function($) {
    $(function(){
        $('.scroll').each(function(){
            $(this).parent().localScroll();
        });
        
        $('#nav-wrap .sub-menu').each(function(){
            $(this).addClass('dropdown-menu');
            $a = $(this).prev('a');
            $a.addClass('dropdown-toggle')
            $a.append('<span class="caret"></span>');
            $a.dropdown()
            $(this).parents('li').addClass('dropdown');
        });
    });
})(window.jQuery);