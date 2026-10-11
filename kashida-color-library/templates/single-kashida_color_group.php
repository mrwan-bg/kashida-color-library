<?php
if(!defined('ABSPATH')) exit;
get_header();
while(have_posts()): the_post();
    $kashida_colors_showcase_code=kashida_colors_showcase_group_code(get_the_ID());
    $kashida_colors_showcase_content=get_the_content();
    ?>
    <main id="primary" class="site-main kcs-single-group-page">
      <article id="post-<?php the_ID(); ?>" <?php post_class('kcs-single-group-article'); ?>>
        <header class="kcs-single-group-header">
          <?php if(has_post_thumbnail()): ?><div class="kcs-single-group-cover"><?php the_post_thumbnail('large',['loading'=>'eager']); ?></div><?php endif; ?>
          <div class="kcs-single-group-heading">
            <p class="kcs-single-group-code"><?php echo esc_html($kashida_colors_showcase_code); ?></p>
            <h1><?php the_title(); ?></h1>
            <?php if(has_excerpt()): ?><p><?php echo esc_html(get_the_excerpt()); ?></p><?php endif; ?>
          </div>
        </header>
        <?php if(trim($kashida_colors_showcase_content)!==''): ?><div class="kcs-single-group-content"><?php the_content(); ?></div><?php endif; ?>
        <div class="kcs-single-group-colors">
          <?php echo wp_kses_post(kashida_colors_showcase_shortcode(['group'=>$kashida_colors_showcase_code,'title'=>'false'])); ?>
        </div>
      </article>
    </main>
    <?php
endwhile;
get_footer();
