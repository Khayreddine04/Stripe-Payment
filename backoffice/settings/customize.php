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

include_once "../includes/bootstrap.php";
include_once "settings.php";

if(!$user->logon){
    header("Location: ../index.php");
    exit();
}
$settings->set("admin_section",$pt_section);


$settings_section = "customize";
$a->addScripts("../../assets/js/jquery.validate-1-19-3.min.js");
$a->addScripts("../../assets/js/additional-methods.min.js");
$a->addScripts("../../assets/bootstrap/js/bootstrap-switch.min.js");
$a->addStyle("../../assets/bootstrap/css/bootstrap-switch.min.css");
$a->addScripts("../../assets/bootstrap/js/bootstrap-colorpicker.min.js");
$a->addStyle("../../assets/bootstrap/css/bootstrap-colorpicker.min.css");
$a->addScripts("scripts.js?t=".time());
$settings_menu = new PT_Admin_Template("settings_menu.php");
$settings_menu->section = $settings_section;
$email_name = $a->esc("email_name");
$email =  $a->esc("email");
$header_background_color =  $a->esc("header_background_color");
$header_text_color =  $a->esc("header_text_color");
$header_text_size =  $a->esc("header_text_size");
$container_background_color =  $a->esc("container_background_color");
$form_headers_background_color =  $a->esc("form_headers_background_color");
$form_header_text_color =  $a->esc("form_header_text_color");
$form_background_color =  $a->esc("form_background_color");
$form_header_text_size =  $a->esc("form_header_text_size");
$form_label_text_color =  $a->esc("form_label_text_color");
$form_label_text_size =  $a->esc("form_label_text_size");
$form_button_background_color =  $a->esc("form_button_background_color");
$form_button_text_color =  $a->esc("form_button_text_color");
$form_button_text_size =  $a->esc("form_button_text_size");
$theme_type =  $a->esc("theme_type");
$selected_theme =  $a->esc("selected_theme");
$action =  $a->esc("action");
$file_to_del =  $a->esc("file_to_del");

if($action=="delete_file"){
    @unlink(HOME_DIR.$settings->$file_to_del);
    $settings->updateOption("$file_to_del","");
}


if($action=='save_settings'){

    $settings->updateOption("header_background_color",$header_background_color);
    $settings->updateOption("header_text_color",$header_text_color);
    $settings->updateOption("header_text_size",$header_text_size);
    $settings->updateOption("container_background_color",$container_background_color);
    $settings->updateOption("form_headers_background_color",$form_headers_background_color);
    $settings->updateOption("form_header_text_color",$form_header_text_color);
    $settings->updateOption("form_background_color",$form_background_color );
    $settings->updateOption("form_header_text_size",$form_header_text_size);
    $settings->updateOption("form_label_text_color",$form_label_text_color);
    $settings->updateOption("form_label_text_size",$form_label_text_size);
    $settings->updateOption("form_button_background_color",$form_button_background_color);
    $settings->updateOption("form_button_text_color",$form_button_text_color);
    $settings->updateOption("form_button_text_size",$form_button_text_size);
    $settings->updateOption("theme_type",$theme_type);
    $settings->updateOption("selected_theme",$selected_theme);

    if(!empty($_FILES['header_background_image']['name'])){
        $result = $a->uploadFile($_FILES['header_background_image'],"header_background_image",2);
        if(!$result['error']){
            $settings->updateOption("header_background_image",$result['imgPath']);
        }else{
            $a->addError($result['error']);
        }
    }

    if(!empty($_FILES['container_background_image']['name'])){
        $result = $a->uploadFile($_FILES['container_background_image'],"container_background_image",2);
        if(!$result['error']){
            $settings->updateOption("container_background_image",$result['imgPath']);
        }else{
            $a->addError($result['error']);
        }
    }

    if(!empty($_FILES['form_background_image']['name'])){
        $result = $a->uploadFile($_FILES['form_background_image'],"form_background_image",2);
        if(!$result['error']){
            $settings->updateOption("form_background_image",$result['imgPath']);
        }else{
            $a->addError($result['error']);
        }
    }

    $a->addSuccess("Settings have been successfully updated");

}

$a->getHeader();

