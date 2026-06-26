<?php
/**
 * Author:     CriticalGears (http://www.CriticalGears.io)
 * Website:    http://www.CriticalGears.io
 * Support:    http://CriticalGears.io/support-tickets/
 * Version:    2.3.3
 *
 * Copyright:   (c)    CriticalGears.io
 *
 *
 */
?>

<nav class="navbar " role="navigation">
    <div class="container">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>

        </div>
        <div id="navbar" class="navbar-collapse collapse">
            <ul class="nav navbar-nav">
                <?php  foreach($menu_list as $menu){?>
                    <li class="<?php echo((isset($admin_section) && $admin_section==$menu['menu_section']) || (isset($_GET['p']) && $_GET['p'] == $menu['menu_section'])?"active":"")?>">
                        <a href="<?php echo($site_url) ?>/<?php echo($menu['menu_url']) ?>"><?php echo($menu['menu_title']) ?></a>
                    </li>
                <?php }?>
            </ul>
        </div><!--/.nav-collapse -->
    </div>
</nav>
