jQuery(function ($) {

    /**
     * Class WPCivi_Jourcoop_ContactListWidget.
     * Simple JS search function for ContactListWidget (see also Widget\ContactListWidget.php).
     * @constructor
     * @package WPCivi\Jourcoop
     */
    var WPCivi_Jourcoop_ContactListWidget = function () {

        // Bind events
        this.init = function () {

            var that = this;

            $('#form_members_search').on('submit', function (ev) {
                that.search(); // Perform search
                ev.preventDefault();
            });

            $('#form_members_search').on('click', ' #search_reset', function (ev) {
                that.reset(); // Reset list items (form fields are reset by browser)
            });
        };

        // Form submit, perform search
        this.search = function () {
            var sName = $('#form_members_search #search_name').val().toLowerCase();
            var sJobTitle = $('#form_members_search #search_jobtitle').val().toLowerCase();

            var $listcontainer = $('.members_list');
            var $profiles = $('.members_list .member_profile');

            $listcontainer.hide();

            // Walk all profiles and search for matches
            $.each($profiles, function (i, profile) {

                var $profile = $(profile);
                $profile.hide();

                if (sName.length > 0) {
                    var name = $profile.find('[itemprop=name]').html();
                    console.log(name, sName, $profile);
                    if(name.toLowerCase().indexOf(sName) == -1) {
                        return true; // No match, next
                    }
                }

                if (sJobTitle.length > 0) {
                    var jobTitle = $profile.find('[itemprop=jobTitle]').html();
                    console.log(jobTitle, sJobTitle, $profile);
                    if(jobTitle.toLowerCase().indexOf(sJobTitle) == -1) {
                        return true; // No match, next
                    }
                }

                // If we reached this point, it's a match
                console.log('Match', $profile);
                $profile.show();
            });

            $listcontainer.slideDown();
        };

        // Form reset, show all members
        this.reset = function () {
            $('.members_list').hide();
            $('.members_list .member_profile').removeClass('search_hit').show();
            $('.members_list').slideDown();
        };

    };

    /**
     * Initialise on DOM ready
     */
    window.wpcivi_jourcoop_clwidget = new WPCivi_Jourcoop_ContactListWidget();
    window.wpcivi_jourcoop_clwidget.init();
});