?>
    <div class="container" role="main">
        <div class="row">
            <div class="col-xs-12 col-sm-3 col-md-3 col-lg-2">
                <?php $settings_menu->render(true)?>
            </div>
            <div class="clearfix visible-xs-block"></div>
            <div class="col-xs-12 col-sm-9 col-md-9 col-lg-10">
                <?php echo($a->getMessages()) ?>
                <?php if($can_view){ ?>
                <form class=" validate"  role="form" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="save_settings" >

                    <h2>Theme Options</h2>
                    <hr/>
                    <div class="btn-group col-md-3" data-toggle="buttons">
                        <label class="btn btn-default <?php echo($settings->theme_type=='theme')?"active":"" ?>">
                            <input type="radio" id="theme_type1" name="theme_type" value="theme" <?php echo($settings->theme_type=='theme')?"checked":"" ?>/> Theme
                        </label>
                        <label class="btn btn-default <?php echo($settings->theme_type=='custom')?"active":"" ?>">
                            <input type="radio" id="theme_type2" name="theme_type" value="custom" <?php echo($settings->theme_type=='custom')?"checked":"" ?>/> Custom
                        </label>
                    </div>
                    <div class="clearfix"></div>
                    <div id="custom_settings" style="display: <?php echo($settings->theme_type=='custom'?"block":"none")?>">
                        <h2>Header Settings</h2>
                        <hr>
                        <div class="form-group col-md-3">
                            <label for="header_background_color2">Background color</label>
                            <div class="input-group colorpick">
                                <input type="text" name="header_background_color" value="<?php echo(htmlspecialchars($settings->header_background_color)) ?>" class="form-control" />
                                <span class="input-group-addon"><i></i></span>
                            </div>

                        </div>
                        <div class="form-group col-md-3">
                            <label for="header_text_color">Text color</label>
                            <div class="input-group colorpick">
                                <input type="text" name="header_text_color" value="<?php echo(htmlspecialchars($settings->header_text_color)) ?>" class="form-control" />
                                <span class="input-group-addon"><i></i></span>
                            </div>
                        </div>
                        <div class="form-group col-md-3">
                            <label for="header_text_color">Text size</label>
                            <select name="header_text_size" class="form-control">
                                <?php for($i=10;$i<32;$i++){ ?>
                                <option value="<?php echo($i) ?>" style="font-size: <?php echo($i)?>px" <?php echo(htmlspecialchars($settings->header_text_size)==$i?"selected":"") ?>><?php echo($i) ?>px</option>
                                <?php }?>
                            </select>
                        </div>
                        <div class="clearfix"></div>
                        <div class="form-group col-md-3">
                            <label for="header_background_image">Background image</label>
                            <input type="file" name="header_background_image" value="Select File" id="header_background_image" data-rule-extension="jpg|jpeg|png" data-msg-extension="Allowed only .jpg, .jpeg, .png">

                        </div>
                        <?php if(!empty($settings->header_background_image)){?>
                            <div class="form-group col-md-4">
                                <label for="site_url">Current image</label>
                                <img src="<?php echo($settings->site_url.$settings->header_background_image) ?>?t=<?php echo time()?>" width="100"/>
                                <small><a href="?action=delete_file&file_to_del=header_background_image"><span aria-hidden="true" class="glyphicon glyphicon-remove" style="color: red"></span>Delete</a></small>
                            </div>
                        <?php }?>
                        <div class="clearfix"></div>
                        <h2>Background Settings</h2>
                        <hr>
                        <div class="form-group col-md-3">
                            <label for="header_background_color">Background color</label>
                            <div class="input-group colorpick">
                                <input type="text" name="container_background_color" value="<?php echo(htmlspecialchars($settings->container_background_color)) ?>" class="form-control" />
                                <span class="input-group-addon"><i></i></span>
                            </div>

                        </div>
                        <div class="form-group col-md-3">
                            <label for="container_background_image">Background image</label>
                            <input type="file" name="container_background_image" value="Select File" id="container_background_image" data-rule-extension="jpg|jpeg|png" data-msg-extension="Allowed only .jpg, .jpeg, .png">

                        </div>
                        <?php if(!empty($settings->container_background_image)){?>
                            <div class="form-group col-md-4">
                                <label for="site_url">Current image</label>
                                <img src="<?php echo($settings->site_url.$settings->container_background_image) ?>?t=<?php echo time()?>" width="100"/>
                                <small><a href="?action=delete_file&file_to_del=container_background_image"><span aria-hidden="true" class="glyphicon glyphicon-remove" style="color: red"></span>Delete</a></small>
                            </div>
                        <?php }?>
                        <div class="clearfix"></div>


                        <h2>Form Settings</h2>
                        <hr>
                        <div class="form-group col-md-3">
                            <label for="form_background_color">Background color</label>
                            <div class="input-group colorpick">
                                <input type="text" name="form_background_color" value="<?php echo(htmlspecialchars($settings->form_background_color)) ?>" class="form-control" />
                                <span class="input-group-addon"><i></i></span>
                            </div>

                        </div>
                        <div class="form-group col-md-3">
                            <label for="form_background_image">Background image</label>
                            <input type="file" name="form_background_image" value="Select File" id="form_background_image" data-rule-extension="jpg|jpeg|png" data-msg-extension="Allowed only .jpg, .jpeg, .png">

                        </div>
                        <?php if(!empty($settings->form_background_image)){?>
                            <div class="form-group col-md-4">
                                <label for="site_url">Current image</label>
                                <img src="<?php echo($settings->site_url.$settings->form_background_image) ?>?t=<?php echo time()?>" width="100"/>
                                <small><a href="?action=delete_file&file_to_del=form_background_image"><span aria-hidden="true" class="glyphicon glyphicon-remove" style="color: red"></span>Delete</a></small>
                            </div>
                        <?php }?>
                        <div class="clearfix"></div>
                        <div class="form-group col-md-3">
                            <label for="form_headers_background_color">Titles background color</label>
                            <div class="input-group colorpick">
                                <input type="text" name="form_headers_background_color" value="<?php echo(htmlspecialchars($settings->form_headers_background_color)) ?>" class="form-control" />
                                <span class="input-group-addon"><i></i></span>
                            </div>

                        </div>
                        <div class="form-group col-md-3">
                            <label for="form_header_text_color">Titles text color</label>
                            <div class="input-group colorpick">
                                <input type="text" name="form_header_text_color" value="<?php echo(htmlspecialchars($settings->form_header_text_color)) ?>" class="form-control" />
                                <span class="input-group-addon"><i></i></span>
                            </div>

                        </div>
                        <div class="form-group col-md-3">
                            <label for="form_header_text_size">Titles text size</label>
                            <select name="form_header_text_size" class="form-control">
                                <?php for($i=10;$i<=32;$i++){ ?>
                                    <option value="<?php echo($i) ?>" style="font-size: <?php echo($i)?>px" <?php echo(htmlspecialchars($settings->form_header_text_size)==$i?"selected":"") ?>><?php echo($i) ?>px</option>
                                <?php }?>
                            </select>
                        </div>
                        <div class="clearfix"></div>
                        <div class="form-group col-md-3">
                            <label for="form_label_text_color">Labels text color</label>
                            <div class="input-group colorpick">
                                <input type="text" name="form_label_text_color" value="<?php echo(htmlspecialchars($settings->form_label_text_color)) ?>" class="form-control" />
                                <span class="input-group-addon"><i></i></span>
                            </div>

                        </div>
                        <div class="form-group col-md-3">
                            <label for="form_label_text_size">Labels text size</label>
                            <select name="form_label_text_size" class="form-control">
                                <?php for($i=10;$i<=32;$i++){ ?>
                                    <option value="<?php echo($i) ?>" style="font-size: <?php echo($i)?>px" <?php echo(htmlspecialchars($settings->form_label_text_size)==$i?"selected":"") ?>><?php echo($i) ?>px</option>
                                <?php }?>
                            </select>
                        </div>
                        <div class="clearfix"></div>
                        <div class="form-group col-md-3">
                            <label for="form_button_background_color">Button background color</label>
                            <div class="input-group colorpick">
                                <input type="text" name="form_button_background_color" value="<?php echo(htmlspecialchars($settings->form_button_background_color)) ?>" class="form-control" />
                                <span class="input-group-addon"><i></i></span>
                            </div>

                        </div>
                        <div class="form-group col-md-3">
                            <label for="form_button_text_color">Button text color</label>
                            <div class="input-group colorpick">
                                <input type="text" name="form_button_text_color" value="<?php echo(htmlspecialchars($settings->form_button_text_color)) ?>" class="form-control" />
                                <span class="input-group-addon"><i></i></span>
                            </div>

                        </div>
                        <div class="form-group col-md-3">
                            <label for="form_button_text_size">Button text size</label>
                            <select name="form_button_text_size" class="form-control">
                                <?php for($i=10;$i<=32;$i++){ ?>
                                    <option value="<?php echo($i) ?>" style="font-size: <?php echo($i)?>px" <?php echo(htmlspecialchars($settings->form_button_text_size)==$i?"selected":"") ?>><?php echo($i) ?>px</option>
                                <?php }?>
                            </select>
                        </div>
                    </div>
                    <div id="theme_settings" style="display: <?php echo($settings->theme_type=='theme'?"block":"none")?>">
                        <h2>Predefined Themes</h2>
                        <hr>
                        <div class="form-group col-md-3">
                            <label for="selected_theme">Select Theme</label>
                            <select name="selected_theme" class="form-control">
                               <option value="CardStyle" <?php echo($settings->selected_theme=='CardStyle')?"selected":""; ?>>CardStyle</option>
                               <option value="Minimalist" <?php echo($settings->selected_theme=='Minimalist' || empty($settings->selected_theme))?"selected":""; ?>>Minimalist</option>
                               <option value="Colorful" <?php echo($settings->selected_theme=='Colorful' || empty($settings->selected_theme))?"selected":""; ?>>Colorful</option>
                               <option value="adaptive-lp" <?php echo($settings->selected_theme=='adaptive-lp')?"selected":""; ?>>Adaptive LP</option>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Theme Preview</label>
                            <div style="border: 1px solid #e5e5e5; border-radius: 8px; padding: 15px; background: #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                                <?php 
                                // Only use available themes: CardStyle, Minimalist, Colorful, Adaptive LP
                                $availableThemes = ['CardStyle', 'Minimalist', 'Colorful', 'adaptive-lp'];
                                $theme = !empty($settings->selected_theme) && in_array($settings->selected_theme, $availableThemes) 
                                    ? $settings->selected_theme 
                                    : 'Minimalist';
                                
                                // Define the correct image path for each theme
                                $themeImages = [
                                    'CardStyle' => rtrim($settings->site_url, '/') . "/assets/images/CardStyle.png",
                                    'Minimalist' => rtrim($settings->site_url, '/') . "/assets/images/Minimalist.png",
                                    'Colorful' => rtrim($settings->site_url, '/') . "/assets/images/Colorful.png",
                                    'adaptive-lp' => rtrim($settings->site_url, '/') . "/assets/images/Minimalist.png"
                                ];
                                
                                // Set default fallback to Minimalist
                                $defaultImage = $themeImages['Minimalist'];
                                $currentImage = $themeImages[$theme] ?? $defaultImage;
                                ?>
                                <img
                                    id="theme_preview"
                                    src="<?php echo $currentImage; ?>"
                                    data-theme-images='<?php echo json_encode($themeImages); ?>'
                                    alt="<?php echo htmlspecialchars($theme); ?>"
                                    style="width: 100%; height: auto; max-height: 400px; object-fit: contain; display: block; margin: 0 auto; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);"
                                    onerror="this.src='<?php echo $defaultImage; ?>'"
                                />
                                <p class="text-muted text-center mt-2" style="margin: 10px 0 0 0; font-size: 0.9em;">
                                    Previewing: <strong id="theme_preview_text"><?php echo htmlspecialchars($theme); ?></strong>
                                </p>
                            </div>
                            <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                var themeSelect = document.querySelector('select[name="selected_theme"]');
                                var themePreview = document.getElementById('theme_preview');
                                var themePreviewText = document.getElementById('theme_preview_text');
                                var themeImages = JSON.parse(themePreview.getAttribute('data-theme-images'));
                                
                                // Function to update theme selection
                                function updateThemeSelection(theme) {
                                    // Update the selected attribute in the dropdown
                                    var options = themeSelect.getElementsByTagName('option');
                                    for (var i = 0; i < options.length; i++) {
                                        if (options[i].value === theme) {
                                            options[i].setAttribute('selected', 'selected');
                                        } else {
                                            options[i].removeAttribute('selected');
                                        }
                                    }
                                    
                                    // Update preview text and image
                                    themePreviewText.textContent = theme;
                                    themePreview.src = themeImages[theme] || themeImages['Minimalist'];
                                    themePreview.alt = theme + ' Theme Preview';
                                }
                                
                                // Set initial selection
                                updateThemeSelection(themeSelect.value);
                                
                                // Update preview when theme changes
                                themeSelect.addEventListener('change', function() {
                                    updateThemeSelection(this.value);
                                    
                                    // Force form submission to save the selection
                                    this.form.submit();
                                });
                            });
                            </script>
                        </div>
                    </div>

                    <div class="clearfix"></div>

                    <hr/>
                    <div class="form-group col-md-12">
                        <button type="submit" class="btn btn-success btn-lg">Save</button>
                    </div>
                </form>
                <?php }else{ ?>
                    You have no permissions to view this section
                <?php } ?>
            </div>


        </div>
    </div>

<?php echo($a->getFooter()) ?>
