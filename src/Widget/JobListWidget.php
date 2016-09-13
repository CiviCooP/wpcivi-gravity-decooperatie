<?php
namespace WPCivi\Jourcoop\Widget;

use WPCivi\Jourcoop\Entity\Cases;
use WPCivi\Shared\Widget\BaseCiviWidget;

/**
 * Class Widget\ContactListWidget
 * Get or display a list of jobs (opdrachten = CiviCases) as a content block.
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
        $cases = Cases::getJobs();
        ?>

        <div class="wpcivi-jourcoop-joblist cases">
            <?php if (!empty($cases) && count($cases) > 0):
                foreach ($cases as $c):
                    ?>
                    <div class="case">
                        <h4><a href="#<?= $c->getSlug(); ?>" class="open-case">
                                <?= $c->subject; ?>
                            </a></h4>
                        <h5>
                            Gestart: <?= $c->start_date; ?> |
                            Status: <?= $c->getCaseStatusName(); ?> |
                            Categorie: <?= $c->getCaseServiceName(); ?>
                        </h5>
                    </div>
                <?php endforeach; ?>

            <?php else: ?>

                <h3><?php _e('No jobs found.', 'wpcivi-jourcoop'); ?></h3>

            <?php endif; ?>
        </div>

        <?php
    }
}