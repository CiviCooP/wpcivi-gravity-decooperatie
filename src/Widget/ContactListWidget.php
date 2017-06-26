<?php
namespace WPCivi\Jourcoop\Widget;

use WPCivi\Jourcoop\Entity\Contact;
use WPCivi\Shared\Entity\OptionValue;
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
        $contacts->prefetchCustomData(['Functie', 'Werkervaring']);
        $websites = Website::getWebsitesForContacts($contacts);
        ?>

        <div class="members_search">
            <form action="#" method="post" name="form_members_search" id="form_members_search">
                <span>Zoek op:</span>
                <input type="text" name="search_name" id="search_name" placeholder="Naam"/>
                <input type="text" name="search_jobtitle" id="search_jobtitle" placeholder="Specialisme"/>
                <select name="search_functie" id="search_functie">
                    <option value="">- Functie -</option>
                    <?php $optionValues = OptionValue::getOptionValues('functie_nieuw__20161225232831');
                    foreach($optionValues as $ov): ?>
                    <option value="<?=$ov->value;?>"><?=$ov->label;?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="submit" id="search_submit">Zoek!</button>
                <button type="reset" name="reset" id="search_reset" class="hide">Toon alles</button>
            </form>
        </div>

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

                <h3><?php _e('Er zijn geen leden gevonden voor deze zoekopdracht', 'wpcivi-jourcoop'); ?></h3>
                <a href="<?= the_permalink(); ?>"><?php _e('Toon alle contacten', 'wpcivi-jourcoop'); ?></a>

            <?php endif; ?>
        </div>

        <?php
    }
}