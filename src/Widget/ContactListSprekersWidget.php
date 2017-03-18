<?php
namespace WPCivi\Jourcoop\Widget;

use WPCivi\Jourcoop\Entity\Contact;
use WPCivi\Shared\Widget\BaseCiviWidget;

/**
 * Class Widget\ContactListSprekersWidget
 * Get or display a list of contacts with 'Beschikbaar als spreker?' => 'Ja' as a content block.
 * @package WPCivi\Jourcoop
 */
class ContactListSprekersWidget extends BaseCiviWidget
{

    /**
     * ContactListWidget constructor.
     */
    public function __construct()
    {
        parent::__construct(__('Speakers List', 'wpcivi-jourcoop'));
    }

    /**
     * Echo widget content (HTML in a PHP class, as WordPress recommends, never mind that it's 2016)
     * @param array $params Parameters
     * @return void
     */
    public function view($params = [])
    {
        // Get all members
        $contacts = Contact::getSprekers();
        ?>

        <div class="members_list">
            <?php
            /** @var Contact[] $contacts */
            if (!empty($contacts) && count($contacts) > 0):
                foreach ($contacts as $c):
                    $functie = $c->getCustom('Functie');
                    ?>
                    <div class="member member_profile" itemscope itemprop="Person">
                        <a href="#<?= $c->getSlug(); ?>" class="member_avatar open-popup">
                          <?php echo get_avatar($c->email); ?>
                        </a>
                        <div class="member_content">
                            <h4><a href="#<?= $c->getSlug(); ?>" class="open-popup"
                                   itemprop="name"><?= $c->display_name; ?></a></h4>
                            <h3 itemprop="jobTitle"><?= $c->job_title; ?></h3>
                            <span style="display:none;" itemprop="functie"><?=$functie; ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php
                foreach ($contacts as $c):
                    require(__DIR__ . '/../../assets/tpl/ContactBlock.php');
                endforeach;
                ?>

            <?php else: ?>

                <h3><?php _e('Er zijn geen contacten gevonden voor deze zoekopdracht', 'wpcivi-jourcoop'); ?></h3>

            <?php endif; ?>
        </div>

        <?php
    }
}