<div id="page">

<!--header-top-->
<div id="header-top">
    <div id="header-top-inside" class="clearfix">
    	
        <!--header-top-inside-left-->
        <div id="header-top-inside-left"><?php print render($page['header']); ?></div>
        <!--EOF:header-top-inside-left-->
        
        <!--header-top-inside-left-feed-->
        <div id="header-top-inside-left-feed">
            <div id="topSocial">
            <ul>									
                <li><a class="twitter" href="http://twitter.com/morethanthemes" title="Follow Us on Twitter!"></a></li>
                <li><a class="facebook" href="http://www.facebook.com/pages/More-than-just-themes/194842423863081" title="Join Us on Facebook!"></a></li>
                <li><a class="rss" title="RSS" href="#" title="Subcribe to Our RSS Feed"></a></li>
            </ul>
            </div>
        </div>
        <!--EOF:header-top-inside-left-feed-->
        
        <!--header-top-inside-left-right-->
        <div id="header-top-inside-right"><?php print render($page['search_area']);?></div> 
        <!--EOF:header-top-inside-left-right-->
         
    </div>
</div>
<!--EOF:header-top-->
    
<div id="wrapper">
	
    <!--header-->
    <div id="header" class="clearfix">
    	
        <!--logo-floater-->
        <div id="logo-floater"> 
			<?php if ($logo): ?>
            <a href="<?php print check_url($front_page); ?>" title="<?php print t('Home'); ?>">
            <img src="<?php print $logo; ?>" alt="<?php print t('Home'); ?>" />
            </a>
            <?php endif; ?>
            
            <?php if ($site_name || $site_slogan): ?>
            <div class="clearfix">
				<?php if ($site_name): ?>
                <span id="site-name"><a href="<?php print check_url($front_page); ?>" title="<?php print t('Home'); ?>"><?php print $site_name; ?></a></span>
                <?php endif; ?>
                
                <?php if ($site_slogan): ?>
                <span id="slogan"><?php print $site_slogan; ?></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div> <!--EOF:logo-floater-->
        
        <!--navigation-->
        <div id="navigation">
			<?php $menu_name = variable_get('menu_main_links_source', 'main-menu');
            $main_menu_tree = menu_tree($menu_name); 
            print drupal_render($main_menu_tree); ?>
        </div><!--EOF:navigation-->
       
    </div><!--EOF:header-->

    <div id="banner">
    <?php print render($page['banner']); ?>
    
        <div class="main_view">
            <div class="window">
                <div class="image_reel">
                    <a href="<?php print url('node/3'); ?>"><img src="<?php print base_path() . drupal_get_path('theme', 'bluemasters') . '/images/slide-image-3.jpg'; ?>"></a>
                    <a href="<?php print url('node/2'); ?>"><img src="<?php print base_path() . drupal_get_path('theme', 'bluemasters') . '/images/slide-image-2.jpg'; ?>"></a>
                    <a href="<?php print url('node/1'); ?>"><img src="<?php print base_path() . drupal_get_path('theme', 'bluemasters') . '/images/slide-image-1.jpg'; ?>"></a>
                </div>
                <div class="descriptions">
                    <div class="desc" style="display: none;">About Bluemasters</div>
                    <div class="desc" style="display: none;">Our Portfolio</div>
                    <div class="desc" style="display: none;">Creation of Beaches</div>
                </div>
            </div>
        
            <div class="paging">
                <a rel="1" href="#">1</a>
                <a rel="2" href="#">2</a>
                <a rel="3" href="#">3</a>
            </div>
        </div>
    </div><!--EOF:banner-->

    <div id="home-blocks-area" class="clearfix">
    
		<?php if ($messages): ?>
        <div id="console" class="clearfix">
        <?php print $messages; ?>
        </div>
        <?php endif; ?>
    
        <div class="home-block-area first">
            <?php print render($page['home_area_1']);?> 		
        </div>
        <div class="home-block-area">
            <?php print render($page['home_area_2']);?> 
        </div>
        <div class="home-block-area last">
            <?php print render($page['home_area_3']);?> 
            <?php print render($page['home_area_3_b']);?> 
        </div>
    </div>

</div><!--EOF:wrapper-->

<!--footer-->
<div id="footer">
    <div id="footer-inside" class="clearfix">
    
    	<div id="footer-left">
    		<div id="footer-left-1">
    			<?php print render($page['footer_left_1']);?>
    		</div>
    		<div id="footer-left-2">
    			<?php print render($page['footer_left_2']);?>
    		</div>
        </div>
        
        <div id="footer-center">
        	<?php print render($page['footer_center']);?>
        </div>
        
        <div id="footer-right">
        	<?php print render($page['footer_right']);?>
        </div>
        
    </div>
</div>
<!--EOF:footer-->

<!--footer-bottom-->
<div id="footer-bottom">
    <div id="footer-bottom-inside" class="clearfix">
    	<div id="footer-bottom-inside-left">
    		<?php print render($page['footer']);?>
    	</div>
    	<div id="footer-bottom-inside-right">
		<?php print theme('links__system_secondary_menu', array('links' => $secondary_menu, 'attributes' => array('id' => 'secondary-menu', 'class' => array('links', 'inline', 'clearfix')))); ?>
    	</div>
    	<div class="credits-container" style="clear:both; padding-top:12px;">Ported to Drupal for the Open Source Community by <a href="http://www.drupalizing.com">Drupalizing</a>, a Project of <a href="http://www.morethanthemes.com">More than Themes</a></div>     
    </div>
    <div class="credits-container clearfix">
        <a title="Brought To You By: www.SmashingMagazine.com" class="smashing" href="http://www.smashingmagazine.com" target="_blank">Brought to you By: www.SmashingMagazine.com</a>
        <a title="Drupalizing" class="drupalizing" href="http://www.drupalizing.com" target="_blank">Drupalizing</a> 
    </div>  
</div>
<!--EOF:footer-bottom-->

</div><!--EOF:page-->