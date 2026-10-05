<?php
/**
 * Yleinen sivupohja. Sivujen sisältö (hero, osiot, kortit, UKK, lomakkeet) tulee
 * suoraan sivun sisällöstä valmiiksi muotoiltuna HTML:nä — tämä pohja lisää
 * yhteisen ylä- ja alatunnisteen ympärille.
 */

get_header();
?>

<main id="main-content" tabindex="-1">
  <?php
  while ( have_posts() ) :
    the_post();
    the_content();
  endwhile;
  ?>
</main>

<?php
get_footer();
