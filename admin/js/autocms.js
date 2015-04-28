$(function() {
    $('#side-menu').metisMenu();

    //Loads the correct sidebar on window load,
    //collapses the sidebar on window resize.
    // Sets the min-height of #page-wrapper to window size
    $(window).bind("load resize", function() {
        topOffset = 50;
        width = (this.window.innerWidth > 0) ? this.window.innerWidth : this.screen.width;
        if (width < 768) {
            $('div.navbar-collapse').addClass('collapse');
            topOffset = 100; // 2-row-menu
        } else {
            $('div.navbar-collapse').removeClass('collapse');
        }

        height = ((this.window.innerHeight > 0) ? this.window.innerHeight : this.screen.height) - 1;
        height = height - topOffset;
        if (height < 1) height = 1;
        if (height > topOffset) {
            $("#page-wrapper").css("min-height", (height) + "px");
        }
    });

    var url = window.location;
    var element = $('ul.nav a').filter(function() {
        return this.href == url || url.href.indexOf(this.href) == 0;
    }).addClass('active').parent().parent().addClass('in').parent();
    if (element.is('li')) {
        element.addClass('active');
    }


    tinymce.init({
        selector: "textarea",
        menubar : false,
        plugins: "textcolor",
        height: 150,
        statusbar: false,
        forced_root_block : "",
        mode : "textareas",
        toolbar: ["styleselect | fontsizeselect", "undo redo | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | table | forecolor backcolor"]
    });

    $('.desc-edit').editable();

    function validateCreateAuth() {
        // TODO: validate the the password matches... other rules if needed

        return true;
    }

});