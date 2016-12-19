<?php
namespace WPCivi\Jourcoop\Widget;

use WPCivi\Jourcoop\Entity\Cases;
use WPCivi\Shared\Civi\WPCiviException;
use WPCivi\Shared\Widget\BaseCiviWidget;

/**
 * Class Widget\JobDetailWidget
 * Display a single job as a separate page / block
 * @package WPCivi\Jourcoop
 */
class JobDetailWidget extends BaseCiviWidget
{

    /**
     * JobDetailWidget constructor.
     */
    public function __construct()
    {
        parent::__construct(__('Job Detail Page', 'wpcivi-jourcoop'));
    }

    /**
     * Echo widget content
     * We currently get the job id via a $_GET parameter, TODO: change into a proper rewrite rule.
     * @param array $params Parameters
     * @return void
     */
    public function view($params = [])
    {
        try {
            if (!isset($_GET['id'])) {
                throw new WPCiviException(__('Opdracht niet gevonden! Een link naar deze pagina moet een geldige opdracht-ID bevatten (?id=X)', 'wpcivi-jourcoop'));
            }
            $case_id = (int)$_GET['id'];

            // Try to load job
            $c = new Cases;
            $c->load($case_id);

            // Check if case is publicly accessible
            if (!in_array($c->getCaseStatusName(), ['Submitted', 'Public'])) {
                throw new WPCiviException(__('Deze opdracht is niet openbaar toegankelijk', 'wpcivi-jourcoop'));
            }
            ?>

            <div class="case case_detail" id="<?= $c->getSlug(); ?>">

                <h4><?= $c->subject; ?></h4>

                <h3><?php _e('Opdrachtnummer', 'wpcivi-jourcoop'); ?>: #<?= $c->id; ?></h3>

                <p>
                    <strong><?php _e('Toegevoegd', 'wpcivi-jourcoop'); ?>:</strong> <?= $c->start_date; ?>
                </p>

                <p>
                    <strong><?php _e('Categorie', 'wpcivi-jourcoop'); ?>:</strong> <?= $c->getCaseServiceName(); ?>
                </p>

                <?php $tariff = $c->getCustom('Price');
                if (!empty($tariff)): ?>
                    <p>
                        <strong><?php _e('Tarief', 'wpcivi-jourcoop'); ?>:</strong> <?= $tariff; ?>
                    </p>
                <?php endif; ?>

                <p>
                    <?= nl2br($c->getCustom('Description')); ?>
                </p>

            </div>

        <?php } catch (WPCiviException $e) {
            status_header(404);
            ?>

            <h3>Error: <?= $e->getMessage(); ?></h3>

            <?php
        }
    }

}