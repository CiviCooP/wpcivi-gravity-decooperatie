<?php
namespace WPCivi\Jourcoop\Widget;

use WPCivi\Jourcoop\Entity\Contact;
use WPCivi\Shared\Entity\Website;
use WPCivi\Shared\Widget\BaseCiviWidget;

/**
 * Class Widget\ContactListWidget
 * Get or display a list of contacts as a content block.
 * @package WPCivi\Jourcoop
 */
class ContactListWidget extends BaseCiviWidget
{

    /**
     * ContactListWidget constructor.
     */
    public function __construct()
    {
        parent::__construct(__('Members List', 'wpcivi-jourcoop'));
    }

    /**
     * Echo widget content (HTML in a PHP class, as WordPress recommends, never mind that it's 2016)
     * @param array $params Parameters
     * @return void
     */
    public function view($params = [])
    {
        // Add widget JS
        wp_enqueue_script('wpcivi_jourcoop_clwidget',
            plugins_url('wpcivi-jourcoop/assets/js/ContactListWidget.js'), ['jquery'], '1.1', true);

        // Get all members
        $contacts = Contact::getMembers();
        ?>

        <div class="members_search">
            <form action="#" method="post" name="form_members_search" id="form_members_search">
                <span>Zoek op:</span>
                <input type="text" name="search_name" id="search_name" placeholder="Naam"/>
                <input type="text" name="search_jobtitle" id="search_jobtitle" placeholder="Specialisme"/>
                <button type="submit" name="submit" id="search_submit">Zoek!</button>
                <button type="reset" name="reset" id="search_reset" class="hide">Toon alles</button>
            </form>
        </div>

        <div class="members_list">
            <?php
            /** @var Contact[] $contacts */
            if (!empty($contacts) && count($contacts) > 0):
                foreach ($contacts as $c):
                    ?>
                    <div class="member member_profile" itemscope itemprop="Person">
                        <a href="#<?= $c->getSlug(); ?>" class="member_avatar open-popup">
                            <img src="<?= get_gravatar($c->email); ?>" alt="<?= $c->display_name; ?>"/>
                        </a>
                        <div class="member_content">
                            <h4><a href="#<?= $c->getSlug(); ?>" class="open-popup"
                                   itemprop="name"><?= $c->display_name; ?></a></h4>
                            <h3 itemprop="jobTitle"><?= $c->job_title; ?></h3>
                        </div>
                    </div>
                <?php endforeach;
                foreach ($contacts as $c): ?>
                    <div class="member member_popup white-popup mfp-hide" id="<?= $c->getSlug(); ?>"
                         data-contactid="<?= $c->getId(); ?>">
                        <div class="member_avatar">
                            <img src="<?= get_gravatar($c->email); ?>" alt="<?= $c->display_name; ?>"/>
                        </div>

                        <div class="member_content">
                            <h4><?= $c->display_name; ?></h4>
                            <h3><?= $c->job_title; ?></h3>
                            <p>
                                <?php if (!empty($c->phone)): ?>
                                    <em><?php _e('Telefoon', 'wpcivi-jourcoop'); ?>:</em>
                                    <?= $c->phone; ?><br/>
                                <?php endif; ?>
                                <?php if (!empty($c->email)): ?>
                                    <em><?php _e('E-mail', 'wpcivi-jourcoop'); ?>:</em>
                                    <a href="mailto:<?= $c->email; ?>"><?= $c['email']; ?></a><br/>
                                <?php endif; ?>
                                <br/>

                                <?php
                                $expertise = $c->getCustom('Expertise');
                                $werkervaring = $c->getCustom('Werkervaring');
                                if (!empty($expertise)): ?>
                                    <em><?php _e('Expertise', 'wpcivi-jourcoop'); ?>: </em>
                                    <?= implode(', ', $expertise); ?><br/>
                                <?php endif; ?>
                                <?php if (!empty($werkervaring)): ?>
                                    <em><?php _e('Omschrijving/werkervaring', 'wpcivi-jourcoop'); ?>:</em>
                                    <?= nl2br($werkervaring); ?><br/>
                                <?php endif; ?>
                                <br/>

                                <?php
                                $websites = Website::getWebsitesForContact($c->id);
                                foreach ($websites as $type => $url):
                                    $type = ($type == 'Work' ? 'Website' : $type);
                                    ?>
                                    <em><?= $type; ?></em>:
                                    <a href="<?= $url; ?>" rel="nofollow" target="_blank"><?= $url; ?></a><br/>
                                <?php endforeach; ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>

            <?php else: ?>

                <h3><?php _e('Er zijn geen leden gevonden voor deze zoekopdracht', 'wpcivi-jourcoop'); ?></h3>
                <a href="<?= the_permalink(); ?>"><?php _e('Toon alle contacten', 'wpcivi-jourcoop'); ?></a>

            <?php endif; ?>
        </div>

        <?php
    }
}