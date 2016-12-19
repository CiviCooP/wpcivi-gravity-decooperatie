<?php
namespace WPCivi\Jourcoop\Widget;

use WPCivi\Jourcoop\Entity\Cases;
use WPCivi\Shared\Widget\BaseCiviWidget;

/**
 * Class Widget\ContactListWidget
 * Get or display a list of jobs (= opdrachten = cases) with status 'Public' as a content block.
 * @package WPCivi\Jourcoop
 */
class JobListWidget extends BaseCiviWidget
{

    /**
     * ContactListWidget constructor.
     */
    public function __construct()
    {
        parent::__construct(__('Job List', 'wpcivi-jourcoop'));
    }

    /**
     * Echo widget content.
     * @param array $params Parameters
     * @return void
     */
    public function view($params = [])
    {
        /** @var Cases[] $cases */
        $cases = Cases::getJobs([
                'status_id' => 'Public',
        ]);
        ?>

        <div class="wpcivi-jourcoop-joblist cases">
            <?php if (!empty($cases) && count($cases) > 0):
                foreach ($cases as $c):
                    ?>
                    <div class="case">
                        <h4>
                            <a href="/opdrachten/detail/?id=<?=$c->getId();?>" class="open-case"><?= $c->subject; ?></a>
                            (#<?=$c->id;?>)
                        </h4>
                        <h5>
                            <?php _e('Toegevoegd', 'wpcivi-jourcoop'); ?>: <?= $c->start_date; ?> |
                            <?php _e('Categorie', 'wpcivi-jourcoop'); ?>: <?= $c->getCaseServiceName(); ?>
                        </h5>
                        <p><?=nl2br($c->getCustom('Description'));?></p>
                    </div>
                <?php endforeach; ?>

            <?php else: ?>

                <h3><?php _e('Geen opdrachten gevonden', 'wpcivi-jourcoop'); ?></h3>

            <?php endif; ?>
        </div>

        <?php
    }
}