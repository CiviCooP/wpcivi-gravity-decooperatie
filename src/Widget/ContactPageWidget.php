<?php
namespace WPCivi\Jourcoop\Widget;

use WPCivi\Jourcoop\Entity\Contact;
use WPCivi\Shared\Civi\WPCiviException;
use WPCivi\Shared\Entity\Website;
use WPCivi\Shared\Widget\BaseCiviWidget;

/**
 * Class Widget\ContactPageWidget
 * Display a single member as a separate page / block
 * @package WPCivi\Jourcoop
 */
class ContactPageWidget extends BaseCiviWidget
{

    /**
     * ContactListWidget constructor.
     */
    public function __construct()
    {
        parent::__construct(__('Member Profile', 'wpcivi-jourcoop'));
    }

    /**
     * Echo widget content
     * We currently get the user id via a $_GET parameter, TODO change into a proper rewrite rule.
     * @param array $params Parameters
     * @return void
     */
    public function view($params = [])
    {
        $contact_id = (int)$_GET['id'];

        try {
            // Try to load contact
            $c = new Contact;
            $c->load($contact_id);

            $slug = $c->getSlug();
            $gravatar = get_gravatar($c->email);
            ?>

            <div class="member member_detail" id="<?= $slug; ?>">
                <div class="member_avatar">
                    <img src="<?= $gravatar; ?>" alt="<?= $c->display_name; ?>"/>
                </div>

                <h4><?= $c->display_name; ?></h4>
                <h3><?= $c->job_title; ?></h3>
                <p>
                    <?php if (!empty($c->phone)): ?><em>T:</em> <?= $c->phone; ?><br/><?php endif; ?>
                    <?php if (!empty($c->email)): ?><em>E:</em> <a
                        href="mailto:<?= $c->email; ?>"><?= $c['email']; ?></a><br/><?php endif; ?>
                    <br/>

                    <?php
                    $expertise = $c->getCustom('Expertise');
                    $werkervaring = $c->getCustom('Werkervaring');
                    if (!empty($expertise)): ?>
                        <em>Expertise: </em> <?= implode(', ', $expertise); ?><br/>
                    <?php endif; ?>
                    <?php if (!empty($werkervaring)): ?>
                        <em>Omschrijving/werkervaring:</em> <?= nl2br($werkervaring); ?><br/>
                    <?php endif; ?>
                    <br/>

                    <?php
                    $websites = Website::getWebsitesForContact($c->id);
                    foreach ($websites as $type => $url): ?>
                        <em><?= $type; ?></em>:
                        <a href="<?= $url; ?>" rel="nofollow" target="_blank"><?= $url; ?></a><br/>
                    <?php endforeach; ?>
                </p>
            </div>

        <?php } catch (WPCiviException $e) {
            status_header(404);
            ?>

            <h3><?php _e('Contact not found! A link to this page should include a valid user id (?id=X).', 'wpcivi-jourcoop'); ?></h3>

            <?php
        }
    }

}