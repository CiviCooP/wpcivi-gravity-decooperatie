<?php /* Blok voor één contact, geinclude vanuit widgets */ ?>
<div class="member member_popup white-popup mfp-hide" id="<?= $c->getSlug(); ?>"
     data-contactid="<?= $c->getId(); ?>">
  <div class="member_avatar">
    <?php echo get_avatar($c->email); ?>
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
      /* $functie = $c->getCustom('Functie');
      if(!empty($functie)): ?>
          <em><?php _e('Functie', 'wpcivi-jourcoop'); ?>: </em>
          <?=$functie;?>
      <?php endif; */
      if (!empty($expertise)): ?>
        <em><?php _e('Expertise', 'wpcivi-jourcoop'); ?>: </em>
        <?= implode(', ', $expertise); ?><br/>
      <?php endif; ?>
      <?php if (!empty($werkervaring)): ?>
        <em><?php _e('Omschrijving/werkervaring', 'wpcivi-jourcoop'); ?>:</em>
        <?= nl2br($werkervaring); ?><br/>
      <?php endif; ?>
      <?php if(!empty($expertise) || !empty($werkervaring)): ?>
        <br/>
      <?php endif; ?>

      <?php
      $websites = \WPCivi\Shared\Entity\Website::getWebsitesForContact($c->id);
      foreach ($websites as $type => $url):
        $type = ($type == 'Work' ? 'Website' : $type);
        if (empty($url)):
          continue;
        endif;
        ?>
        <em><?= $type; ?></em>:
        <a href="<?= $url; ?>" rel="nofollow" target="_blank"><?= $url; ?></a><br/>
      <?php endforeach; ?>
    </p>
  </div>
</div>