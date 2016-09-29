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
        /** @var Contact[] $contacts */
        $contacts = Contact::getMembers();
        
        ?>
            
            <?php if (!empty($contacts) && count($contacts) > 0):
                foreach ($contacts as $c):
                    $slug = $c->getSlug();
                    $gravatar = get_gravatar($c->email);
                    ?>
                    <div class="member member_profile">
                        <a href="#<?= $slug; ?>" class="member_avatar open-popup">
                            <img src="<?= $gravatar; ?>" alt="<?= $c->display_name; ?>"/>
                        </a>
                        <div class="member_content">
                            <h4><a href="#<?= $slug; ?>" class="open-popup"><?= $c->display_name; ?></a></h4>
                            <h3><?= $c->job_title; ?></h3>
                        </div>
                    </div>
                    <div class="member member_popup white-popup mfp-hide" id="<?= $slug; ?>">
                        <div class="member_avatar">
                            <img src="<?= $gravatar; ?>" alt="<?= $c->display_name; ?>"/>
                        </div>

                        <h4><?= $c->display_name; ?></h4>
                        <h3><?= $c->job_title; ?></h3>
                        <p>
                            <?php if(!empty($c->phone)): ?><em>T:</em> <?= $c->phone; ?><br/><?php endif; ?>
                            <?php if(!empty($c->email)): ?><em>E:</em> <a href="mailto:<?= $c->email; ?>"><?= $c['email']; ?></a><br/><?php endif; ?>
                            <br/>

                            <?php
                            $expertise = $c->getCustom('Expertise');
                            $werkervaring = $c->getCustom('Werkervaring');
                            if (!empty($expertise)): ?>
                                <em>Expertise: </em> <?= implode(', ', $expertise); ?><br/>
                            <?php endif; ?>
                            <?php if (!empty($werkervaring)): ?>
                                <em>Omschrijving/werkervaring:</em> <?= nl2br($werkervaring); ?><br />
                            <?php endif; ?>
                            <br />

                            <?php
                            $websites = Website::getWebsitesForContact($c->id);
                            foreach ($websites as $type => $url): ?>
                                <em><?= $type; ?></em>:
                                <a href="<?= $url; ?>" rel="nofollow" target="_blank"><?= $url; ?></a><br/>
                            <?php endforeach; ?>
                        </p>
                    </div>
                <?php endforeach; ?>

            <?php else: ?>

                <h3><?php _e('No contacts found.', 'wpcivi-jourcoop'); ?></h3>

            <?php endif; ?>

        <?php
    }

}